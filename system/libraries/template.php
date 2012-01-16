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
		$template = $arguments->get(0);
		$this->setTemplate($template);
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
		$this->hooks[] = $function;
	}

	/**
	 * Template::setTemplate()
	 * Sets the current working template directory.
	 *
	 * @param string $template The current working template directory.
	 * @return void
	 */
	public function setTemplate($template)
	{
		if ( strpos($template, '../') )
			throw new TemplateException('Bad template name. May not contain "../"');

		if ( !file_exists('system/views/' . $template . '/') )
			throw new TemplateException('Missing template directory, please ensure the template exists.');

		// We have the template, set a few paths.
		$this->template_path = 'system/views/' . $template . '/';
		$this->media_path    = '/' . $this->template_path . 'media/';
		$this->image_path    = '/' . $this->template_path . 'media/images/';
		$this->style_path    = '/' . $this->template_path . 'media/styles/';
		$this->script_path   = '/' . $this->template_path . 'media/scripts/';
	}

	/**
	 * Template::display()
	 * Display the template using a output buffer.
	 *
	 * @param string $_tpl The template file to display.
	 * @return void
	 */
	public function display($_tpl, $_return = false)
	{
		if ( !empty($this->hooks) )
		{
			foreach($this->hooks as $hook)
				$hook($this);
		}

		ob_start();

		include $this->template_path . strtolower($_tpl) . '.tpl.php';
		$_output = ob_get_contents();

		ob_end_clean();

		if ($_return)
			return $_output;
		else
			echo $_output;
	}

	/**
	 * Template::__get()
	 * Fetches the argument with index $var or null.
	 *
	 * @param  string $var Index to return.
	 * @return mixed|string Variable if found, otherwise "UNINITIALIZED VARIABLE!".
	 */
	public function __get($var)
	{
		if ( isset($this->$var) )
		{
			return $this->$var;
		}
		else
		{
			//TODO: Log it.
			return 'UNINITIALIZED VARIABLE!';
		}
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
		$this->title       = $title;
		$this->description = $description;
		$this->keywords    = $keywords;
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
		//Todo: Log this.
		parent::__construct($message, $code);
	}
}