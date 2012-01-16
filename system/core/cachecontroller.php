<?php
namespace Evil\Core;

/**
 * Cache Controller
 * Provides basic framework functions such as auto loading and library loading.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class CacheController extends Controller
{
	/**
	 * Holds the name of the controller class.
	 *
	 * @var string
	 */
	protected $class;

	/**
	 * Holds a string representation of the arguments.
	 *
	 * @var string
	 */
	protected $arguments;

	/**
	 * An array of page-level cached libraries.
	 *
	 * @var array
	 */
	protected $libraries = array();

	/**
	 * CacheController::__construct()
	 *
	 * @param Config      $config      Object holding the configuration variables.
	 * @param Application $application The bootstrapper class holding a few auto load methods.
	 * @return void
	 */
	public function __construct($config, $application = null)
	{
		if ( !is_null($application) )
			spl_autoload_unregister(array($application, 'autoLoadCore'));

		spl_autoload_register(array($this, 'autoLoadClasses'));

		if ( !is_null($application) )
			spl_autoload_register(array($application, 'autoLoadError'));

		$this->config = $config;
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

		$this->cache_tracker = new CacheTracker($this->config);
		$arguments->set('CacheTracker', $this->cache_tracker);

		// Dashes in URI map to underscores in class and file name.
		$class = ltrim(str_replace(array('-', '//'), array('_', '/'), $class), '/');

		$this->class     = strtolower($class);
		$this->arguments = $arguments;

		$class = 'Evil\Controller\\' . str_replace('/', '\\', $class);

		require 'system/controllers/' . $this->class . '.php';

		ob_start();
		new $class($this, $arguments);
		$data = ob_get_clean();
		echo $data;

		$this->cache_tracker->storePage($data);
	}

	/**
	 * CacheController::loadLibrary()
	 * Factory for libraries. Uses an optional registry for caching libraries.
	 *
	 * @param string|array $library    Names of libraries to load.
	 * @param mixed        $arguments  Arguments to pass to the library.
	 * @param string       $identifier The identifier for the library.
	 * @param bool         $cache      Whether to cache the library in the registry or not.
	 * @return Object Object of the specified library.
	 */
	public function loadLibrary($library, $arguments = null, $identifier = null, $cache = true)
	{
		if ( empty($identifier) )
			$identifier = is_array($library) ? implode('-', $library) : $library;

		$identifier = strtolower($identifier);

		// Simple request-life library caching.
		if ( isset($this->libraries[$identifier]) )
			return $this->libraries[$identifier];

		if ( !is_array($library) )
			$library = array($library);

		if ( !isset($arguments['CacheTracker']) )
			$arguments['CacheTracker'] = $this->cache_tracker;

		$path = 'system/libraries/';

		foreach($library as $lib)
		{
			$lib = strtolower($lib);

			if ( file_exists($path . $lib . '.php') )
			{
				// Prevents double inclusion when loading multiple instances of same library.
				if ( !class_exists('Evil\Library\\' . str_replace('/', '\\', $lib), false) )
					require $path . $lib . '.php';

				$lib = 'Evil\Library\\' . str_replace('/', '\\', $lib);
				$library = new $lib($this, new Arguments($arguments));

				if ($cache)
					$this->libraries[$identifier] = $library;

				return $library;
			}
		}

		throw new CoreException('Failed to load library chain "' . implode(', ', $library) . '"');
	}

	/**
	 * CacheController::loadInclude()
	 * Returns path to specified file from the include directory.
	 *
	 * @param string $include Name of file to include.
	 * @return void
	 */
	public function loadInclude($include)
	{
		return 'system/includes/' . strtolower($include) . '.php';
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

		// First element is empty on Windows, not on Linux...
		if ( empty($namespace[0]) )
			array_shift($namespace);

		$class     = strtolower(array_pop($namespace)); // Class name is alwaysthe last element.
		$namespace = strtolower(implode('/', array_slice($namespace, 2))); // First two elements aren't used for path.

		if ( !empty($namespace) )
			$namespace .= '/';

		$libraries_path   = 'system/libraries/' . $namespace;
		$controllers_path = 'system/controllers/' . $namespace;

		if ( file_exists($libraries_path . $class . '.php') )
			include $libraries_path . $class . '.php';
		elseif ( file_exists($controllers_path . $class . '.php') )
			include $controllers_path . $class . '.php';
	}

	/**
	 * CacheController::redirect()
	 * Redirect to the same file keeping only $number amount of arguments.
	 *
	 * @todo Method should find the absolute URL and construct header properly.
	 * @param integer $number Number of arguments to include in the redirect.
	 * @return void
	 */
	public function redirect($number)
	{
		$arguments = $this->arguments->slice(0, $number);

		// Class name might be something we don't want to show in the URL.
		$class = str_replace('Index', '', $this->class);

		if ( !headers_sent() )
		{
			header('HTTP/1.1 301 Moved Permanently');

			if ( empty($arguments) )
				header('Location: /' . $class . '/' . implode('/', $arguments));
			else
				header('Location: /' . $class . '/' . implode('/', $arguments) . '/');
		}

		die('Should have redirected to /' . $class . '/' . implode('/', $arguments) );
	}

	/**
	 * CacheController::libraryExists()
	 * Checks whether or not the specified library is present in the libraries dir.
	 *
	 * @param string Name of the library to check exists.
	 * @return bool True if specified library exists, false otherwise
	 */
	public function libraryExists($lib)
	{
		return file_exists('system/libraries/' . strtolower($lib) . '.php');
	}
}