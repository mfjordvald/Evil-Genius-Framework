<?php
namespace Evil\Core;

/**
 * Cache Controller
 * Provides dispatching and auto loading.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class CacheController
{
	/**
	 * CacheController::__construct()
	 *
	 * @param Config      $config      Object holding the configuration variables.
	 * @param string      $app_path    The application to use in paths.
	 * @param Application $application The bootstrapper class holding a few auto load methods.
	 * @return void
	 */
	public function __construct($config, $app_path, $application = null)
	{
		if ( !is_null($application) )
			spl_autoload_unregister(array($application, 'autoLoadCore'));

		spl_autoload_register(array($this, 'autoLoadClasses'), true, true);

		$this->config      = $config;
		$this->application = $app_path;
	}

	/**
	 * CacheController::load()
	 *
	 * @param string    $class     Class to instantialize.
	 * @param Arguments $arguments Arguments object.
	 * @return void
	 */
	public function load($class, Arguments $arguments)
	{
		// Included before controller so that it's available to the application.
		require 'cachetracker.php';
		#require 'basecontroller.php';

		$cache_tracker = new CacheTracker($this->config);

		// Dashes in URI map to underscores in class and file name.
		$class = ltrim(str_replace(array('-', '//'), array('_', '/'), $class), '/');

		$class     = strtolower($class);

		$class = '\Evil\Controllers\\' . str_replace('/', '\\', $class);

		require 'apps/' . $this->application . '/controllers/' . $class . '.php';

		ob_start();
		new $class($cache_tracker, $this->application, $arguments);
		$data = ob_get_clean();
		echo $data;

		$cache_tracker->storePage($data);
	}

	/**
	 * CacheController::autoLoadClasses()
	 * Auto load undefined classes.
	 *
	 * @param string $class Name of class to load.
	 * @return void
	 */
	public function autoLoadClasses($class)
	{
		// Only work on our namespace.
		if (substr($class,0, 4) !== 'Evil')
			return;

		$namespace = explode('\\', $class);

		// First element might be empty on Windows, not on Linux.
		if ( empty($namespace[0]) )
			array_shift($namespace);

		$class     = strtolower(array_pop($namespace)); // Class name is always the last element.
		$namespace = strtolower(implode('/', array_slice($namespace, 1))); // First element is not used for path.

		if ( !empty($namespace) )
			$namespace .= '/';

		$app_path    = 'apps/' . $this->application . '/' . $namespace;
		$system_path = 'system/' . $namespace;

		if ( file_exists($app_path . $class . '.php') )
			include $app_path . $class . '.php';
		elseif ( file_exists($system_path . $class . '.php') )
			include $system_path . $class . '.php';
	}
}