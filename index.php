<?php
namespace Evil\Core;

/**
 * Application
 * A bootstrapper class.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Application
{
	/**
	 * Application::__construct()
	 * Setup the class.
	 *
	 * @param Config $config Object holding the configuration variables.
	 * @return void
	 */
	public function __construct($config)
	{
		header('Content-Type: text/html; charset=utf-8');

		set_exception_handler(array($this, 'handleException'));
		spl_autoload_register(array($this, 'autoLoadCore'));

		define('DS', DIRECTORY_SEPARATOR);

		$this->config = $config;
	}

	/**
	 * Application::initialize()
	 * Configure and start our application.
	 *
	 * @return void
	 */
	public function initialize()
	{
		$route = $this->getRoute();
		$route = $this->cleanRoute($route);

		$application = $this->getApplication();

		$router = new Router($route, $application, $this->config->user404, $this->config->cache_route);
		$class  = $router->getController();

		$controller = new $this->config->loader($this->config, $application, $this);
		$controller->load($class[0], $class[1]);
	}

	/**
	 * Application::getApplication()
	 * Analyse the request and figure out the app to load.
	 *
	 * @return string Application to load.
	 */
	protected function getApplication()
	{
		$application = strtolower($_SERVER['HTTP_HOST']);

		if ( empty($application) )
			throw new CoreException('Server does not support multi-app setup, please configure it to pass HOST header to PHP.');

		list($application) = explode(':', $application);

		if (stripos($application, '../') !== false)
			throw new CoreException('Upper directory travesal not allowed.');

		if ( !is_dir('apps/' . $application) )
			throw new CoreException('Application directory does not exist.');

		return $application;
	}

	/**
	 * Application::getRoute()
	 * Analyse the URI and figure out the route to load.
	 *
	 * @return string Route to load.
	 */
	protected function getRoute()
	{
		if ( !empty($_SERVER['REQUEST_URI']) ) // Web request.
		{
			$route = $_SERVER['REQUEST_URI'];
		}
		else // CLI request.
		{
			$root = dirname(array_shift($_SERVER['argv']));
			chdir($root);
			$route = '/' . implode('/', $_SERVER['argv']) . '/';
		}

		return $route;
	}

	/**
	 * Application::cleanRoute()
	 * Clean the route of unwanted elements.
	 *
	 * @param string $route The route to clean.
	 * @return string Cleaned route to load.
	 */
	protected function cleanRoute($route)
	{
		if (substr($route, 0, 7) === '/system')
			throw new CoreException('System dir access forbidden.');

		if (stripos($route, '/../') !== false)
			throw new CoreException('Upper directory travesal not allowed.');

		$route = str_replace('/index.php', '', $route);
		$route = trim($route, '/');
		$route = preg_replace('/[\\:*<>|"]/', '', $route); // Invalid URL characters.
		$route = preg_replace('#\?.+$#', '', $route);      // Remove any query path.

		return $route;
	}

	/**
	 * Application::handleException()
	 * Logs uncaught exceptions and echo an error. Does not stop script execution.
	 * @param Exception $exception Native Exception object.
	 * @return void
	 */
	public function handleException($exception)
	{
		$this->writeLog($exception->getMessage(), $exception->getFile(), $exception->getLine());
		echo '<h1>An exceptional error occured, this has been logged and will be fixed soon, sorry for the inconvenience.</h1>';

		if ($this->config->development)
			echo $exception->getMessage();
	}

	/**
	 * Application::autoLoadCore()
	 * Auto load undefined core classes.
	 *
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
	 * Application::autoLoadError()
	 * Last method in the autoload stack. Logs and dies with an error.
	 *
	 * @param string $class Name of class to load.
	 * @return void
	 */
	public function autoLoadError($class)
	{
		// Only work on our namespace.
		if (substr($class,0, 4) !== 'Evil')
			return;

		$this->writeLog('Failed to load required class ' . $class, $this->cleanRoute($this->getRoute()), 0);
		die('Failed to load required class ' . $class);
	}

    /**
     * Application::writeLog()
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
 * Exception class for Core framework
 *
 * @package Evil Genius Framework
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

require 'config.php';
$config = new Config();

$application = new Application($config);
$application->initialize($config);