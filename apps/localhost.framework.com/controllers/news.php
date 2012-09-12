<?php
namespace Evil\Controllers;

/**
 * News
 * Sample controller which provides a very basic example of full page caching.
 * The controller defines a few data keys and URIs they each invalidate.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class News implements \Evil\Core\ControllerCacheable
{
	use \Evil\Core\FrameworkCommons;

	/**
	 * __construct()
	 *
	 * @param CacheTracker $cache_tracker The cache tracker object.
	 * @param string       $app_path      The application to use in paths.
	 * @param Arguments    $arguments     Container for URI parts.
	 * @return void
	 */
	public function __construct($cache_tracker, $app_path, $arguments)
	{
		include $this->loadInclude('Config', $app_path);
		include $this->loadInclude('Common', $app_path);

		$this->news = new \Evil\Libraries\CacheTest\News($this->database, $cache_tracker);

		if ( !empty($_POST) )
		{
			if ($arguments->get(0) === 'comment')
				$this->postComment($_POST);
			else
				$this->postNews($_POST);
		}

		$news_id = $arguments->get(0);

		if ( empty($news_id) || !is_numeric($news_id) )
			$this->getAllNews();
		else
			$this->getNewsPosT($news_id);
	}

	/**
	 * getAllNews()
	 * Gets news from library and displays the news template.
	 *
	 * @return void
	 */
	public function getAllNews()
	{
		$this->template->news = $this->news->getNews();
		$this->template->display('News');
	}

	/**
	 * getNewsPost()
	 * Get a specific news post and its comments from library
	 * and display newspost template.
	 *
	 * @param integer $news_id ID of news post to fetch.
	 * @return void
	 */
	public function getNewsPost($news_id)
	{
		$this->template->news_post = $this->news->getNews($news_id);
		$this->template->comments  = $this->getComments($news_id);

		$this->template->display('NewsPost');
	}

	/**
	 * getComments()
	 * Get the comments associated with a news post.
	 *
	 * @param integer $news_id ID of news post to fetch.
	 * @return array The news post.
	 */
	public function getComments($news_id)
	{
		return $this->news->getComments($news_id);
	}

	/**
	 * postNews()
	 *
	 * @param array $data The POST data.
	 * @return void
	 */
	public function postNews($data)
	{
		if ( empty($data['title']) || empty($data['content']) )
			return false;

		$this->news->postNews($data['title'], $data['content']);
		header('Location: /news/');
	}

	/**
	 * postComment()
	 *
	 * @param array $data The POST data.
	 * @return void
	 */
	public function postComment($data)
	{
		if ( empty($data['id']) || empty($data['content']) )
			return false;

		$this->news->postComment($data['id'], $data['content']);
		header('Location: /news/' . $data['id'] . '/');
	}

	/**
	 * News::dataKeyReads()
	 * Defines an array of the data keys a controller reads from.
	 *
	 * @return array Array of data keys a controller reads from.
	 */
	public static function dataKeyReads()
	{
		return array('news', 'comment');
	}

	/**
	 * News::dataKeyInvalidats()
	 * Defines the cache entries a certain key and payload invalidates.
	 *
	 * @return array The cache keys to invalidate.
	 */
	public static function dataKeyInvalidates($key, $payload)
	{
		$invalidate = array();
		switch($key)
		{
			case 'news':
				$invalidate[] = '/news/';
				break;
			case 'comment':
				$invalidate[] = '/news/' . $payload . '/';
				break;
		}

		return $invalidate;
	}
}