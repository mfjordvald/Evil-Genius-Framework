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
	 * @param string $module Module the class belongs to.
	 * @param string $class Class to instantialize.
	 * @param Arguments $arguments Arguments object.
	 * @return
	 */
	abstract public function __construct($class, Arguments $arguments, $initialize = null);

	/**
	 * Controller::loadLibrary()
	 * Factory for libraries.
	 *
	 * @param string|array $library string or an array of names of libraries to load.
	 * @param mixed $arguments Arguments to pass to the library.
	 * @param string $arguments Arguments to pass to the library.
	 * @return Object Object of the specified library.
	 */
	abstract public function loadLibrary($library, $arguments = '', $identifier = '');


	/**
	 * Controller::loadInclude()
	 * Returns path to specified file from the include directory.
	 *
	 * @param string $include Name of file to include.
	 * @example include $controller->loadInclude('Common');
	 * @return void
	 */
	abstract public function loadInclude($include);

	/**
	 * Controller::redirect()
	 * Redirect to the same file keeping only $number amount of arguments.
	 *
	 * @param integer $number Number of arguments to include in the redirect.
	 * @return void
	 */
	abstract public function redirect($number);

	/**
	 * Initialize::autoLoadLibrary()
	 * Auto load undefined library classes.
	 *
	 * @param string $class Name of class to load.
	 * @return void
	 */
	abstract public function autoLoadLibrary($class);

	/**
	 * Initialize::autoLoadController()
	 * Auto load undefined controller classes.
	 *
	 * @param string $class Name of class to load.
	 * @return void
	 */
	abstract public function autoLoadController($class);
}