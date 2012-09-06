Database to RESTful API
=======================

Given database credentials, creates an interactive, RESTful API

Databases supported (in theory)
-------------------------------

* MySQL
* Oracle
* ODBC and DB2
* PostgreSQL
* SQLite
* MS SQL Server
* IBM
* CUBRID
* Firebird/Interbase
* Informix
* 4D

Output Formats
--------------

* JSON
* XML
* HTML

API Structure
-------------

* All rows in a table - `[database]/[table]/.[format]`
* Specific row in a table `[database]/[table]/[ID].[format]`
* All rows matching a query `[database]/[table]/[column]/[value].[format]`

Additional Parameters
---------------------

* `order_by` - name of column to sort by
* `direction` - either `ASC` or `DESC`, sort direction
* `limit` - number, maximum number of results to return

e.g., `/[table]/[column]/[value].[format]?order_by=[column]&direction=[direction]`

Requirements
------------

* PHP
* MySQL Database (for now)
* PDO Extension

Setting up
----------

1. Copy `config.sample.php` to `config.php`
2. Follow the in-line example to register a new dataset. Tip: It's best to provide read-only database credentials here.
3. Document the API

How to Register a Dataset
-------------------------

Edit `config.php` to include the following for each dataset:

```php

$args = array( 
			'name' => null,
		    'username' => 'root',
			'password' => 'root',
			'server' => 'localhost',
			'port' => 3306,
			'type' => 'mysql',
			'table_blacklist' => array(),
			'column_blacklist' => array(),
);

register_db_api( 'dataset-name', $args );

```

**Note: All fields (other than the dataset name) are option and will default to the above.**