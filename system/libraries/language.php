<?php
namespace Evil\Libraries;

/**
 * Language
 * Provides multi-language support.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 * @todo Rework.
 */
class Language
{
	/**
	 * Language::__construct()
	 *
	 * @param string $language The language to load.
	 * @return void
	 */
	public function __construct($language)
	{
		$this->setLocale($language);
	}

	/**
	 * Language::setLocale()
	 * Set the language.
	 *
	 * @param string $language The Language
	 * @return void
	 */
	public function setLocale($language)
	{
		// We'll want to reconsider the below statement.
		// We'll want to manually add all languages so we can verify no husky stuff is going on.
		switch ($language)
		{
			default:
				$locale = 'en_EN';
				break;
			case 'English':
				$locale = 'en_EN';
				break;
			case 'Danish':
				$locale = 'da_DK';
				break;
			case 'Dutch':
				$locale = 'nl_NL';
				break;
		}

		putenv('LC_ALL=' . $locale);
		setlocale(LC_ALL, $locale);
		bindtextdomain('messages', './locale');
		textdomain('messages');
	}

	/**
	 * Language::htmlentities()
	 * Provides UTF-8 entity conversion.
	 *
	 * @param string $string String to convert.
	 * @return string The converted string.
	 */
	public function htmlentities($string)
	{
		return htmlentities($string, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Language::getText()
	 * Gets a translated string
	 *
	 * @param string $string The string to translate.
	 * @return The translated string.
	 */
	public function getText($string)
	{
		//Todo: Check if gettext module is installed, if not then emulate it.
		if (false)
		{
			return $this->php_getText($string);
		}

		return gettext($string);
	}

	/**
	 * Language::ngetText()
	 * Gets a translated string for a possible plural word
	 *
	 * @param string $single The single form of the sentence.
	 * @param string $plural The plural form of the sentnece.
	 * @param int $number The number used to determine form.
	 * @return The translated string.
	 */
	public function ngetText($single, $plural, $number)
	{
		//Todo: Check if gettext module is installed, if not then emulate it.
		if (false)
		{
			return $this->php_ngetText($single, $plural, $number);
		}

		return sprintf( ngettext($single, $plural, $number), $number );
	}

	/**
	 * Language::getImage()
	 * Gets a translated image.
	 *
	 * @param mixed $name
	 * @return void
	 */
	public function getImage($name)
	{
		//Todo: Implement.
	}

	/**
	 * Language::linkify()
	 * Converts a [link] to a proper link.
	 *
	 * @param string $string The string to convert.
	 * @param string $url The url to link to.
	 * @param bool $external Whether to add rel="external" or not.
	 * @param bool $nofollow Whether to add rel="nofollow" or not.
	 * @return void
	 */
	public function linkify($string, $url, $external = false, $nofollow = false)
	{
		return preg_replace('|\[([^\]]*?)\]|', '<a href="' . $url . '" rel="' . ($external ? 'external ' : '') . ($nofollow ? 'nofollow' : '') . '">\1</a>', gettext($string));
	}

	/**
	 * Language::php_getText()
	 * Placeholder
	 *
	 * @param string $string
	 * @return string
	 */
	private function php_getText($string)
	{
		return $string;
	}

	/**
	 * Language::php_ngetText()
	 * Placeholder
	 *
	 * @param string $single
	 * @param string $plural
	 * @param int $number
	 * @return string
	 */
	private function php_ngetText($single, $plural, $number)
	{
		return $single;
	}
}