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
		spl_autoload_register(array($this, 'autoLoadError'));

		define('DS', DIRECTORY_SEPARATOR);

		$this->config = $config;
	}

	/**
	 * Application::run()
	 * Configure and start our application.
	 *
	 * @return void
	 */
	public function run()
	{
		if ( $this->isCLIRequest() )
		{
			$this->checkCLIEnvironment();
			$this->prepareCLIEnvironment();
			$route = $this->getCLIRoute();
		}
		else
		{
			$route = $this->getWebRoute();
		}

		$route = $this->cleanRoute($route);

		$router = new Router($route, $this->config->user404, $this->config->cache_route);
		$class  = $router->getController();

		if ($this->config->profile)
			$dispatcher = new ProfileDispatcher($this->config, $this);
		else
			$dispatcher = new Dispatcher($this->config, $this);

		$dispatcher->load($class[0], $class[1]);
	}

	private function isCLIRequest()
	{
		return empty($_SERVER['REQUEST_URI']);
	}

	private function getWebRoute()
	{
		if ( empty($_SERVER['REQUEST_URI']) )
			return false;

		return $_SERVER['REQUEST_URI'];
	}

	private function getCLIRoute()
	{
		return $_SERVER['argv'][2];
	}

	private function checkCLIEnvironment()
	{
		if ($_SERVER['argc'] != 3)
			die($this->outputCLIUsage());
	}

	private function prepareCLIEnvironment()
	{
		$root = dirname($_SERVER['argv'][0]);
		chdir($root);
	}

	private function outputCLIUsage()
	{
		echo <<<'EOD'
CLI Usage:
php -f index.php application route
Application is similar to the HOST header sent by a web browser.
route is deliminated by a forward slash.
Example:
php -f index.php www.example.com /blog/2010/post-title/
EOD;
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
		if (substr($route, 0, 7) === '/system/')
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
		echo '<h1>An error occured, this has been logged and will be fixed soon, sorry for the inconvenience.</h1>';

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

		throw new CoreException('Failed to load required class ' . $class);
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
		$entry = date('[Y-m-d]') . ' :: Line ' . $line . ' :: ' . $file . ' :: ' . $message . "\r\n";
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
$application->run($config);