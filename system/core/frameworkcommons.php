<?php
namespace Evil\Core;

/**
 * Base Controller
 * Provides basic controller functions.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
trait FrameworkCommons
{
	/**
	 * BaseController::loadInclude()
	 * Returns path to specified file from the include directory.
	 *
	 * @param string $include Name of file to include.
	 * @return void
	 */
	public function loadInclude($include)
	{
		return 'app/includes/' . strtolower($include) . '.php';
	}

	/**
	 * BaseController::redirect()
	 * Redirect to the same file keeping only $number amount of arguments.
	 *
	 * @todo Method should find the absolute URL and construct header properly.
	 * @param integer $number Number of arguments to include in the redirect.
	 * @return void
	 */
	public function redirect($number)
	{
		$arguments = $this->arguments->slice(0, $number);

		// Class name might be something we don't want to show in the URL.
		$class = str_replace('Index', '', $this->class);

		if ( !headers_sent() )
		{
			header('HTTP/1.1 301 Moved Permanently');

			if ( empty($arguments) )
				header('Location: /' . $class . '/' . implode('/', $arguments));
			else
				header('Location: /' . $class . '/' . implode('/', $arguments) . '/');
		}

		die('Should have redirected to /' . $class . '/' . implode('/', $arguments) );
	}

	/**
	 * BaseController::libraryExists()
	 * Checks whether or not the specified library is present in the libraries dir.
	 *
	 * @param string Name of the library to check exists.
	 * @return bool True if specified library exists, false otherwise
	 */
	public function libraryExists($lib)
	{
		return file_exists('app/libraries/' . strtolower($lib) . '.php') ||
		       file_exists('system/libraries/' . strtolower($lib) . '.php');
	}
}