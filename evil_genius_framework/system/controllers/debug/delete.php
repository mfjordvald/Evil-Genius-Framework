<?php
namespace Evil\Controller\Debug;

/**
 * Delete
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Delete
{
	public function __construct($controller, $arguments)
	{
		include $controller->loadInclude('Config');
		$controller->cache = false;

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