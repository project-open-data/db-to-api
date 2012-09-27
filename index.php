<?php

include dirname( __FILE__ ) . '/includes/functions.php';
include dirname( __FILE__ ) . '/includes/class.db-api.php';
include dirname( __FILE__ ) . '/config.php';

$query = $db_api->parse_query();
$db_api->set_db( $query['db'] );
$results = $db_api->query( $query );

$renderer = 'render_' . $query['format'];
$db_api->$renderer( $results, $query );
