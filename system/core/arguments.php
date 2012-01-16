<?php
namespace Evil\Core;

/**
 * Arguments Container
 * Provides methods for handling arguments converted from the URI path.
 * Can be used to contain custom named and manually inserted arguments.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Arguments
{
	private $arguments;

	/**
	 * Arguments::__construct()
	 * Configures the arguments array.
	 *
	 * @param string|array $arguments Arguments to work with.
	 * @return void
	 */
	public function __construct($arguments = null)
	{
		$this->arguments = is_array($arguments) ? $arguments : array($arguments);
	}

	/**
	 * Arguments::maximum()
	 * Checks if there are more than the expected amount of arguments present.
	 *
	 * @param mixed $amount Maximum amount of arguments expected.
	 * @return bool True if amount of arguments is within the maximum.
	 */
	public function maximum($amount)
	{
		return count($this->arguments) <= (int)$amount;
	}

	/**
	 * Arguments::minimum()
	 * Checks if there are less than the expected amount of arguments present.
	 *
	 * @param mixed $amount Minimum amount of arguments expected.
	 * @return bool True if amount of arguments is above the minimum.
	 */
	public function minimum($amount)
	{
		return count($this->arguments) >= (int)$amount;
	}

	/**
	 * Arguments::exactly()
	 * Checks if the amount of arguments present are exactly the expected amount.
	 *
	 * @param mixed $amount Exact amount of arguments expected.
	 * @return bool True if amount of arguments is as expected.
	 */
	public function exactly($amount)
	{
		return count($this->arguments) === (int)$amount;
	}

	/**
	 * Arguments::get()
	 * Fetches the argument with index $var or null.
	 *
	 * @param mixed $index Index to return.
	 * @return mixed Variable if found, otherwise null.
	 */
	public function get($index)
	{
		return (isset($this->arguments[$index])) ? $this->arguments[$index] : null;
	}

	/**
	 * Arguments::slice()
	 * Get a slice of the arguments from offset to index.
	 *
	 * @param mixed $index The index to cut off at.
	 * @return array of arguments limited by $index.
	 */
	public function slice($offset = 0, $length = null)
	{
		return array_slice($this->arguments, $offset, $length);
	}

	/**
	 * Arguments::debug()
	 * var_dump the arguments.
	 *
	 * @return void
	 */
	public function debug()
	{
		var_dump($this->arguments);
	}

	/**
	 * Arguments::all()
	 * Returns all the arguments.
	 *
	 * @return array All the arguments.
	 */
	public function all()
	{
		return $this->arguments;
	}

	/**
	 * Arguments::last()
	 * Returns the last arguments.
	 *
	 * @return mixed The last argument.
	 */
	public function last()
	{
		return end($this->arguments);
	}

	/**
	 * Arguments::insert()
	 * Insert an element to the beginning/end of the array
	 *
	 * @return void
	 */
	public function insert($value, $beginning = true)
	{
		if ($beginning)
			$this->arguments = array_merge(array($value), $this->arguments);
		else
			$this->arguments = array_merge($this->arguments, array($value));
	}

	/**
	 * Arguments::assign()
	 * Insert an element as a key/value pair.
	 * @return
	 */
	public function assign($key, $value)
	{
		$this->arguments[$key] = $value;
	}

	/**
	 * Arguments::__get()
	 * Fetches the argument with index $var or null.
	 *
	 * @param mixed $var Index to return.
	 * @return mixed Variable if found, otherwise null.
	 */
	public function __get($var)
	{
		return isset($this->arguments[$var]) ? $this->arguments[$var] : null;
	}

	/**
	 * Arguments::__isset()
	 * True if variable is defined, false if not.
	 *
	 * @param mixed $var Index to check if is set.
	 * @return bool
	 */
	public function __isset($var)
	{
		return isset($this->arguments[$var]);
	}

	/**
	 * Arguments::__toString()
	 * var_dump the arguments.
	 *
	 * @return string
	 */
	public function __toString()
	{
		// Same as var_dump($this->arguments); return '';
		return (string)var_dump($this->arguments);
	}

	/**
	 * Arguments::count()
	 * returns number of arguments.
	 *
	 * @return int|bool false on empty array, otherwise count from 1.
	 */
	public function count()
	{
		return count($this->arguments);
	}
}