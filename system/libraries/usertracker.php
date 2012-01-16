<?php
namespace Evil\Library;

/**
 * UserTracker
 * Provides various methods for tracking a users movement through a site.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class UserTracker
{
	protected $table_name = 'usertracker';

	/**
	 * UserTracker::__construct()
	 * Loads required libraries.
	 *
	 * @param Controller $controller The framework controller.
	 * @param Arguments $arguments The framework arguments object.
	 * @return void.
	 */
	public function __construct($controller, $arguments)
	{
		$table_name = $arguments->get( array('Table Name', 1) );

		if ( !empty($table_name) && is_string($table_name) )
			$this->table_name = $table_name;

		$this->sql = $arguments->get( array('Database', 2) );

		if ( !($this->sql instanceof \Evil\Library\SQL) )
			$this->sql = $controller->loadLibrary('Database');

		$this->session = $arguments->get( array('Session', 3) );

		if ( !($this->session instanceof \Evil\Library\Session) )
			$this->session = $controller->loadLibrary('Session');

		if ((int)$arguments->get(0) > 0)
			$this->doRaffle($arguments->get(0));
	}

	/**
	 * UserTracker::doRaffle()
	 * Checks whether or not a user should be tracked.
	 *
	 * @param int $tickets The amount of tickets in the raffle, lower values equals higher chance.
	 * @return bool True if user hit the jackpot, false otherwise.
	 */
	public function doRaffle($tickets)
	{
		if ( isset($this->session->tracked) )
			return $this->session->tracked;

		if (mt_rand(0, (int)$tickets) === 0)
			return $this->session->tracked = true;
		else
			return $this->session->tracked = false;
	}

	/**
	 * UserTracker::trashUser()
	 * Trashes the tracking of the user.
	 *
	 * @return bool Always false.
	 */
	public function trashUser()
	{
		return $this->session->tracked = false;
	}

	/**
	 * UserTracker::track()
	 * Track the user as (s)he moves through the site.
	 *
	 * @param bool $forced Force the user to be tracked.
	 * @return bool Whether the user navigation movement was tracked or not.
	 */
	public function track($forced = false)
	{
		if (!$forced && !$this->session->tracked)
			return false;

		$info = $this->getState();

		// User tracking might have been trashed due to insufficient info.
		if (!$this->session->tracked)
			return false;

		$this->saveState(     $info);
		$this->saveToDatabase($info);

		return true;
	}

	/**
	 * UserTracker::getState()
	 * Gets the users state in the website.
	 *
	 * @return array The current user state in the path navigation. (previous/current page)
	 */
	private function getState()
	{
		return array(
			'previous_page' => $this->getPreviousPage(),
			'current_page'  => $this->getCurrentPage()
		);
	}

	/**
	 * UserTracker::saveToDatabase()
	 * Saves the state to the database.
	 *
	 * @param array $info Info array with user state.
	 * @return void
	 */
	private function saveToDatabase($info)
	{
		if ( !isset($this->session->tracker_id) )
			$this->session->tracker_id = $this->crc32( uniqid(mt_rand(), true) );

		$statement = '
		INSERT INTO
			`' . $this->sql->prefix . $this->table_name . '`
			(`session`, `previous_page`, `current_page`)
		VALUES(
			'  . $this->sql->escape($this->session->tracker_id) . ',
			"' . $this->sql->escape($info['previous_page']) . '",
			"' . $this->sql->escape($info['current_page'])  . '"
		)';

		$this->sql->execute($statement);
	}

	/**
	 * UserTracker::saveState()
	 * Saves the users current state in the site.
	 *
	 * @param array $info Info array with user state.
	 * @return void
	 */
	private function saveState($info)
	{
		$this->session->previous_page = $info['current_page'];
	}

	/**
	 * UserTracker::getPreviousPage()
	 * Gets the previous page the user was on.
	 *
	 * @return bool|string previous page or false.
	 */
	private function getPreviousPage()
	{
		if ( isset($this->session->previous_page) )
			return $this->session->previous_page;
		else if ( isset($_SERVER['HTTP_REFERER']) )
			return $_SERVER['HTTP_REFERER'];
		else if ( !isset($this->session->tracker_id) ) // Because if tracker_id isn't set then it's the first request.
			return 'Start';
		else
			return $this->trashUser();
	}

	/**
	 * UserTracker::getCurrentPage()
	 * Gets the current page the user is on.
	 *
	 * @return bool|string current page or false.
	 */
	private function getCurrentPage()
	{
		if ( isset($_SERVER['REQUEST_URI']) )
			return $_SERVER['REQUEST_URI'];
		else
			return $this->trashUser();
	}

	/**
	 * UserTracker::crc32()
	 * Calculates a true crc32 checksum.
	 *
	 * @param mixed $string
	 * @return int The crc32 checksum.
	 */
	private function crc32($string)
	{
		return sprintf('%u', crc32($string));
	}
}