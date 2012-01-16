<?php
namespace Evil\Core;

/**
 * Config
 * A configuration holder.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Config
{
	/**
	 * When set to false any path that only match the root index.php controller will result in a 404.
	 * When set to true the request will instead be passed to the root index.php
	 * controller, provided that it exists.
	 */
	public $user404     = false;

	/**
	 * True will result in core errors being output.
	 */
	public $development = true;

	/**
	 * The controller loader to use, currently CacheController and ProfileController exist.
	 */
	public $loader      = 'Evil\Core\CacheController';

	/**
	 * Will use the route cache file to find correct controller, rather than traverse the file system.
	 */
	public $cache_route = false;

	/**
	 * Whether to cache the page or not.
	 * It's recommended to keep false while developing.
	 * Can also be used to disable caching of specific pages which should never be cached.
	 *
	 * @var bool
	 */
	public $cache_content = false;

	/**
	 * Value prepended to all cache keys to avoid clashes with other
	 * applications using same memcached store.
	 *
	 * @var string
	 */
	public $cache_namespace = 'foobar';

	/**
	 * IP of memcached server to store pages in.
	 *
	 * @var string
	 */
	public $cache_server_ip = '127.0.0.1';

	/**
	 * Port used to communicate with memcached server to store pages in.
	 *
	 * @var int
	 */
	public $cache_server_port = 11211;
}