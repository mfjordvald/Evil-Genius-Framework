<?php
namespace Evil\Library;

/**
 * RSSGenerator
 * Helps generating RSS feeds.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 * @todo Needs to be redone.
 */
class RSSGenerator
{
	private $rss_info;
	private $rss_items;

	/**
	 * RSSGenerator::addInfo()
	 *
	 * @param string $var
	 * @param string $info
	 * @return void
	 */
	public function addInfo($var, $info)
	{
		$this->rss_info[$var] = htmlentities($info);
	}

	/**
	 * RSSGenerator::addItem()
	 *
	 * @param array $array
	 * @return void
	 */
	public function addItem(array $array)
	{
		$i = count($this->rss_items);
		foreach($array as $item => $value)
		{
			$this->rss_items[$i][$item] = htmlentities($value);
		}
	}

	/**
	 * RSSGenerator::saveFile()
	 *
	 * @param string $file
	 * @return
	 */
	public function saveFile($file)
	{
		$output = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<rss version=\"2.0\">\n\t<channel>\n";

		// Add channel info.
		foreach ($this->rss_info as $key => $value)
		{
			$output .= "\t\t<" . $key . ">" . $value . "</" . $key . ">\n";
		}

		// Add each item.
		foreach ($this->rss_items as $item)
		{
			$output .= "\t\t<item>\n";
			foreach ($item as $key => $value)
			{
				$output .= "\t\t\t<" . $key . ">" . $value . "</" . $key . ">\n";
			}
			$output .= "\t\t</item>\n";
		}

		$output .= "\t</channel>\n</rss>";

		file_put_contents(str_replace(' ', '_', strtolower($file)), $output);
	}
}