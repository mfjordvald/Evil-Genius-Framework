<?php
namespace Evil\Core;

/**
 * Base Controller
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
abstract class Controller
{
	/**
	 * Controller::__construct()
	 *
	 * @param Config      $config      Object holding the configuration variables.
	 * @param string      $app_path    The application to use in paths.
	 * @param Application $application The bootstrapper class holding a few auto load methods.
	 * @return void
	 */
	abstract public function __construct($config, $app_path, $application = null);

	/**
	 * Controller::load()
	 *
	 * @param string    $class     Class to instantialize.
	 * @param Arguments $arguments Arguments object.
	 * @return void
	 */
	abstract public function load($class, Arguments $arguments);

	/**
	 * Controller::loadLibrary()
	 * Factory for libraries. Forces lazy loading.
	 *
	 * @param string|array $library    Names of libraries to load.
	 * @param mixed        $arguments  Arguments to pass to the library.
	 * @param string       $identifier The identifier for the library.
	 * @return Object Object of the specified library.
	 */
	abstract public function loadLibrary($library, $arguments = null, $identifier = null);

	/**
	 * Controller::loadInclude()
	 * Returns path to specified file from the include directory.
	 *
	 * @param string $include Name of file to include.
	 * @return void
	 */
	abstract public function loadInclude($include);

	/**
	 * Controller::autoLoadClasses()
	 * Auto load undefined classes.
	 *
	 * @param string $class Name of class to load.
	 * @return void
	 */
	abstract public function autoLoadClasses($class);

	/**
	 * Controller::redirect()
	 * Redirect to the same file keeping only $number amount of arguments.
	 *
	 * @param integer $number Number of arguments to include in the redirect.
	 * @return void
	 */
	abstract public function redirect($number);

	/**
	 * Controller::libraryExists()
	 * Checks whether or not the specified library is present in the libraries dir.
	 *
	 * @param string Name of the library to check exists.
	 * @return bool True if specified library exists, false otherwise
	 */
	abstract public function libraryExists($library);
}