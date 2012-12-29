<?php
namespace Evil\Core;

/**
 * Profiling Dispatcher
 * Provides methods for profiling the application.
 *
 * !!! EXPERIMENTAL !!!
 *
 * THIS IS CURRENTLY SEVERELY OUTDATED.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Profiler extends Dispatcher
{
	/**
	 * Profiler::__construct()
	 *
	 * @param Config      $config      Object holding the configuration variables.
	 * @param Application $application The bootstrapper class holding a few auto load methods.
	 * @return void
	 */
	public function __construct($config, $application = null)
	{
		parent::__construct($config, $application);
	}

	/**
	 * Profiler::load()
	 *
	 * @param string    $class     Class to instantialize.
	 * @param Arguments $arguments Arguments object.
	 * @return void
	 */
	public function load($class, Arguments $arguments)
	{
		if ( function_exists('xhprof_enable') && is_dir('./thirdparty/xhprof_lib') )
			xhprof_enable(XHPROF_FLAGS_MEMORY);

		$this->cache_tracker = new CacheTracker();
		$arguments->set('CacheTracker', $this->cache_tracker);

		// Convert dashes in class name to underscores.
		$class = ltrim(str_replace(array('-', '//'), array('_', '/'), $class), '/');

		$this->class     = strtolower($class);
		$this->arguments = $arguments;

		$class = 'Evil\Controller\\' . str_replace('/', '\\', $class);

		require 'app/controllers/' . $this->class . '.php';

		ob_start();
		new $class($this, $arguments);
		ob_end_clean();

		foreach($this->libraries as $library)
		{
			var_dump($library->timings);
		}

		if ( function_exists('xhprof_enable') && is_dir('./thirdparty/xhprof_lib') )
		{
			$xhprof_data = xhprof_disable();

			require './thirdparty/xhprof_lib/utils/xhprof_lib.php';
			require './thirdparty/xhprof_lib/utils/xhprof_runs.php';

			$xhprof_runs = new \XHProfRuns_Default();
			$xhprof_runs->save_run($xhprof_data, 'profiling');
		}
	}

	/**
	 * Profiler::loadLibrary()
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

		if ( !is_array($arguments) )
			$arguments = array($arguments);

		if ( !isset($arguments['CacheTracker']) )
			$arguments['CacheTracker'] = $this->cache_tracker;

		$app_path    = 'app/libraries/';
		$system_path = 'system/libraries/';

		foreach($library as $lib)
		{
			$lib = strtolower($lib);

			$found = false;
			if ( file_exists($app_path . $lib . '.php') )
			{
				// Prevents double inclusion when loading multiple instances of same library.
				if ( !class_exists('Evil\Library\\' . str_replace('/', '\\', $lib), false) )
					require $app_path . $lib . '.php';

				$found = true;
			}
			elseif ( file_exists($system_path . $lib . '.php') )
			{
				// Prevents double inclusion when loading multiple instances of same library.
				if ( !class_exists('Evil\Library\\' . str_replace('/', '\\', $lib), false) )
					require $system_path . $lib . '.php';

				$found = true;
			}

			if (!$found)
				continue;

			$lib = 'Evil\Library\\' . str_replace('/', '\\', $lib);
			$library = new LibraryWrapper($this, new Arguments($arguments), $lib);

			if ($cache)
				$this->libraries[$identifier] = $library;

			return $library;
		}

		throw new CoreException('Failed to load library chain "' . implode(', ', $library) . '"');
	}
}

/**
 * Library Wrapper
 * Provides a wrapper for all libraries to enable profiling them.
 *
 * !!! EXPERIMENTAL !!!
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 * @todo Needs more testing.
 */
class LibraryWrapper
{
	/**
	 * The library object itself.
	 *
	 * @var Object
	 */
	public $library;

	/**
	 * Used to hold the timings of all library method calls.
	 *
	 * @var array
	 */
	public $timings = array();

	/**
	 * LibraryWrapper::__construct()
	 *
	 * @param Controller $controller The framework controller.
	 * @param Arguments  $arguments  The framework arguments object.
	 * @param string     $lib The library to load.
	 * @return void
	 */
	public function __construct($controller, $arguments, $lib)
	{
		$this->library = new $lib($controller, $arguments);
	}

	/**
	 * LibraryWrapper::__call()
	 *
	 * @param string    $name      Method name called.
	 * @param Arguments $arguments Arguments provided in method call.
	 * @return mixed Return value of the library method called.
	 */
	public function __call($name, $arguments)
	{
		$start_time = microtime(true);

		// Because fuck you that's why. Also no call user func.
		switch(count($arguments)) {
			case 0: $data = $this->library->{$name}(); break;
			case 1: $data = $this->library->{$name}($arguments[0]); break;
			case 2: $data = $this->library->{$name}($arguments[0], $arguments[1]); break;
			case 3: $data = $this->library->{$name}($arguments[0], $arguments[1], $arguments[2]); break;
			case 4: $data = $this->library->{$name}($arguments[0], $arguments[1], $arguments[2], $arguments[3]); break;
			case 5: $data = $this->library->{$name}($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]); break;
		}

		$end_time   = microtime(true);
		$time       = $end_time - $start_time;

		$this->timings[] = array(
			'Library' => get_class($this->library),
			'Method'  => $name,
			'Time'    => $time
		);

		return $data;
	}

	/**
	 * LibraryWrapper::__get()
	 *
	 * @param string $name Name of library class member called.
	 * @return mixed Value of the class member.
	 */
	public function __get($name)
	{
		return $this->library->$name;
	}

	/**
	 * LibraryWrapper::__set()
	 *
	 * @param string $name  Name of library class member to set.
	 * @param mixed  $value Value to set.
	 * @return void
	 */
	public function __set($name, $value)
	{
		$this->library->$name = $value;
	}
}

/**
 * Cache Tracker
 * Mocks the full CacheTracker class to ensure application does not fail when trying to use caching.
 *
 * @internal
 */
class CacheTracker
{
	public function getDataKeysAccessors(){return array();}
	public function triggerDataKeyInvalidation(){return;}
	public function generateList(){return;}
	public function storePage(){return;}
	public function setNamespace(){return;}
	public function setCaching(){return;}
	public function setServer(){return;}
}

/**
 * Controller Cacheable
 * Mocks the full ControllerCacheable interface to ensure application does not fail when trying to use caching.
 *
 * @internal
 */
interface ControllerCacheable
{
	public static function dataKeyReads();
	public static function dataKeyInvalidates($key, $payload);
}
/**
 * Library Cacheable
 * Mocks the full Libraryacheable interface to ensure application does not fail when trying to use caching.
 *
 * @internal
 */
interface LibraryCacheable
{
	public static function dataKeyWrites();
}