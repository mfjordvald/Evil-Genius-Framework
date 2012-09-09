<?php
namespace Evil\Libraries\SQL;

/**
 * SQL
 * Abstract class for extending.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
abstract class SQL
{
  	private $queries = 0;
  	public $prefix = '';

	/**
	 * SQL::getQueries()
	 * Returns the amount queries done.
	 *
	 * @return int
	 */
	public function getQueries()
	{
		return $this->queries;
	}

	/**
	 * SQL::incrementQueries()
	 * Increments the query counter.
	 *
	 * @return void
	 */
	public function incrementQueries()
	{
		$this->queries++;
	}

	/**
	 * SQL::connect()
	 * Establishes a connection to the SQL server
	 *
	 * @param string $host The host to connect to.
	 * @param string $database The database to select.
	 * @param string $username The username to authenticate with.
	 * @param string $password The password to authenticate with.
	 * @return bool
	 */
	abstract protected function connect($host, $database, $username, $password);

	/**
	 * SQL::execute()
	 * Executes the provided query statement.
	 *
	 * @param string $statement The query statement to execute.
	 * @return Resource
	 */
	abstract public function execute($statement);

	/**
	 * SQL::complete()
	 * Alias of SQL::execute()
	 *
	 * @param string $statement The query statement to execute.
	 * @return Resource
	 */
	public function complete($statement)
	{
		return $this->execute($statement);
	}

	/**
	 * SQL::fetch_assoc()
	 * Fetches a single row as an associative array.
	 *
	 * @param string $statement The query statement to execute.
	 * @return array
	 */
	abstract public function fetch_assoc($statement);

	/**
	 * SQL::fetch_numeric()
	 * Returns a single row as a numeric array.
	 *
	 * @param string $statement The query statement to execute.
	 * @return array
	 */
	abstract public function fetch_numeric($statement);

	/**
	 * SQL::num_rows()
	 * Returns the number of rows fetched by a mysql query statement.
	 *
	 * @param string $statement The query statement to execute.
	 * @return int
	 */
	abstract public function num_rows($statement);

	/**
	 * SQL::fetch_assoc_array()
	 * Returns all rows as an associative array of arrays.
	 *
	 * @param string $statement The query statement to execute.
	 * @return array
	 */
	abstract public function fetch_assoc_array($statement);

	/**
	 * SQL::fetch_assoc_array()
	 * Returns all rows as an associative array with the first row as key and second row as value.
	 *
	 * @param string $statement The query statement to execute.
	 * @return array
	 */
	abstract public function fetch_value_pair($statement);

	/**
	 * SQL::insert_id()
	 *
	 * @return id The ID of the last inserted row.
	 */
	abstract public function insert_id();

	/**
	 * SQL::escape()
	 *
	 * @param string $string The string to escape
	 * @return string The escaped string.
	 */
	abstract public function escape($string);
}

/**
 * SQLException
 * Exception class for SQL
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class SQLException extends \Exception
{
	public function __construct ($message = '', $code = 0)
	{
		parent::__construct($message, $code);
	}
}