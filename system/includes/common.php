<?php
include $controller->loadInclude('Config');
\Evil\Core\CacheTracker::setNamespace($cache_namespace);
\Evil\Core\CacheTracker::setServer($cache_server_ip, $cache_server_port);

$this->template = $controller->loadLibrary('Template', 'CacheTest');

try {
	$this->sql = $controller->loadLibrary(
		'SQL/MySQLimproved',
		array(
			$db_host,
			$db_database,
			$db_username,
			$db_password,
			$db_debug
		),
		'Database' // Identifier to store library under in the library registry.
		           // Can later be accessed by calling ->loadLibrary('Database')
	);
}
catch (SQLException $e) {
	// Obviously show a proper error page here.
	if ($e->getCode() === 1)
		die('The database configuration was incomplete, this is our issue, we\'ll get it fixed soon! Sorry!.');
	else
		die('The database server is currently unavailable, please try again in a minute or two.');
}

$this->controller = $controller;