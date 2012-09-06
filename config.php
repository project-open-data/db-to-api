<?php

$args = array( 
	'name' => 'wordpress',
	'port' => '8889',
	'table_blacklist' => array( 'wp_options' ),
);

register_db_api( 'test-db', $args );