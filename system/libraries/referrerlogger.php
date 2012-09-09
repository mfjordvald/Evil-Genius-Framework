<?php
namespace Evil\Libraries;

/**
 * ReferrerLogger
 * Provides referrer logging.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class ReferrerLogger
{
	/**
	 * List of search engines to exclude and not log.
	 */
	protected $search_engines = array(
		'search.live',
		'search.yahoo',
		'google',
		'aolsearch.aol',
		'search.sweetim'
	);

	/**
	 * List of noise sites to exclude and not log.
	 */
	protected $noise_sites = array(
		'mail.live',
		'mail.yahoo',
		'mail.google',
		'translate.google',
		'localhost',
	);

	public $exclude_search_engines = true;
	public $exclude_noise_sites    = true;
	public $table_name             = 'referrer_log';

	/**
	 * RefererLogger::__construct()
	 *
	 * @param Controller $controller The framework controller.
	 * @return void
	 */
	public function __construct($controller)
	{
		$this->database = $controller->loadLibrary('Database');
	}

	/**
	 * RefererLogger::logReferrer()
	 * Logs the referrer data to the database.
	 *
	 * @param array $ignore Array of hostnames to not log.
	 * @return bool
	 */
	public function logReferrer($ignore)
	{
		$referrer = $this->getReferrer($ignore);

		if (!$referrer)
			return false;

		$statement = '
		INSERT IGNORE INTO `' . $this->database->prefix . $this->table_name . '`
			(`date`, `url`) VALUES(NOW(), "' . $this->database->escape($referrer) . '")
		ON DUPLICATE KEY UPDATE `times` = `times` + 1';

		$this->database->execute($statement);
		return true;
	}

	/**
	 * RefererLogger::getReferrer()
	 * Get and analyze the referrer to check if it should be logged.
	 *
	 * @param array $ignore Array of hostnames to not log.
	 * @return bool|string Either false or the hostname.
	 */
	protected function getReferrer($ignore = null)
	{
		if ( empty($_SERVER['HTTP_REFERER']) )
			return false;

		$host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);

		if ($this->exclude_search_engines)
		{
			if ($this->exclude($host, $this->search_engines))
				return false;
		}

		if ($this->exclude_noise_sites)
		{
			if ($this->exclude($host, $this->noise_sites))
				return false;
		}

		if ( !empty($ignore) && is_array($ignore) )
		{
			if ($this->exclude($host, $ignore))
				return false;
		}

		return $host;
	}

	/**
	 * RefererLogger::exclude()
	 * Helper method to check if a hostname is in an excluded list.
	 *
	 * @param string $host The hostname to check.
	 * @param array $excludes Array of hostnames excluded from logging.
	 * @return bool
	 */
	protected function exclude($host, $excludes)
	{
		foreach($excludes as $site)
		{
			if (strpos($host, $site) !== false)
				return true;
		}

		return false;
	}
}