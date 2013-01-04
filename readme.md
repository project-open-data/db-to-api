Database to API
=======================

Dynamically generate RESTful APIs from the contents of a database table. Provides JSON, XML, and HTML. Supports most popular databases.

What Problem This Solves
------------------------

Creating an API to access information within existing database tables is laborious task, when done as a bespoke task. This is often dealt with by exporting the contents of the database as CSV files, and providing downloads of them as a “good enough” solution.

How This Solves It
------------------

*Database to API* acts as a filter, sitting between a database and the browser, allowing users to interact with that database as if it was a native API. The column names function as the key names. This obviates the need for custom code for each database layer.

When Alternative PHP Cache (APC) is installed, parsed data is stored within APC, which accellerates  its functionality substantially. While APC is not required, it is recommended highly.


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

* all rows in a table: `/[database]/[table].[format]`
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
2. Follow the below example to register a new dataset in `config.php`. Tip: It's best to provide read-only database credentials here.
3. Document the API.

How to Register a Dataset
-------------------------

Edit `config.php` to include a a single instance of the following for each dataset (including as many instances as you have datasets):

```php

$args = array( 
			'name' => database_name,
		    'username' => 'username',
			'password' => 'password',
			'server' => 'localhost',
			'port' => 3306,
			'type' => 'mysql',
			'table_blacklist' => array(),
			'column_blacklist' => array(),
);

register_db_api( 'dataset_name', $args );

```

*Note: All fields (other than the dataset name) are optional and will default to the above.*

Here is a `config.php` file for a MySQL database named “inspections,” accessed with a MySQL user named “website” and a password of “s3cr3tpa55w0rd,” with MySQL running on the same server as the website, with the standard port of 3306. All tables may be accessed by *Database to API* except for “cache” and “passwords,” and among the accessible tables, the “password_hint” column may not be accessed via *Database to API*. All of this is registered to create an API named “facility-inspections”.

```php

$args = array( 
			'name' => 'inspections',
		    'username' => 'website',
			'password' => 's3cr3tpa55w0rd',
			'server' => 'localhost',
			'port' => 3306,
			'type' => 'mysql',
			'table_blacklist' => array('cache', 'passwords'),
			'column_blacklist' => array('password_hint'),
);

register_db_api( 'facility-inspections', $args );

```

For a SQLite database, simply provide the path to the database in `name`.

For an Oracle database, you can either specify a service defined in tsnames.ora (e.g. `dept_spending`) or you can define an Oracle Instant Client connection string (e.g., `//localhost:1521/dept_spending`).

License
-------

GPLv3 or later.

Roadmap
-------

* Automagic documentation generation
