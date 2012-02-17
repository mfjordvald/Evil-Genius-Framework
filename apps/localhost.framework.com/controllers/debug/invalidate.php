<?php
namespace Evil\Controller\Debug;
use \Evil\Core\CacheTracker;

/**
 * Invalidate
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Invalidate
{
	public function __construct($controller, $arguments)
	{
		$controller->cache = false;

		if ( !empty($_POST) )
		{
			$this->invalidateKey($_POST['key'], $_POST['payload']);
		}

		$this->showForm();
	}

	public function invalidateKey($key, $payload)
	{
		CacheTracker::triggerDataKeyInvalidation(array($key => $payload));
	}

	public function showForm()
	{
		?>
		<form method="post" enctype="application/x-www-form-urlencoded">
			Key: <input type="text" name="key" />
			Payload: <input type="text" name="payload" />
			<input type="submit" value="Invalidate" />
		</form>
		<?php
	}
}