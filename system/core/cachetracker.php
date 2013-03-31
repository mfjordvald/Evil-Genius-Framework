<?php
namespace Evil\Core;

/**
 * Cache Tracker
 * Provide methods and interfaces which enable easier tracking of data changes.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class CacheTracker
{
	/**
	 * Path where controllers are stored.
	 *
	 * @var string
	 */
	private $controller_path = 'app/controllers';

	/**
	 * Memcached object.
	 *
	 * @var Memcached
	 */
	private $memcached;

	/**
	 * CacheTracker::__construct()
	 *
	 * @param Config $config Object holding the configuration variables.
	 * @return void
	 */
	public function __construct($config)
	{
		$this->setNamespace($config->cache_namespace);
		$this->setServer($config->cache_server_ip, $config->cache_server_port);
		$this->setCaching($config->cache_content);
	}

	/**
	 * CacheTracker::getMemcached()
	 * Returns a memcached object.
	 *
	 * @return Memcached
	 */
	protected function getMemcached()
	{
		if ($this->memcached instanceof \Memcached)
			return $this->memcached;

		$this->memcached = new \Memcached();
		$this->memcached->addServer($this->server_ip, $this->server_port);
		$this->memcached->setOption(\Memcached::OPT_COMPRESSION, false);
		$this->memcached->setOption(\Memcached::OPT_NO_BLOCK, true);
		$this->memcached->setOption(\Memcached::OPT_TCP_NODELAY, true);

		return $this->memcached;
	}

	/**
	 * CacheTracker::getDataKeysAccessors()
	 * Iterates all controllers and checks for data keys.
	 * Used for buidling a dependency list.
	 *
	 * @return array Array of controllers defining data keys.
	 */
	public function getDataKeysAccessors()
	{
		$directory = new \RecursiveDirectoryIterator($this->controller_path);
		$iterator  = new \RecursiveIteratorIterator($directory);

		// Find all PHP controllers.
		$controllers = array();
		foreach($iterator as $controller)
		{
			if (substr($controller, -3) === 'php')
			{
				$controller    = str_replace($this->controller_path, '', $controller);
				$controllers[] = substr($controller, 0, -4);
			}
		}

		// And get their data keys if they read any.
		$keys = array();
		foreach ($controllers as $controller)
		{
			$controller = '\Evil\Controllers' . str_replace('/', '\\', $controller);
			if ( in_array('Evil\Core\ControllerCacheable', class_implements($controller)) )
			{
				$data = $controller::dataKeyReads();
				foreach ($data as $dataKey)
				{
					$keys[$dataKey][] = $controller;
				}
			}
		}

		return $keys;
	}

	/**
	 * CacheTracker::triggerDataKeyInvalidation()
	 * Trigger an invalidation of a set of data keys.
	 *
	 * @todo Consider supporting updating of content directly instead of deleting keys.
	 * @param array $invalidate [dataKey] => mixed $payload, [dataKey2] => mixed $payload, [..]
	 * @return void
	 */
	public function triggerDataKeyInvalidation($invalidate = array())
	{
		if ( !file_exists('deplist.txt') )
			$this->generateList();

		$accessors = json_decode(file_get_contents('deplist.txt'), true);

		if ( empty($accessors) )
			return;

		// Get controllers which access the keys we're invalidating.
		$controllers = array();
		foreach ($invalidate as $key => $payload)
		{
			if ( !isset($accessors[$key]) )
				continue;

			foreach ($accessors[$key] as $controller)
			{
				$controllers[] = $controller;
			}
		}

		// Get the cache keys (URIs) which the controllers generate.
		$keys = array();
		foreach ($controllers as $controller)
		{
			$controller = ltrim($controller, '\\');
			foreach($invalidate as $key => $payload)
			{
		 		$data = $controller::dataKeyInvalidates($key, $payload);
				foreach ($data as $uri)
				{
					$keys[] = $uri;
				}
 			}
		}

		$memcached = $this->getMemcached();

		$keys = array_unique($keys);
		foreach ($keys as $index => $key)
		{
			$keys[$index] = $this->namespace . $key;
		}
		$memcached->deleteMulti($keys);
	}

	/**
	 * CacheTracker::generateList()
	 * Generate the dependency list of controllers -> data keys.
	 *
	 * @return void
	 */
	public function generateList()
	{
		$accessors = json_encode($this->getDataKeysAccessors());
		file_put_contents('deplist.txt', $accessors);
	}

	/**
	 * CacheTracker::storePage()
	 * Stores the provided content in memcached.
	 *
	 * @param string $content The output content to cache.
	 * @return void
	 */
	public function storePage($content)
	{
		if (!$this->cache_content || $_SERVER['REQUEST_METHOD'] !== 'GET')
			return;

		if ( empty($this->namespace) )
			throw new CoreException('No cache namespace is defined.');

		if ( empty($this->server_ip) || empty($this->server_port) )
			throw new CoreException('No memcached server defined.');

		$key = $this->namespace . $_SERVER['REQUEST_URI'];

		$memcached = $this->getMemcached();
		$memcached->set($key, $content);
	}

	/**
	 * CacheTracker::setNamespace()
	 * Sets the namespace value prepended to all cache keys.
	 *
	 * @param string $namespace The namespace value to prepend to all cache keys.
	 * @return void
	 */
	public function setNamespace($namespace)
	{
		if ( empty($namespace) )
			throw new CoreException('Trying to define invalid namespace.');

		$this->namespace = $namespace;
	}

	/**
	 * CacheTracker::setCaching()
	 * Set whether to cache the page or not.
	 *
	 * @param bool $cache Whether to cache the page or not.
	 * @return void
	 */
	public function setCaching($cache)
	{
		if ( !is_bool($cache) )
			throw new CoreException('Invalid cache value, must be true or false.');

		$this->cache_content = $cache;
	}

	/**
	 * CacheTracker::setServer()
	 * Set whether to cache the page or not.
	 *
	 * @param integer $ip   The IP of the memcached server.
	 * @param integer $port The port of the memcached server.
	 * @return void
	 */
	public function setServer($ip, $port)
	{
		if ( !filter_var($ip, FILTER_VALIDATE_IP) )
			throw new CoreException('Server IP is not a valid IP.');

		if ( !is_integer($port) || $port < 1024 || $port > 65535  )
			throw new CoreException('Server port is not a valid port.');

		$this->server_ip   = $ip;
		$this->server_port = $port;
	}
}

/**
 * Controller Cachable
 * Interface to implement if the controller is cachable.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
interface ControllerCacheable
{
	/**
	 * ControllerCacheable::dataKeyReads()
	 * Defines an array of the data keys a controller reads from.
	 *
	 * @return array Array of data keys a controller reads from.
	 */
	public static function dataKeyReads();

	/**
	 * ControllerCacheable::dataKeyInvalidats()
	 * Defines the cache entries a certain key and payload invalidates.
	 *
	 * @param string $key     The key to determine which caches to invalidate.
	 * @param string $payload Data useful to determine which caches to invalidate.
	 * @return array The cache keys to invalidate.
	 */
	public static function dataKeyInvalidates($key, $payload);
}

/**
 * Library Cacheable
 * Optional interface for libaries to implement. Not currently in use.
 * Can help programmers determine which keys a library access.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
interface LibraryCacheable
{
	/**
	 * LibraryCacheable::dataKeyWrites()
	 * Defines an array of the data keys a library can write to.
	 *
	 * @return array Array of data keys a library can write to.
	 */
	public static function dataKeyWrites();
}