<?php
namespace Evil\Library\SQL;

/**
 * MySQLimproved
 * Wrapper class to provide access to a MySQL database via MySQLimproved.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class MySQLimproved extends SQL
{
  	private $instance;
  	private $last_id;
  	private $num_rows;
  	private $affected_rows;

  	/**
  	 * MyMySQLimproved::__construct()
  	 *
  	 * @param Controller $controller The base controller.
  	 * @param Arguments $arguments An Arguments objecting holding configuration settings.
  	 * @return void
  	 */
  	public function __construct($controller, $arguments)
  	{
		if ( !$arguments->minimum(4) )
			throw new SQLException('Missing configuration information', 1);

  		$host     = $arguments->get(0);
  		$database = $arguments->get(1);
  		$username = $arguments->get(2);
  		$password = $arguments->get(3);
  		$this->debug = $arguments->get(4);

		$this->connect($host, $database, $username, $password);
	}

	/**
	 * MySQLimproved::connect()
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
		$this->instance = new \mysqli($host, $username, $password, $database);

		if ($this->instance->connect_error)
		{
			throw new SQLException('Couldn\'t connect to host ' . $host . ' by user ' . $username . ".\n" .
				$this->instance->connect_error, $this->instance->connect_errno);
		}

		return true;
	}

	/**
	 * MySQLimproved::execute()
	 * Executes the provided query statement.
	 *
	 * @param string $statement The query statement to execute.
	 * @return Resource
	 */
	public function execute($statement)
	{
  		if ( !$this->result = $this->instance->query($statement) )
		{
			throw new SQLException('Error in completing your request: <strong>' . $statement . "</strong>\n" .
				'Error: ' . $this->instance->error, $this->instance->errno);
		}

		if ( isset($this->instance->insert_id) )
			$this->last_id = $this->instance->insert_id;

		if ( isset($this->result->num_rows) )
			$this->num_rows = $this->result->num_rows;

		$this->affected_rows = $this->instance->affected_rows;

		$this->incrementQueries();
		return $this->result;
	}

	/**
	 * MySQLimproved::fetch_assoc()
	 * Fetches a single row as an associative array.
	 *
	 * @param string $statement The query statement to execute.
	 * @return array
	 */
	public function fetch_assoc($statement)
	{
		$result = $this->execute($statement);
		$return = $result->fetch_assoc();
		$result->close();

		return $return;
	}

	/**
	 * MySQLimproved::fetch_numeric()
	 * Returns a single row as a numeric array.
	 *
	 * @param string $statement The query statement to execute.
	 * @return array
	 */
	public function fetch_numeric($statement)
	{
		$result = $this->execute($statement);
		$return = $result->fetch_row();
		$result->close();

		return $return;
	}

	/**
	 * MySQLimproved::num_rows()
	 * Returns the number of rows fetched by a mysql query statement.
	 *
	 * @param string $statement The query statement to execute.
	 * @return int
	 */
	public function num_rows($statement)
	{
		$result = $this->execute($statement);
		$result->close();

		return $this->num_rows;
	}

	/**
	 * MySQLimproved::fetch_assoc_array()
	 * Returns all rows as an associative array of arrays.
	 *
	 * @param string $statement The query statement to execute.
	 * @return array
	 */
	public function fetch_assoc_array($statement)
	{
		$return = array();

		$result = $this->execute($statement);

		if ($result->num_rows != 0)
		{
			while ( $row = $result->fetch_assoc() )
				$return[] = $row;
		}
		else
		{
			$return = array();
		}

		$result->close();

		return $return;
	}

	/**
	 * MySQLimproved::fetch_assoc_array()
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

		if ($result->num_rows != 0)
		{
			while ( $row = $result->fetch_row() )
				$return[$row[0]] = $row[1];
		}
		else
		{
			$return = array();
		}

		$result->close();

		return $return;
	}

	/**
	 * MySQLimproved::insert_id()
	 * Returns the ID of the last inserted row.
	 *
	 * @return string The ID of the last inserted row.
	 */
	public function insert_id()
	{
		return $this->last_id;
	}

	/**
	 * MySQLimproved::get_affected_rows()
	 * Returns the number of rows affected by the last query.
	 *
	 * @return string The number of affected rows.
	 */
	public function get_affected_rows()
	{
		return $this->affected_rows;
	}

	/**
	 * MySQLimproved::escape()
	 *
	 * @param string $string The string to escape
	 * @return string The escaped string.
	 */
	public function escape($string, $special_characters = false)
	{
		if ($special_characters)
			$string = addcslashes($string, '_%');

		return $this->instance->real_escape_string($string);
	}
}