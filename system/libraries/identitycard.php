<?php
namespace Evil\Libraries;

/**
 * IdentityCard
 * Provides a way to create and read identity cards that can help identifying a server.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class IdentityCard
{
	public $path = '../identify.card';
	private $card = array();

	/**
	 * IdentityCard::__construct()
	 *
	 * @param bool $require Require that the identity card exist.
	 * @return void
	 */
	public function __construct($require = false)
	{
		if ($require)
			$this->checkExistance();
	}

	/**
	 * IdentityCard::checkExistance()
	 * Checks if the identity card exists and throws an exception if not.
	 *
	 * @return void
	 */
	private function checkExistance()
	{
		if ( !file_exists($this->path) )
			throw new \Exception('Identity card does not exist.');
	}

	/**
	 * IdentityCard::readCard()
	 * Attempts to read the identity card.
	 *
	 * @return string The identity card.
	 */
	public function readCard()
	{
		if ( file_exists($this->path) )
			return json_decode(file_get_contents($this->path), true);
	}

	/**
	 * IdentityCard::get()
	 * Gets a value from the identity card.
	 *
	 * @param string $key The key to fetch, if empty then fetch all.
	 * @return null|string|array The identity card value, if exists.
	 */
	public function get($key = '')
	{
		if ( !empty($key) )
			return isset($this->card[$key]) ? $this->card[$key] : null;

		return $this->card;
	}

	/**
	 * IdentityCard::set()
	 * Set an identity card value.
	 *
	 * @param string $value The value identifier.
	 * @param string $key The actual value.
	 * @return bool
	 */
	public function set($value, $key = '')
	{
		if ( !empty($key) )
			$this->card[$key] = $value;

		$this->card = $value;

		return true;
	}

	/**
	 * IdentityCard::writeCard()
	 * Write the identity card.
	 *
	 * @return void
	 */
	public function writeCard()
	{
		return file_put_contents($this->path, json_encode($this->card) );
	}
}