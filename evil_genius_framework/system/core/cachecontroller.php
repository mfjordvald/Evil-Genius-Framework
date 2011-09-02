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
	private $class;
	private $arguments;
	private $libraries;

	/**
	 * Controller::__construct()
	 *
	 * @param string $module Module the class belongs to.
	 * @param string $class Class to instantialize.
	 * @param Arguments $arguments Arguments object.
	 * @return void
	 */
	public function __construct($class, Arguments $arguments, $initialize = null)
	{
		include 'cachetracker.php';

		if ( !is_null($initialize) )
			spl_autoload_unregister(array($initialize, 'autoLoadCore'));

		spl_autoload_register(array($this, 'autoLoadLibrary'));
		spl_autoload_register(array($this, 'autoLoadController'));

		if ( !is_null($initialize) )
			spl_autoload_register(array($initialize, 'autoLoadError'));

		unset($initialize);

		// Convert dashes in class name to underscores.
		$class = str_replace('-', '_', $class);

		$this->class     = strtolower($class);
		$this->arguments = $arguments;

		$class = 'Evil\Controller\\' . str_replace('/', '\\', $class);

		include 'system/controllers/' . $this->class . '.php';

		ob_start();
		new $class($this, $arguments);
		$data = ob_get_clean();

		CacheTracker::storePage($data);

		echo $data;
	}

	/**
	 * Controller::loadLibrary()
	 * Factory for libraries. Forces lazy loading.
	 *
	 * @param string|array $library string or an array of names of libraries to load.
	 * @param mixed $arguments Arguments to pass to the library.
	 * @param string $identifier The identifier for the library.
	 * @param bool $cache Whether to cache the library in the registry or not.
	 * @return Object Object of the specified library.
	 */
	public function loadLibrary($library, $arguments = null, $identifier = '', $cache = true)
	{
		//BUG: Will error if sent a library chain without an identifier
		$identifier = empty($identifier) ? strtolower($library) : strtolower($identifier);

		if ( isset($this->libraries[$identifier]) )
			return $this->libraries[$identifier];

		if ( !is_array($library) )
			$library = array($library);

		$path = 'system/libraries/';

		foreach($library as $lib)
		{
			$lib = strtolower($lib);

			if ( file_exists($path . $lib . '.php') )
			{
				if ( !class_exists('Evil\Library\\' . $lib, false) )
					include $path . $lib . '.php';

				$lib = 'Evil\Library\\' . str_replace('/', '\\', $lib);

				$library = new $lib($this, new Arguments($arguments));

				if ($cache && !empty($identifier) )
					$this->libraries[$identifier] = $library;

				return $library;
			}
		}

		throw new CoreException('Failed to load library chain "' . implode(', ', $library) . '"');
	}

	/**
	 * Controller::loadInclude()
	 * Returns path to specified file from the include directory.
	 *
	 * @param string $include Name of file to include.
	 * @example include $controller->loadInclude('Common');
	 * @return void
	 */
	public function loadInclude($include)
	{
		return 'system/includes/' . strtolower($include) . '.php';
	}

	/**
	 * Initialize::autoLoadLibrary()
	 * Auto load undefined library classes.
	 *
	 * @param string $class Name of class to load.
	 * @return void
	 */
	public function autoLoadLibrary($class)
	{
		if (substr($class,0, 4) !== 'Evil')
			return;

		$namespace = explode('\\', $class);

		// First element is empty on windows, not on Linux...
		if ( empty($namespace[0]) )
			array_shift($namespace);

		$class     = strtolower(array_pop($namespace));
		$namespace = strtolower(implode('/', array_slice($namespace, 2)));

		if ( !empty($namespace) )
			$namespace .= '/';

		$path = 'system/libraries/' . $namespace;

		if ( file_exists($path . strtolower($class) . '.php') )
			include $path . strtolower($class) . '.php';
	}

	/**
	 * Initialize::autoLoadController()
	 * Auto load undefined controller classes.
	 *
	 * @param string $class Name of class to load.
	 * @return void
	 */
	public function autoLoadController($class)
	{
		if (substr($class,0, 4) !== 'Evil')
			return;

		$namespace = explode('\\', $class);

		// First element is empty on windows, not on Linux...
		if ( empty($namespace[0]) )
			array_shift($namespace);

		$class     = strtolower(array_pop($namespace));
		$namespace = strtolower(implode('/', array_slice($namespace, 2)));

		if ( !empty($namespace) )
			$namespace .= '/';

		$path = 'system/controllers/' . $namespace;

		if ( file_exists($path . strtolower($class) . '.php') )
			include $path . strtolower($class) . '.php';
	}

	/**
	 * Controller::redirect()
	 * Redirect to the same file keeping only $number amount of arguments.
	 *
	 * @param integer $number Number of arguments to include in the redirect.
	 * @return void
	 */
	public function redirect($number)
	{
		// Get a slice of the first $number arguments.
		$arguments = $this->arguments->slice(0, $number);

		// Clean the class name, we don't want Index showing since it's found by default.
		$class = str_replace('Index', '', $this->class);

		if ( !headers_sent() )
		{
			//header('HTTP/1.1 301 Moved Permanently');

			if (count($arguments) == 0)
				header('Location: /' . $class . '/' . implode('/', $arguments));
			else
				header('Location: /' . $class . '/' . implode('/', $arguments) . '/');
		}

		// In case headers have already been sent.
		die('Should have redirected to /' . $class . '/' . implode('/', $arguments) );
	}
}