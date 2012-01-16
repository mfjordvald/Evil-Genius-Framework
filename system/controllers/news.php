<?php
namespace Evil\Controller;

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
	/**
	 * __construct()
	 *
	 * @param Controller $controller The base controller from which we handle framework calls.
	 * @param Arguments $arguments Container for URI parts.
	 * @return void
	 */
	public function __construct($controller, $arguments)
	{
		include $controller->loadInclude('Common');

		$this->news = $controller->loadLibrary('CacheTest/News', $this->sql);

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
		$this->controller->loadLibrary('Header')->redirect('/news/');
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
		$this->controller->loadLibrary('Header')->redirect('/news/' . $data['id'] . '/');
	}

	/**
	 * dataKeyReads()
	 * Defines the data keys this controller reads from.
	 *
	 * @return void
	 */
	public static function dataKeyReads()
	{
		return array('news', 'comment');
	}

	/**
	 * dataKeyInvalidates()
	 * Defines the data key to cache key relation.
	 *
	 * @param string $key The key that is being invalidated.
	 * @param string $payload An optional payload associated with the key which
	 * may help identify the proper cache key(s) to invalidate.
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