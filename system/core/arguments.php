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
	/**
	 * Arguments sent to the class.
	 *
	 * @var array
	 */
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
	 * @param mixed $index Index or indices of value(s) to return.
	 * @return mixed Variable if found, otherwise null.
	 */
	public function get($index)
	{
		if (is_null($index) || $index === '')
			return null;

		if ( !is_array($index) )
			return isset($this->arguments[$index]) ? $this->arguments[$index] : null;

		foreach($index as $key)
		{
			$value = isset($this->arguments[$key]) ? $this->arguments[$key] : null;
			if ( !is_null($value) )
				return $value;
		}

		return null;
	}

	/**
	 * Arguments::slice()
	 * Get a slice of the arguments from offset to index.
	 *
	 * @param mixed $offset The index to cut off at.
	 * @param int   $length How many elements to include.
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
	 * @param mixed $value     The value to insert.
	 * @param bool  $beginning Whether or not to insert value at beginning. (or end)
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
	 * Arguments::set()
	 * Insert an element as a key/value pair.
	 *
	 * @param string $key   Key to insert value under.
	 * @param mixed  $value Value to insert.
	 * @return void
	 */
	public function set($key, $value)
	{
		$this->arguments[$key] = $value;
	}

	/**
	 * Arguments::assign()
	 * Insert an element as a key/value pair.
	 *
	 * @deprecated Use set()
	 * @param string $key   Key to insert value under.
	 * @param mixed  $value Value to insert.
	 * @return void
	 */
	public function assign($key, $value)
	{
		$this->set($key, $value);
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
		return $this->get($var);
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
		return var_export($this->arguments, true);
	}

	/**
	 * Arguments::count()
	 * Returns number of arguments.
	 *
	 * @return int Number of arguments.
	 */
	public function count()
	{
		return count($this->arguments);
	}
}