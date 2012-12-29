<?php
namespace Evil\Controllers\Debug;

/**
 * Delete
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Delete
{
	use \Evil\Core\FrameworkCommons;

	/**
	 * __construct()
	 *
	 * @param CacheTracker $cache_tracker The cache tracker object.
	 * @param Arguments    $arguments     Container for URI parts.
	 * @return void
	 */
	public function __construct($cache_tracker, $arguments)
	{
		include $this->loadInclude('Config');
		$cache_tracker->setCaching(false);

		if ( !empty($_POST) )
			$this->deleteKey($_POST['delete']);

		$this->showForm();
	}

	public function deleteKey($key)
	{
		$m = new \Memcached();
		$m->addServer($cache_ip, $cache_port);
		$m->setOption(\Memcached::OPT_NO_BLOCK, true);
		$m->setOption(\Memcached::OPT_TCP_NODELAY, true);

		$m->delete($key);
	}

	public function showForm()
	{
		?>
		<form method="post" enctype="application/x-www-form-urlencoded">
			Delete: <input type="text" name="delete" />
			<input type="submit" value="Delete" />
		</form>
		<?php
	}
}