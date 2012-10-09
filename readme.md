Database to API
=======================

Dynamically generate RESTful APIs from the contents of a database table. Provides JSON, XML, and HTML. Supports most popular databases.

What Problem This Solves
------------------------

Creating an API to access information within existing database tables is laborious task, when done as a bespoke task. This is often dealt with by exporting the contents of the database as CSV files, and providing downloads of them as a “good enough” solution.

How This Solves It
------------------

Database to API acts as a filter, sitting between a database and the browser, allowing users to interact with that database as if it was a native API. The column names function as the key names. This obviates the need for custom code for each database layer.

When Alternative PHP Cache (APC) is installed, parsed data is stored within APC, which accellerates substantially its functionality.


Databases Supported
-------------------

* 4D
* CUBRID
* Firebird/Interbase
* IBM
* Informix
* MS SQL Server
* MySQL
* ODBC and DB2
* Oracle
* PostgreSQL
* SQLite

API Structure
-------------

* all rows in a table: `/[database]/[table]/.[format]`
* specific row in a table: `/[database]/[table]/[ID].[format]`
* all rows matching a query: `/[database]/[table]/[column]/[value].[format]`

Additional Parameters
---------------------

* `order_by`: name of column to sort by
* `direction`: direction to sort, either `asc` or `desc` (default `asc`)
* `limit`: number, maximum number of results to return

e.g., `/[database]/[table]/[column]/[value].[format]?order_by=[column]&direction=[direction]`

Requirements
------------

* PHP
* Database
* PHP Data Objects (PDO) Extension
* APC (optional)

Usage
-----

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

*Note: All fields (other than the dataset name) are optional and will default to the above.*

For a SQLite database, simply provide the path to the database in `name`.

For an Oracle database, you can either specify a service defined in tsnames.ora (e.g. `dept_spending`) or you can define an Oracle Instant Client connection string (e.g., `//localhost:1521/dept_spending`).

License
-------

GPLv3 or Later

Roadmap
-------

* Automagic documentation generation