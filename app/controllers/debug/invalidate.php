<?php
namespace Evil\Controllers\Debug;
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
	/**
	 * __construct()
	 *
	 * @param CacheTracker $cache_tracker The cache tracker object.
	 * @param Arguments    $arguments     Container for URI parts.
	 * @return void
	 */
	public function __construct($cache_tracker, $arguments)
	{
		$cache_tracker->setCaching(false);

		if ( !empty($_POST) )
			$this->invalidateKey($_POST['key'], $_POST['payload']);

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