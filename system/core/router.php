<?php
namespace Evil\Core;

/**
 * Router
 * Finds the controller to handle the request based on a provided route.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Router
{
	/**
	 * Initialize::__construct()
	 * Configure our framework environment.
	 *
	 * @param string $route       The route to find a controller for.
	 * @param string $user_404    Whether to return 404 or let user controller handle it.
	 * @param string $cache_route Whether or not to use cached routing file.
	 * @return void
	 */
	public function __construct($route, $user_404 = false, $cache_route = false)
	{
		$this->route       = $route;
		$this->user404     = $user_404;
		$this->cache_route = $cache_route;
	}

	/**
	 * Router::getController()
	 * Load controller by calling the fastest function allowed.
	 *
	 * @return Array array($controller, $arguments)
	 */
	public function getController()
	{
		if (!$this->cache_route)
			return $this->getControllerFromFileSystem($this->route);
		else
			return $this->getControllerFromCache($this->route);
	}

	/**
	 * Router::getControllerFromFileSystem()
	 * Load appropiate controller, start at the deepest level and ascend the file
	 * structure until a fitting controller is found, otherwise 404.
	 * This method looks for files in the following order:
	 * - The full path with .php appended.
	 * - The full path with /index.php appended.
	 * - A part of the path is popped and process restarted.
	 *
	 * @param string $route The route used to find the controller.
	 * @return Array array($controller, $arguments)
	 */
	public function getControllerFromFileSystem($route)
	{
		// Store the route for arguments later on.
		$route     = explode('/', $route);
		$arguments = $route;

		// Deepest point to top point is the reverse route.
		$reverse = array_reverse($route, true);

		foreach($reverse as $key => $url_fragment)
		{
			// Remove the url fragment we're trying now to get our path.
			array_pop($route);
			$path = strtolower(implode('/', $route));
			$path = empty($path) ? $path : $path . '/';

			// - in the URI maps to _ in filename.
			$url_fragment = str_replace('-', '_', strtolower($url_fragment));

			if (file_exists('app/controllers/' . $path .  $url_fragment . '.php'))
				return array($path . $url_fragment, new Arguments( array_slice($arguments, $key + 1) ));
			elseif (file_exists('app/controllers/' . $path . $url_fragment . '/index.php'))
				return array($path . $url_fragment . '/Index', new Arguments( array_slice($arguments, $key + 1) ));
		}

		// Try index.php in root as a last resort.
		// Empty route because otherwise we'd never have a 404, unless user wants to handle 404.
		if (!$this->user404 && empty($arguments) && file_exists('app/controllers/index.php') )
			return array('Index', new Arguments($arguments));
		elseif ($this->user404 && file_exists('app/controllers/index.php') )
			return array('Index', new Arguments($arguments));
		else
		{
			header('HTTP/1.1 404 Not Found');
			die('404 Controller Not Found');
		}
	}

	/**
	 * Router::getControllerFromCache()
	 * Use the cached controller routes to find the most specific one.
	 *
	 * @param string $route The route used to find the controller.
	 * @return Array array($controller, $arguments)
	 */
	protected function getControllerFromCache($route)
	{
		$cached = $this->getCachedPaths();

		$selected = array(
			'index'       => 0,
			'specificity' => 0
		);

		foreach($cached as $key => $controller)
		{
			$length      = strlen($this->route);
			$specificity = 0;
			for($x = 0; $x < $length; $x++)
			{
				// Stop when our alphabetically sorted list goes past first letter in route.
				if ($controller[0] > $this->route[0])
					break 2;

				// Skip to next when the left prefix stops matching.
				if(empty($controller[$x]) || $controller[$x] !== $this->route[$x])
					continue 2;

				if ( !ctype_alpha($this->route[$x]) )
					continue;

				$specificity++;
				if ($specificity > $selected['specificity'])
					$selected = array('index' => $key, 'specificity' => $specificity);
			}
		}

		$controller = $cached[$selected['index']];
		$arguments  = explode('/', str_replace($controller . '/', '', $this->route));

		return array($controller, new Arguments($arguments));
	}

	/**
	 * Router::getCachedPaths()
	 *
	 * @return array Cached controller paths.
	 */
	protected function getCachedPaths()
	{
		if ( !file_exists('route_cache') )
		{
			$controllers = $this->getControllerPaths();
			sort($controllers); // Want list to be alphabetical for index lookups.
			$this->saveCachedPaths($controllers);
		}

		return json_decode(file_get_contents('route_cache'), true);
	}

	/**
	 * Router::saveCachedPaths()
	 *
	 * @return void
	 */
	protected function saveCachedPaths($controllers)
	{
		file_put_contents('route_cache', json_encode($controllers));
	}

	/**
	 * Router::getControllerPaths()
	 * Recurse all controllers and return their paths.
	 *
	 * @return array Controller paths.
	 */
	public function getControllerPaths()
	{
		$directory = new \RecursiveDirectoryIterator('system/controllers');
		$iterator  = new \RecursiveIteratorIterator($directory);

		// Find all PHP controllers.
		$controllers = array();
		foreach($iterator as $controller)
		{
			if (substr($controller, -3) === 'php')
			{
				$controller    = str_replace('system/controllers', '', $controller);
				$controllers[] = str_replace('\\', '/', substr($controller, 1, -4));
			}
		}

		return $controllers;
	}
}