<?php
namespace Evil\Core;

/**
 * Bootstrapper
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Initialize
{
	/**
	 * When set to false any path that only match the root index.php controller
	 * will result in a 404.
	 * When set to true the request will instead be passed to the root index.php
	 * controller, provided that it exists.
	 */
	private $user404     = false;

	/**
	 * The controller loader to use, currently only CacheController exists.
	 */
	private $loader      = 'Evil\Core\CacheController';

	/**
	 * True will result in core errors being output.
	 */
	private $development = true;

	/**
	 * Initialize::__construct()
	 * Configure our framework environment.
	 * @return void
	 */
	public function __construct()
	{
		header('Content-Type: text/html; charset=utf-8');

		set_exception_handler(array($this, 'handleException'));
		spl_autoload_register(array($this, 'autoLoadCore'));

		// Figure out what todo.
		$route = $this->findRoute();
		$this->findController($route);
	}

	/**
	 * Initialize::findRoute()
	 * Analyse the URI and figure out the route to load.
	 * @return array Route to load.
	 */
	protected function findRoute()
	{
		if ( empty($_SERVER['argv']) )
		{
			// Web request.
			$route = $_SERVER['REQUEST_URI'];
		}
		else
		{
			// CLI request.
			$root = dirname(array_shift($_SERVER['argv']));
			chdir($root);
			$route = '/' . implode('/', $_SERVER['argv']) . '/';
		}

		// Make sure they don't want us to display anything system specific.
		if (substr($route, 0, 7) === '/system')
			die('denied');

		// Or anything OS specific.
		if (stripos($route, '../') !== false)
			die('denied');

		// Remove a few unwanted things.
		$route = str_replace('/index.php', '', $route);
		$route = trim($route, '/');
		$route = preg_replace('/[\\:*?<>|"]/', '', $route); // Invalid URL characters
		$route = explode('/', $route);

		return $route;
	}

	/**
	 * Initialize::loadController()
	 * Load appropiate controller, start at the deepest level and ascend the file
	 * structure until a fitting controller is found, otherwise 404.
	 * This method looks for files in the following order:
	 * - The full path with .php appended.
	 * - The full path with /index.php appended.
	 * - A part of the path is popped and process restarted.
	 * @return Controller|bool The chosen Controller or false.
	 */
	protected function findController($route)
	{
		if ( empty($route) )
		{
			header('HTTP/1.1 404 Not Found');
			return false;
		}

		// Store the route for arguments.
		$arguments = $route;

		// Deepest point to top point is the reverse route.
		$reverse = array_reverse($route, true);

		foreach($reverse as $key => $part)
		{
			$path = implode('/', $route);

			// Since $key is from the reverse route and we pop elements this will remove
			// the elements we have already tried, thus finding the new file to look for.
			list($file) = array_slice($route, $key);

			// If a file part is found then remove that from our path.
			$path = !empty($file) ? str_replace($file, '', $path): '';

			$path = strtolower($path);
			$file = str_replace('-', '_', strtolower($file));

			if (file_exists('system/controllers/' . $path .  $file . '.php'))
				return $this->loadController($path . $file, new Arguments( array_slice($arguments, $key + 1) ), $this);
			elseif (file_exists('system/controllers/' . $path . $file . '/index.php'))
				return $this->loadController($path . $file . 'Index', new Arguments( array_slice($arguments, $key + 1) ), $this);

			// Pop the element that did not resolve in a file.
			array_pop($route);
		}

		// Index.php in root, last resort.
		// Empty route because otherwise we'd never have a 404, possibly leave 404'ing up to the end-user?
		if (!$this->user404 && empty($arguments) && file_exists('system/controllers/index.php') )
			return $this->loadController('Index', new Arguments($arguments), $this);
		else if ($this->user404 && file_exists('system/controllers/index.php') )
			return $this->loadController('Index', new Arguments($arguments), $this);
		else
			header('HTTP/1.1 404 Not Found');

		return false;
	}

	/**
	 * Initialize::loadController()
	 * Loads the specified controller.
	 * @param string $route The route that resulted in a 404.
	 * @return Controller The chosen Controller.
	 */
	protected function loadController($path, $file, $arguments)
	{
		return new $this->loader($path, $file, $arguments, $this);
	}

	/**
	 * Initialize::handleException()
	 * Logs uncaught exceptions and echo an error. Does not stop script execution.
	 * @param Exception $exception Native Exception object.
	 * @return void
	 */
	public function handleException($exception)
	{
		$this->writeLog($exception->getMessage(), $exception->getFile(), $exception->getLine());
		echo '<h1>An exception went uncaught! Check the log for further details.</h1>';

		if ($this->development)
			echo $exception->getMessage();
	}

	/**
	 * Initialize::autoLoadCore()
	 * Auto load undefined core classes.
	 * Die if class isn't found.
	 * @param string $class Name of class to load.
	 * @return void
	 */
	public function autoLoadCore($class)
	{
		$class = explode('\\', $class);
		$class = strtolower(end($class));

		if ( file_exists('system/core/' . $class . '.php') )
			include 'system/core/' . $class . '.php';
	}

	/**
	 * Initialize::autoLoadError()
	 * Last method in the autoload stack. Logs and dies with an error.
	 * @param string $class Name of class to load.
	 * @return void
	 */
	public function autoLoadError($class)
	{
		if (substr($class,0, 4) !== 'Evil')
			return;

		$this->writeLog('Failed to load required class ' . $class, $_SERVER['REQUEST_URI'], 0);
		die('Failed to load required class ' . $class);
	}

    /**
     * Initialize::writeLog()
     *
     * @param string $message Message text to log.
     * @param string $file File that triggered the log event.
     * @param integer $line Line that  triggered the log event.
     * @return void
     */
    private function writeLog($message, $file, $line)
    {
		$entry = date('[Y-m-d]') . $line . ' :: ' . $file . ' | ' . $message . "\r\n";
		file_put_contents('./system/log.txt', $entry, FILE_APPEND);
	}
}

/**
 * CoreException
 *
 * Exception class for Core framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class CoreException extends \Exception
{
	public function __construct ($message = 'A core function failed', $code = 0)
	{
		parent::__construct($message, $code);
	}
}

// Start the whole thing.
new Initialize();