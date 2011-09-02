<?php
namespace Evil\Core;

/**
 * Controller Cachable
 * Interface to implement if the controller is cachable.
 */
interface ControllerCacheable
{
	/**
	 * ControllerCacheable::dataKeyWrites()
	 * Defines the data keys a controller reads from.
	 *
	 * @return array Array of data keys a controller reads from.
	 */
	public static function dataKeyReads();

	/**
	 * ControllerCacheable::dataKeyWrites()
	 * Defines the cache keys a data key generates.
	 *
	 * @return array Array of cache keys to invalidate.
	 */
	public static function dataKeyInvalidates($key, $payload);
}

/**
 * Library Cacheable
 * Optional interface for libaries to implement. Not currently in use.
 * Can help programmers determine which keys a library access.
 */
interface LibraryCacheable
{
	/**
	 * LibraryCacheable::dataKeyWrites()
	 * Defines the data keys a library can write to.
	 *
	 * @return array Array of data keys a library can write to.
	 */
	public static function dataKeyWrites();
}

/**
 * Cache Tracker
 * Provide methods and interfaces which enable easier tracking of data changes.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 * @todo Streamline memcached access to avoid multiple points of entrace.
 */
class CacheTracker
{
	/**
	 * Whether to cache the page or not.
	 * It's recommended to keep false while developing.
	 * Can also be used to disable caching of specific pages which should
	 * never be cached.
	 */
	private static $cache = false;

	/**
	 * String value prepended to all cache keys to avoid clashes with other
	 * applications using same memcached store.
	 */
	private static $namespace;

	/**
	 * IP and port of memcached server to store pages in.
	 */
	private static $server_ip;
	private static $server_port;

	private static $controller_path = 'system/controllers';

	/**
	 * CacheTracker::getDataKeysAccessors()
	 * Iterates all controllers and checks for data keys.
	 * Used for buidling a dependency list.
	 *
	 * @return array Array of controllers defining data keys.
	 */
	public static function getDataKeysAccessors()
	{
		$directory = new \RecursiveDirectoryIterator(self::$controller_path);
		$iterator  = new \RecursiveIteratorIterator($directory);

		$controllers = array();
		foreach($iterator as $controller)
		{
			if (substr($controller, -3) === 'php')
			{
				$controller    = str_replace(self::$controller_path, '', $controller);
				$controllers[] = substr($controller, 0, -4);
			}
		}

		$keys = array();
		foreach ($controllers as $controller)
		{
			$controller = '\Evil\Controller' . str_replace('/', '\\', $controller);
			if( method_exists($controller, 'dataKeyReads') )
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
	public static function triggerDataKeyInvalidation($invalidate = array())
	{
		if (file_exists('deplist.txt'))
		{
			$accessors = json_decode(file_get_contents('deplist.txt'), true);
		}
		else
		{
			self::generateList();
			$accessors = json_decode(file_get_contents('deplist.txt'), true);
		}

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

		$m = new \Memcached();
		$m->addServer(self::$server_ip, self::$server_port);
		$m->setOption(\Memcached::OPT_NO_BLOCK, true);
		$m->setOption(\Memcached::OPT_TCP_NODELAY, true);

		// TODO: Consider using a multi set for the keys with an expiration date in the past (or a second from now)
		$keys = array_unique($keys);
		foreach ($keys as $key)
		{
			$template = self::$namespace . $key;
			$m->delete($key);
		}
	}

	/**
	 * CacheTracker::generateList()
	 * Generate the dependency list of controllers -> data keys.
	 *
	 * @return void
	 */
	public static function generateList()
	{
		$accessors = json_encode(self::getDataKeysAccessors());
		file_put_contents('deplist.txt', $accessors);
	}

	/**
	 * CacheTracker::storePage()
	 * Stores the provided content in memcached.
	 *
	 * @param string $content The output content to cache.
	 * @return void
	 */
	public static function storePage($content)
	{
		if (!self::$cache || $_SERVER['REQUEST_METHOD'] !== 'GET')
			return;

		if ( empty(self::$namespace) )
			throw new CoreException('No cache namespace is defined.');

		if ( empty(self::$server_ip) || empty(self::$server_port) )
			throw new CoreException('No memcached server defined.');

		$key = self::$namespace . $_SERVER['REQUEST_URI'];

		$m = new \Memcached();
		$m->addServer(self::$server_ip, self::$server_port);
		$m->setOption(\Memcached::OPT_COMPRESSION, false);
		$m->setOption(\Memcached::OPT_NO_BLOCK, true);
		$m->setOption(\Memcached::OPT_TCP_NODELAY, true);

		$m->set($key, $content);
	}

	/**
	 * CacheTracker::setNamespace()
	 * Sets the namespace value prepended to all cache keys.
	 *
	 * @param string $namespace The namespace value to prepend to all cache keys.
	 * @return void
	 */
	public static function setNamespace($namespace)
	{
		if ( empty($namespace) )
			throw new CoreException('Trying to define invalid namespace.');

		self::$namespace = $namespace;
	}

	/**
	 * CacheTracker::setCaching()
	 * Set whether to cache the page or not.
	 *
	 * @param bool $cache Whether to cache the page or not.
	 * @return void
	 */
	public static function setCaching($cache)
	{
		if ( !is_bool($cache) )
			throw new CoreException('Invalid cache value, must be true or false.');

		self::$cache = $cache;
	}

	/**
	 * CacheTracker::setServer()
	 * Set whether to cache the page or not.
	 *
	 * @param integer $ip The IP of the memcached server.
	 * @param integer $port The port of the memcached server.
	 * @return void
	 */
	public static function setServer($ip, $port)
	{
		if ( !filter_var($ip, FILTER_VALIDATE_IP) )
			throw new CoreException('Server IP is not a valid IP.');

		if ( !is_integer($port) || $port < 1024 || $port > 65535  )
			throw new CoreException('Server port is not a valid port.');

		self::$server_ip   = $ip;
		self::$server_port = $port;
	}
}