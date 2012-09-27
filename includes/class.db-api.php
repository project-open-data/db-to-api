<?php

class DB_API {

	public $dbs = array();
	public $db = null;
	public $dbh = null;
	public $query = array();
	static $instance;
	public $ttl = 3600;
	public $cache = array();
	public $connections = array();

	function __construct() {

		self::$instance = &$this;

	}

	/** 
	 * Returns static reference to the class instance
	 */
	public static function &get_instance() {

		return self::$instance;

	}

	/**
	 * Register a new dataset
	 * @param string $name the dataset name
	 * @param array $args the dataset properties
	 */
	function register_db( $name = null, $args = array() ) {

		$defaults = array(
			'name' => null,
			'username' => 'root',
			'password' => 'root',
			'server' => 'localhost',
			'port' => 3306,
			'type' => 'mysql',
			'table_blacklist' => array(),
			'column_blacklist' => array(),
			'ttl' => $this->ttl,
		);

		$args = shortcode_atts( $defaults, $args );
		$name = $this->slugify( $name );

		$this->dbs[$name] = (object) $args;

	}

	/**
	 * Retrieves a database and its properties
	 * @param string $db the DB slug (optional)
	 * @return array the database property array
	 */
	function get_db( $db = null ) {

		if ( $db == null && !is_null($this->db) ) {
			return $this->db;
		}

		if ( is_object( $db ) ) {
			var_dump( debug_backtrace() );//$db = $db->name;
		}
		
		
		if ( !array_key_exists( $db, $this->dbs ) ) {
			$this->error( 'Invalid Database' );
		}

		return $this->dbs[$db];

	}

	/**
	 * Sets the current database
	 * @param string $db the db slug
	 * @return bool success/fail
	 */
	function set_db( $db = null ) {

		$db = $this->get_db( $db );

		if ( !$db ) {
			return false;
		}
		
		$this->db = $db;

		return true;
		
	}


	/**
	 * Modifies a string to remove all non-ASCII characters and spaces.
	 * http://snipplr.com/view.php?codeview&id=22741
	 */
	function slugify( $text ) {

		// replace non-alphanumeric characters with a hyphen
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);

		// trim off any trailing or leading hyphens
		$text = trim($text, '-');

		// transliterate from UTF-8 to ASCII
		if (function_exists('iconv')) {
			$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
		}

		// lowercase
		$text = strtolower($text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		// ensure that this slug is unique
		$i=1;
		while ( array_key_exists( $text, $this->dbs ) ) {
			$text .= "-$i";
			$i++;
		}

		return $text;
	}

	/**
	 * Parses rewrite and actual query var and sanitizes
	 * @return array the query arg array
	 * @param $query (optional) a query other than the current query string
	 */
	function parse_query( $query = null ) {

		if ( $query == null ) {
			$query = $_SERVER['QUERY_STRING'];
		}

		parse_str( $query, $parts );

		$defaults = array(
			'db' => null,
			'table' => null,
			'order_by' => null,
			'direction' => 'ASC',
			'column' => null,
			'value' => null,
			'limit' => null,
			'format' => 'json',
		);

		$parts = shortcode_atts( $defaults, $parts );

		if ( $parts['db'] == null ) {
			$this->error( 'Must select a database' );
		}

		if ( $parts['table'] == null ) {
			$this->error( 'Must select a table' );
		}

		$db = $this->get_db( $parts['db'] );

		if ( in_array( $parts['table'], $db->table_blacklist ) ) {
			$this->error( 'Invalid table' );
		}

		if ( !in_array( $parts['direction'], array( 'ASC', 'DESC' ) ) ) {
			$parts['direction'] = null;
		}

		if ( !in_array( $parts['format'], array( 'html', 'xml', 'json' ) ) ) {
			$parts['format'] = null;
		}

		return $parts;

	}

	/**
	 * Establish a database connection
	 * @param string $db the database slug
	 * @return object the PDO object
	 * @todo support port #s and test on each database
	 */
	function &connect( $db ) {
		
		// check for existing connection
		if ( isset( $this->connections[$db] ) ) {
			return $this->connections[$db];
		}
			
		$db = $this->get_db( $db );

		try {
			$dbh = new PDO("mysql:host={$db->server};dbname={$db->name}", $db->username, $db->password );
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			echo $e->getMessage();
		}

		// cache
		$this->connections[$db] = &$dbh;
		
		return $dbh;

	}

	/**
	 * Verify a table exists, used to sanitize queries
	 * @param string $query_table the table being queried
	 * @param string $db the database to check
	 * @param return bool true if table exists, otherwise false
	 */
	function verify_table( $query_table, $db = null ) {
		
		$tables = $this->cache_get( $this->get_db( $db )->name . '_tables' );
		
		if ( !$tables  ) {
		
			$dbh = &$this->connect( $db );
			try { 
				$stmt = $dbh->query( 'SHOW TABLES' );
			} catch( PDOException $e ) {
				echo $e->getMessage();
			}
			
			$tables = array();
			while ( $table = $stmt->fetch() ) {
				$tables[] = $table[0];
			}
		
		}
		
		return in_array( $query_table, $tables );
		
	}

