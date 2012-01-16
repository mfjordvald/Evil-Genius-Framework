<?php
namespace Evil\Library;

/**
 * Settings
 * Provides database based settings.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @author Peter Schofield
 * @copyright Evil Genius Media
 */
class Settings
{
	private $settings   = array();
	private $table_name = 'settings';

	/**
	 * Settings::__construct()
	 *
	 * @param Controller $controller The framework controller.
	 * @param Arguments $arguments The framework arguments object.
	 * @return void
	 */
	public function __construct($controller, $arguments)
	{
		$table_name = $arguments->get( array('Table Name', 0) );

		if ( !empty($table_name) && is_string($table_name) )
			$this->table_name = $table_name;

		$this->sql = $arguments->get( array('Database', 1) );

		if ( !($this->sql instanceof \Evil\Library\SQL) )
			$this->sql = $controller->loadLibrary('Database');

		try {
			$this->cache = $arguments->get( array('Cache', 2) );

			if ( !($this->cache instanceof \Evil\Library\Cache\Cache) )
				$this->cache = $controller->loadLibrary('Cache');

		}
		catch(\Evil\Core\CoreException $e)
		{
			$this->cache = false;
		}

		$this->settings = $this->readSettings();
	}

	/**
	 * Settings::readSettings()
	 * Read the settings.
	 *
	 * @return array An array of settings.
	 */
	private function readSettings()
	{
		$statement = '
		SELECT
			`setting`,
			`value`
		FROM
			`' . $this->sql->prefix . $this->table_name . '`';

		if ($this->cache)
		{
			$settings = $this->cache->fetch('site_settings');

			if ( is_null($settings) )
			{
				$settings = $this->sql->fetch_value_pair($statement);
				$this->cache->add('site_settings', $settings, 600);
			}
		}
		else
		{
			$settings = $this->sql->fetch_value_pair($statement);
		}

		return $settings;
	}

	/**
	 * Settings::get()
	 *
	 * @param string $setting The setting identifier.
	 * @return string The setting value.
	 */
	public function get($setting = '')
	{
		if ( !empty($setting) )
			return isset($this->settings[$setting]) ? $this->settings[$setting] : null;

		return $this->settings;
	}

	/**
	 * Settings::set()
	 * Set a settings value.
	 *
	 * @param string $setting The setting identifier.
	 * @param string $value The setting value.
	 * @return bool
	 */
	public function set($setting, $value)
	{
		$statement = '
		UPDATE
			`' . $this->sql->prefix . $this->table_name . '`
		SET
			`value` = "' . $this->sql->escape($value) . '"
		WHERE
			`setting` = "' . $this->sql->escape($setting) . '"
		LIMIT 1';

		$this->sql->execute($statement);

		if ( !$this->sql->get_affectd_rows() )
		{
			$statement = '
			INSERT INTO
				`' . $this->sql->prefix . $this->table_name . '`
				(`setting`, `value`)
			VALUES
				("' . $this->sql->escape($setting) . '", "' . $this->sql->escape($value) . '")';
		}

		$this->setting[$setting] = $value;

		if ($this->cache)
			$this->cache->delete('site_settings');

		return true;
	}
}