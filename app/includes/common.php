<?php
$this->cache_tracker = $cache_tracker;
$this->cache_tracker->setNamespace($cache_namespace);
$this->cache_tracker->setServer($cache_server_ip, $cache_server_port);

$this->template = new \Evil\Libraries\Template('CacheTest');

try {
	$this->database = new \Evil\Libraries\SQL\MySQLimproved($db_host, $db_database, $db_username, $db_password);
}
catch (\Evil\Libraries\SQL\SQLException $e) {
	// Obviously show a proper error page here.
	if ($db_debug)
		echo $e->getMessage();
	else
		die('The database server is currently unavailable, please try again in a minute or two.');
}