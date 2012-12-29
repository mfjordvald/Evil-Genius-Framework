<?php
namespace Evil\Core;

/**
 * Dispatcher
 * Provides dispatching and auto loading.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Dispatcher
{
	/**
	 * Dispatcher::__construct()
	 *
	 * @param Config      $config      Object holding the configuration variables.
	 * @param Application $application The bootstrapper class holding a few auto load methods.
	 * @return void
	 */
	public function __construct($config, $application = null)
	{
		if ( !is_null($application) )
			spl_autoload_unregister(array($application, 'autoLoadCore'));

		spl_autoload_register(array($this, 'autoLoadClasses'), true, true);

		$this->config = $config;
	}

	/**
	 * Dispatcher::load()
	 *
	 * @param string    $class     Class to instantialize.
	 * @param Arguments $arguments Arguments object.
	 * @return void
	 */
	public function load($class, Arguments $arguments)
	{
		require 'cachetracker.php';
		$cache_tracker = new CacheTracker($this->config);

		$class_path = $this->getClassPath($class);
		$class_name = $this->getClassName($class);

		require 'app/controllers/' . $class_path . '.php';

		ob_start();
		new $class_name($cache_tracker, $arguments);
		$data = ob_get_clean();
		echo $data;

		$cache_tracker->storePage($data);
	}

	/**
	 * Dispatcher::getClassPath()
	 * Get file path for the class.
	 *
	 * @param string $class Requested URI to get class name for.
	 * @return string The class name.
	 */
	protected function getClassPath($class)
	{
		return strtolower($this->sanitizeName($class));
	}

	/**
	 * Dispatcher::getClassName()
	 * Converts requested URI to class name.
	 *
	 * @param string $class Requested URI to convert to class name.
	 * @return string The class name.
	 */
	protected function getClassName($class)
	{
		return '\Evil\Controllers\\' . str_replace('/', '\\', $this->sanitizeName($class));
	}

	/**
	 * Dispatcher::sanitizeName()
	 * Sanitizes requested class name to fit actual class name.
	 *
	 * @param string $class Class name to sanitize.
	 * @return string The sanitized class name.
	 */
	protected function sanitizeName($class)
	{
		$search  = ['-', '//'];
		$replace = ['_', '/'];

		return ltrim(str_replace($search, $replace, $class), '/');
	}

	/**
	 * Dispatcher::autoLoadClasses()
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

		$namespace  = $this->getNamespace($class);
		$class_name = array_pop($namespace);
		$class_path  = implode('/', $namespace);

		if ( !empty($class_path) )
			$class_path .= '/';

		$app_path    = 'app/' . $class_path;
		$system_path = 'system/' . $class_path;

		if ( file_exists($app_path . $class_name . '.php') )
			include $app_path . $class_name . '.php';
		elseif ( file_exists($system_path . $class_name . '.php') )
			include $system_path . $class_name . '.php';
	}

	/**
	 * Dispatcher::getNamespace()
	 * Gets the namespace from the full class name.
	 *
	 * @param string $class Full class name.
	 * @return string The namespace.
	 */
	protected function getNamespace($class)
	{
		$namespace = explode('\\', $class);

		// First element might be empty on Windows, not on Linux.
		if ( empty($namespace[0]) )
			array_shift($namespace);

		array_shift($namespace);

		array_map(function($value) {
			return strtolower($value);
		}, $namespace);

		return $namespace;
	}
}