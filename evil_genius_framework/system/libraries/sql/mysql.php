<?php
namespace Evil\Library\SQL;

/**
 * MySQL
 * Wrapper class to provide access to a MySQL database.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class MySQL extends SQL
{
	private $connected = false;

	/**
	 * MySQL::__construct()
	 *
	 * @param Controller $controller The base controller.
	 * @param Arguments $arguments An Arguments objecting holding configuration settings.
	 * @return void
	 */
	public function __construct($controller, $arguments)
	{
		if ( !$arguments->minimum(4) )
			throw new SQLException('Missing configuration information');

  		$this->host     = $arguments->get(0);
  		$this->database = $arguments->get(1);
  		$this->username = $arguments->get(2);
  		$this->password = $arguments->get(3);
  		$this->debug    = $arguments->get(4);
	}

	/**
	 * MySQL::__destruct()
	 * Make sure we clean up when the object isn't being used any more.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		if ($this->connected)
			mysql_close($this->link);
	}

	/**
	 * MySQL::connect()
	 * Establishes a connection to the MySQL server
	 *
	 * @param string $host The host to connect to.
	 * @param string $database The database to select.
	 * @param string $username The username to authenticate with.
	 * @param string $password The password to authenticate with.
	 * @return bool
	 */
	protected function connect($host, $database, $username, $password)
	{
		if (!$this->link = mysql_connect($host, $username, $password))
		{
			throw new SQLException('Couldn\'t connect to host ' . $host . ' by user ' . $username . ".\n" .
				'Error ' . mysql_error(), mysql_errno());

			return false;
		}

		if(!mysql_select_db($database, $this->link))
		{
			throw new SQLException('Error while selecting ' . $database . ' database.' . "\n" .
				'Error ' . mysql_error(), mysql_errno());

			return false;
		}

		return true;
	}

	/**
	 * MySQL::execute()
	 * Executes the provided query statement.
	 *
	 * @param string $statement The query statement to execute.
	 * @return Resource
	 */
	public function execute($statement)
	{
		if (!$this->connected)
		{
			$this->connect($this->host, $this->database, $this->username, $this->password);
			$this->connected = true;
		}

		if( !$result = mysql_query($statement, $this->link) )
		{
			throw new SQLException('Error in completing your request: ' . $statement . "\n" .
				'Error ' . mysql_error(), mysql_errno());
		}

		$this->incrementQueries();
		return $result;
	}

	/**
	 * MySQL::fetch_assoc()
	 * Fetches a single row as an associative array.
	 *
	 * @param string $statement The query statement to execute.
	 * @return array
	 */
	public function fetch_assoc($statement)
	{
		$result = $this->execute($statement);
		$return = mysql_fetch_assoc($result);

		if ( is_resource($result) )
			mysql_free_result($result);

		return $return;
	}

	/**
	 * MySQL::fetch_numeric()
	 * Returns a single row as a numeric array.
	 *
	 * @param string $statement The query statement to execute.
	 * @return array
	 */
	public function fetch_numeric($statement)
	{
		$result = $this->execute($statement);
		$return = mysql_fetch_row($result);

		if ( is_resource($result) )
			mysql_free_result($result);

		return $return;
	}

	/**
	 * MySQL::num_rows()
	 * Returns the number of rows fetched by a mysql query statement.
	 *
	 * @param string $statement The query statement to execute.
	 * @return int
	 */
	public function num_rows($statement)
	{
		$result = $this->execute($statement);
		$return = mysql_num_rows($result);

		if ( is_resource($result) )
			mysql_free_result($result);

		return $return;
	}

	/**
	 * MySQL::fetch_assoc_array()
	 * Returns all rows as an associative array of arrays.
	 *
	 * @param string $statement The query statement to execute.
	 * @return array
	 */
	public function fetch_assoc_array($statement)
	{
		$return = array();

		// Get amount of columns to get
		$result = $this->execute($statement);

		if ( is_resource($result) )
		{
			while ( $row = mysql_fetch_assoc($result) )
			{
				$return[] = $row;
			}
		}
		else
		{
			$return = false;
		}

		if ( is_resource($result) )
			mysql_free_result($result);

		return $return;
	}

	/**
	 * MySQL::fetch_assoc_array()
	 * Returns all rows as an associative array with the first row as key and second row as value.
	 *
	 * @param string $statement The query statement to execute.
	 * @return array
	 */
	public function fetch_value_pair($statement)
	{
		$return = array();

		// Get amount of columns to get
		$result = $this->execute($statement);

		if ( is_resource($result) )
		{
			while ( $row = mysql_fetch_row($result) )
				$return[$row[0]] = $row[1];
		}
		else
		{
			$return = array();
		}

		if ( is_resource($result) )
			mysql_free_result($result);

		return $return;
	}

	/**
	 * MySQL::insert_id()
	 * Returns the ID of the last inserted row.
	 *
	 * @return string The ID of the last inserted row.
	 */
	public function insert_id()
	{
		return mysql_insert_id($this->link);
	}

	/**
	 * MySQL::get_affected_rows()
	 * Returns the number of rows affected by the last query.
	 *
	 * @return string The number of affected rows.
	 */
	public function get_affected_rows()
	{
		return mysql_affected_rows($this->link);
	}

	/**
	 * MySQL::escape()
	 *
	 * @param string $string The string to escape
	 * @return string The escaped string.
	 */
	public function escape($string, $special_characters = false)
	{
		if (!$this->connected)
		{
			$this->connect($this->host, $this->database, $this->username, $this->password);
			$this->connected = true;
		}

		if ($special_characters)
			$string = addcslashes($string, '_%');

		return mysql_real_escape_string($string);
	}
}