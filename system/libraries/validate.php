<?php
namespace Evil\Library;

/**
 * Validate
 * Provides various methods to validate text against a set of rules.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Validate
{
	private $data;

	/**
	 * Validate::__construct()
	 * Loads required libraries.
	 *
	 * @param Controller $controller The framework controller.
	 * @param Arguments $arguments The framework arguments object.
	 * @return void
	 */
	public function __construct($controller, $arguments)
	{
		try {
			$this->sql = $arguments->get( array('Database', 0) );

			if ( !($this->sql instanceof \Evil\Library\SQL) )
				$this->sql = $controller->loadLibrary('Database');
		}
		catch(\Evil\Core\CoreException $e)
		{
			$this->sql = false;
		}
	}

	/**
	 * Validate::match()
	 * Checks if data matches a string.
	 *
	 * @param string $match The string to match against.
	 * @param string $data The data to validate.
	 * @param string $message A custom message for the exception.
	 * @return Validate $this Itself.
	 */
	public function match($match, $data = false, $message = false)
	{
		if ( !empty($data) )
			$this->data = $data;

		if ($data !== $match)
		{
			if (!$message)
				$message = 'The entered text did not match.';

			throw new ValidationException($message);
		}
	}

	/**
	 * Validate::differ)
	 * Checks if data differs from as tring.
	 *
	 * @param string $match The string to match against.
	 * @param string $data The data to validate.
	 * @param string $message A custom message for the exception.
	 * @return Validate $this Itself.
	 */
	public function differ($match, $data = false, $message = false)
	{
		if ( !empty($data) )
			$this->data = $data;

		if ($data === $match)
		{
			if (!$message)
				$message = 'The entered text did not differ.';

			throw new ValidationException($message);
		}
	}


	/**
	 * Validate::alphanumeric()
	 * Checks if data is alpha-numeric only.
	 *
	 * @param string $data The data to validate.
	 * @param string $message A custom message for the exception.
	 * @return Validate $this Itself.
	 */
	public function alphanumeric($data = '', $message = false)
	{
		if ( !empty($data) )
			$this->data = $data;

		if ( !ctype_alnum($data) )
		{
			if (!$message)
				$message = 'Alphanumeric characters only.';

			throw new ValidationException($message);
		}

		return $this;
	}

	/**
	 * Validate::numeric()
	 * Checks if data is numeric only.
	 *
	 * @param string $data The data to validate.
	 * @param string $message A custom message for the exception.
	 * @return Validate $this Itself.
	 */
	public function numeric($data = '', $message = false)
	{
		if ( !empty($data) )
			$this->data = $data;

		if ( !is_numeric($data) )
		{
			if (!$message)
				$message = 'Numeric characters only.';

			throw new ValidationException('Numeric characters only.');
		}

		return $this;
	}

	/**
	 * Validate::min()
	 * Checks if data is at least $min characters long.
	 *
	 * @param int $min The minimum length.
	 * @param string $data The data to validate.
	 * @param string $message A custom message for the exception.
	 * @return Validate $this Itself.
	 */
	public function min($min, $data = '', $message = false)
	{
		if ( !empty($data) )
			$this->data = $data;

		if ( strlen($this->data) < $min)
		{
			if (!$message)
				$message = 'Input too small.';

			throw new ValidationException($message);
		}

		return $this;
	}

	/**
	 * Validate::max()
	 * Checks if data is at most $max characters long.
	 *
	 * @param int $max The maximum length.
	 * @param string $data The data to validate.
	 * @return Validate $this Itself.
	 */
	public function max($max, $data = '', $message = false)
	{
		if ( !empty($data) )
			$this->data = $data;

		if ( strlen($this->data) > $max)
		{
			if (!$message)
				$message = 'Input too long.';

			throw new ValidationException($message);
		}

		return $this;
	}

	/**
	 * Validate::custom()
	 * Performs a custom regular expression rule.
	 *
	 * @param string $regex The regex to validate gainst.
	 * @param string $data The data to validate.
	 * @param string $message A custom message for the exception.
	 * @return Validate $this Itself.
	 */
	public function custom($regex, $data = '', $message = false)
	{
		if ( !empty($data) )
			$this->data = $data;

		if ( !preg_match($regex, $this->data) )
		{
			if (!$message)
				$message = 'Custom rule failed validation.';

			throw new ValidationException($message);
		}

		return $this;
	}

	/**
	 * Validate::isUnique()
	 * Checks if data is unique a database table.
	 *
	 * @param string $column The colum to check.
	 * @param string $table The table to check.
	 * @param string $data The data to validate.
	 * @param string $message A custom message for the exception.
	 * @return Validate $this Itself.
	 */
	public function isUnique($column, $table, $data = '', $message = false)
	{
		if (!$this->sql)
			throw new ValidationException('Our database server appears to be down, please have patince while we fix this issue and sorry for the inconvenience.');

		if ( !empty($data) )
			$this->data = $data;

		$stmt = '
		SELECT
			1
		FROM
			`' . $this->sql->prefix . $table . '`
		WHERE
			`' . $column . '` = "' . $this->sql->escape($this->data) . '"
		LIMIT 1';

		list($valid) = $this->sql->fetch_numeric($stmt);

		if ($valid)
		{
			if (!$message)
				$message = $column . ' already exists in the ' . $table . ' table.';

			throw new ValidationException($message);
		}

		return $this;
	}

	/**
	 * Validate::isEmail()
	 * Checks if data is a valid email.
	 *
	 * @param string $data The data to validate.
	 * @param string $message A custom message for the exception.
	 * @return Validate $this Itself.
	 */
	public function isEmail($data = '', $message = false)
	{
		if ( !empty($data) )
			$this->data = $data;

		if (filter_var($this->data, FILTER_VALIDATE_EMAIL) === false)
		{
			if (!$message)
				$message = '"' . $this->data . '" is not a valid email address.';

			throw new ValidationException($message);
		}

		return $this;
	}
}

/**
 * ValidationException
 * Exception class for Validation.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class ValidationException extends \Exception
{
	/**
	 * __construct()
	 *
	 * @param string $message The exception message.
	 * @param integer $code The exception code.
	 * @return void
	 */
	public function __construct ($message = '', $code = 0)
	{
		parent::__construct($message, $code);
	}
}