<?php
namespace Evil\Library;

/**
 * Template
 * Provides non-parsing template support.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Template
{
	/**
	 * Template::__construct()
	 *
	 * @param Controller $controller The framework controller.
	 * @param Arguments $arguments The framework arguments object.
	 * @return void
	 */
	public function __construct($controller, $arguments)
	{
		$template = $arguments->get(array('Template', 0));

		if ( empty($template) || !is_string($template) )
			$template = 'default';

		$this->setTemplate($template, $arguments->get('Application'));
	}

	/**
	 * Template::addHook()
	 * Add a pre-display hook to the template.
	 *
	 * @param Closure $function The function to execute.
	 * @return void
	 */
	public function addHook($function)
	{
		$this->_hooks[] = $function;
	}

	/**
	 * Template::setTemplate()
	 * Sets the current working template directory.
	 *
	 * @param string $template The current working template directory.
	 * @param string $app_path The application loaded.
	 * @return void
	 */
	public function setTemplate($template, $app_path)
	{
		if ( strpos($template, '../') )
			throw new TemplateException('Bad template name. May not contain "../"');

		if ( !file_exists('apps/' . $app_path . '/views/' . $template . '/') )
			throw new TemplateException('Missing template directory, please ensure the template exists.');

		// We have the template, set a few paths.
		$this->template_path = 'apps/' . $app_path . '/views/' . $template . '/';
		$this->media_path    = '/' . $this->template_path . 'media/';
		$this->image_path    = '/' . $this->template_path . 'media/images/';
		$this->style_path    = '/' . $this->template_path . 'media/styles/';
		$this->script_path   = '/' . $this->template_path . 'media/scripts/';
	}

	/**
	 * Template::display()
	 * Display the template using an output buffer.
	 *
	 * @param string $_tpl The template file to display.
	 * @param bool $_return Whether to echo or return to output buffer.
	 * @return void|string
	 */
	public function display($_tpl, $_return = false)
	{
		if ( !empty($this->_hooks) )
		{
			foreach($this->_hooks as $_hook)
			{
				$_hook($this);
			}
		}

		ob_start();

		include $this->template_path . strtolower($_tpl) . '.tpl.php';

		if ($_return)
			return ob_get_flush();
		else
			ob_end_flush();
	}

	/**
	 * Template::__get()
	 * Fetches the argument with index $var or null.
	 *
	 * @param  string $var Index to return.
	 * @return mixed
	 */
	public function __get($var)
	{
		// Will error if not set. This is desirable for logging purposes.
		return $this->$var;
	}

	/**
	 * Template::setMeta()
	 * Set the meta data variables.
	 *
	 * @param string $title The meta title tag.
	 * @param string $description The meta Description
	 * @param string $keywords The meta keywords tag.
	 * @return void
	 */
	public function setMeta($title, $description = '', $keywords = '')
	{
		$this->meta_title       = $title;
		$this->meta_description = $description;
		$this->meta_keywords    = $keywords;
	}

	/**
	 * Template::exists()
	 * Checks whether a template file exits or not.
	 *
	 * @param string $tpl The template file to check.
	 * @return bool Whether or not the template file exists.
	 */
	public function exists($tpl)
	{
		if ( strpos($tpl, '../') )
			return false;

		return file_exists($this->template_path . strtolower($tpl) . '.tpl.php');
	}
}

/**
 * TemplateException
 * Exception class for templates.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class TemplateException extends \Exception
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