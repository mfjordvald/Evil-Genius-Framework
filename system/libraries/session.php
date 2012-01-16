<?php
namespace Evil\Library;

/**
 * Session
 * Provides dual session layers, page-sessions and actual session data.
 * This abstraction layer makes sense since it allows PHP to write all sessions to disk at the same time.
 * Further, a segmentation fault will never result in sessions being partly one request and partly a previous request.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Session
{
	private $data  = array();

	// Token that must be known for attackers to replicate the integrity data.
	private $token = 'h0ts4ucep!cket';

	/**
	 * Session::__construct()
	 * Initalizes session and updates fingerprint.
	 *
	 * @param Controller $controller The framework controller.
	 * @param Arguments $arguments The framework arguments object.
	 * @return void
	 */
	public function __construct($controller, $arguments)
	{
		session_start();

		// If this user has no session data then regenerate the ID.
		if ( empty($_SESSION['initiated']) )
		{
	    	$this->regenerate();
	    	$this->data['initiated'] = true;
		}

		$token = $arguments->get( array('Token', 0) );

		if ( !empty($token) && is_string($token) )
			$this->token = $token;

		$this->updateFingerprint();
	}

	/**
	 * Session::updateFingerprint()
	 * Updates fingerprint for integrity checks.
	 * Realistically you can only use very little data here.
	 * IP might change so using that for fingerprinting can significantly annoy users.
	 *
	 * @return null
	 */
	protected function updateFingerprint()
	{
		$user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';

		// No need for the exact user agent, a hash is enough.
		$this->set('UA', $this->hash($user_agent . $this->token));
	}

	/**
	 * Session::checkIntegrity()
	 * Checks the integrity of the session.
	 *
	 * @return bool
	 */
	public function checkIntegrity()
	{
		if ( !empty($_SESSION['UA']) && $this->get('UA') != $_SESSION['UA'])
			return false; // Compromised.
		else
			return true;  // Valid.
	}

	/**
	 * Session::get()
	 * Gets session data from page session data or global session data, in that order.
	 * @param mixed $name The session identifier to fetch.
	 *
	 * @return mixed The session data requested.
	 */
	public function get($name)
	{
		if ( isset($this->data[$name]) )
			return $this->data[$name];
		else if ( isset($_SESSION[$name]) )
			return $_SESSION[$name];
		else
			return null;
	}

	/**
	 * Session::set()
	 * Sets page session data.
	 *
	 * @param string $name The session identifier to save as.
	 * @param mixed $value The value to save in the session.
	 * @return null
	 */
	public function set($name, $value)
	{
		$this->data[$name] = $value;
	}

	/**
	 * Session::delete()
	 * Deletes page session data.
	 *
	 * @param string $name The session identifier to save as.
	 * @return null
	 */
	public function delete($name)
	{
		unset($_SESSION[$name]);
		unset($this->data[$name]);
	}

	/**
	 * Session::save()
	 * Commits the page session data to the session handler.
	 *
	 * @return null
	 */
	public function save()
	{
		if ( empty($this->data) )
  			return;

		foreach ($this->data as $name => $value)
		{
			$_SESSION[$name] = $value;
		}
	}

	/**
	 * Session::discard()
	 * Discards the session data.
	 *
	 * @param bool $hard Discard all data, not just page session data.
	 * @return null
	 */
	public function discard($hard = false)
	{
		if ($hard)
			session_destroy();

		$this->data = array();
	}

	/**
	 * Session::regenerate()
	 * Regenerates the session ID.
	 *
	 * @return null
	 */
	public function regenerate()
	{
		session_regenerate_id();
	}

	/**
	 * Session::__destruct()
	 * Commits the page session data to the session handler on class shutdown.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->save();
	}

	/**
	 * Session::__get()
	 * Magic method wrapper for get()
	 *
	 * @param mixed $name The session identifier to fetch.
	 * @return mixed The session data requested.
	 */
	public function __get($name)
	{
		$this->get($name);
	}

	/**
	 * Session::__set()
	 * Magic method wrapper for set()
	 *
	 * @param string $name The session identifier to save as.
	 * @param mixed $value The value to save in the session.
	 * @return null
	 */
	public function __set($name, $value)
	{
		$this->set($name, $value);
	}

	/**
	 * Session::__isset()
	 * Gets session data from page session data or global session data, in that order. (Used with isset() and empty())
	 *
	 * @param string $name The session identifier to save as.
	 * @return mixed The session data requested.
	 */
	function __isset($name)
	{
		return $this->get($name);
	}

	/**
	 * Session::__unset()
	 * Magic method wrapper for delete()
	 *
	 * @param string $name The session identifier to remove.
	 * @return void
	 */
	function __unset($name)
	{
		$this->delete($name);
	}

	/**
	 * Session::hash()
	 * Calculates a true crc32 checksum.
	 *
	 * @param mixed $string
	 * @return int The crc32 checksum.
	 */
	private function hash($string)
	{
		return sprintf('%u', crc32($string));
	}
}