	/**
	 * Returns an array of all columns in a table
	 * @param string $table the table to check
	 * @param string $db the database to check
	 * @return array an array of the column names
	 */
	function get_columns( $table, $db = null ) {

		if ( !$this->verify_table( $table ) ) {
			return false;
		}
			
		$key = $this->get_db( $db )->name . '.' . $table . '_columns';
		
		if ( $cache = $this->cache_get( $key ) ) {
			return $cache;
		}
			
		$dbh = &$this->connect( $db );
		
		try {
			$q = $dbh->prepare( "DESCRIBE $table" );
			$q->execute();
			$columns = $q->fetchAll(PDO::FETCH_COLUMN);
		} catch( PDOException $e ) {
			echo $e->getMessage();
		}
		
		$this->cache_set( $key, $columns, $db->ttl );
		return $columns;
	}

	/**
	 * Verify a column exists
	 * @param string $column the column to check
	 * @param string $table the table to check
	 * @param string $db (optional) the db to check
	 * @retrun bool true if exists, otherwise false
	 */
	function verify_column( $column, $table, $db = null ) {

		$columns = $this->get_columns( $table, $db );
		return in_array( $column, $columns );

	}

	/**
	 * Returns the first column in a table
	 * @param string $table the table 
	 * @param string $db the datbase slug
	 * @return string the column name
	 */
	function get_first_column( $table, $db = null ) {

		return reset( $this->get_columns( $table, $db ) );

	}

	/**
	 * Build and execute the main database query
	 * @param array $query the database query ASSUMES SANITIZED
	 * @return array an array of results
	 */
	function query( $query, $db = null ) {
	
		$key = md5( serialize( $query ) . $this->get_db( $db )->name );
		
		if ( $cache = $this->cache_get( $key ) ) {
			return $cache;
		}

		try {

			$dbh = &$this->connect( $db );

			//santize table name
			if ( !$this->verify_table( $query['table'] ) ) {
				$this->error( 'Invalid Table' );
			}

			//santize column name
			if ( $query['column'] ) {
				if ( !$this->verify_column( $query['column'], $query['table'] ) ) {
					$query['column'] = null;
				}

				$sql = 'SELECT * FROM ' . $query['table'];
			}

			if ( $query['value'] && $query['column'] == null ) {
				$query['column'] = $this->get_first_column( $query['table'] );
			}

			if ( $query['value'] && $query['column'] ) {
				$sql .= " WHERE `{$query['table']}`.`{$query['column']}` = :value";
			}

			if ( $query['order_by'] && $query['direction'] ) {

				if ( !$this->verify_column( $query['order_by'], $query['table'] ) ) {
					return false;
				}

				$sql .= " ORDER BY `{$query['table']}`.`{$query['order_by']}` {$query['direction']}";

			}

			if ( $query['limit'] ) {
				$sql .= " LIMIT " . (int) $query['limit'];
			}

			$sth = $dbh->prepare( $sql );
			$sth->bindParam( ':value', $query['value'] );
			$sth->execute();

			$results = $sth->fetchAll( PDO::FETCH_OBJ );
			$results = $this->sanitize_results( $results );

		} catch( PDOException $e ) {
			echo $e->getMessage();
		}
		
		$this->cache_set( $key, $results, $db->ttl );
		
		return $results;

	}


	function sanitize_results( $results, $db = null ) {

		$db = $this->get_db( $db );

		if ( empty( $db->column_blacklist ) ) {
			return $results;
		}

		foreach ( $results as $ID => $result ) {

			foreach ( $db->column_blacklist as $column ) {
				unset( $results[ $ID ][ $column] );
			}

		}

		return $results;

	}


	function error( $error, $code = '500' ) {
		http_response_code( $code );
		die( $error );
		return false;

	}


	function render_json( $data ) {

		header('Content-type: application/json');
		echo json_encode( $data );

	}


	function render_html( $data ) {

		var_dump( $data );
	}


	function render_xml( $data ) {

		echo "XML HERE";

	}


	function cache_get( $key ) {

		if ( function_exists( 'apc_fetch' ) ) {
			return apc_fetch( $key );
		}

		if ( isset( $this->cache[ $key ] ) ) {
			return $this->cache[ $key ];
		}

		return false;

	}


	function cache_set( $key, $value, $tll = null ) {

		if ( $ttl == null ) {
			$ttl = ( isset( $this->db->ttl) ) ? $this->db->ttl : $this->ttl;
		}

		$key = 'db_api_' . $key;

		if ( function_exists( 'apc_store' ) ) {
			return apc_store( $key, $value, $ttl );
		}

		$this->cache[$key] = $value;


	}


}


$db_api = new DB_API();