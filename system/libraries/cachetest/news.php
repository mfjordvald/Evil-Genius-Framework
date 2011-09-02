<?php
namespace Evil\Library\CacheTest;
use \Evil\Core\CacheTracker;

/**
 * News
 * News API.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class News implements \Evil\Core\LibraryCacheable // Implements the optional interface.
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
		// The database library object was passed in.
		// Alternatively we could have used $controller->loadLibrary('Database');
		$this->sql = $arguments->get(0);
	}

	/**
	 * getNews()
	 *
	 * @param integer $news_id ID of news post to fetch.
	 * @param integer $limit The amount of news posts to fetch.
	 * @return array An array of news posts.
	 */
	public function getNews($news_id = null, $limit = 5)
	{
		$statement = '
		SELECT
			`id`,
			`title`,
			`content`
		FROM
			`' . $this->sql->prefix . 'news`';

		if ( is_numeric($news_id) )
		{
			$statement .= '
			WHERE
				`id` = ' . (int)$news_id . '
			LIMIT 1';

			return $this->sql->fetch_assoc($statement);
		}
		else
		{
			$statement .= '
			LIMIT ' . (int)$limit;

			return $this->sql->fetch_assoc_array($statement);
		}
	}

	/**
	 * getComments()
	 *
	 * @param integer $news_id ID of news post to fetch comments for.
	 * @return array An array of news comments.
	 */
	public function getComments($news_id)
	{
		if ( !is_numeric($news_id) )
			return array();

		$statement = '
		SELECT
			`content`
		FROM
			`' . $this->sql->prefix . 'comments`
		WHERE
			`parent` = ' . (int)$news_id;

		return $this->sql->fetch_assoc_array($statement);
	}

	/**
	 * postNews()
	 *
	 * @param string $title The title of the news post.
	 * @param string $content The content of the news post.
	 * @return void
	 */
	public function postNews($title, $content)
	{
		$statement = '
		INSERT INTO
			`' . $this->sql->prefix . 'news`
			(`title`, `content`)
		VALUES(
			"' . $this->sql->escape($title) . '",
			"' . $this->sql->escape($content) . '"
		)';

		$this->sql->execute($statement);

		CacheTracker::triggerDataKeyInvalidation(array('news' => null));
	}

	/**
	 * postComment()
	 *
	 * @param integer $news_id The news ID to post the comment to.
	 * @param string $content The contents of the comment.
	 * @return void
	 */
	public function postComment($news_id, $content)
	{
		$statement = '
		INSERT INTO
			`' . $this->sql->prefix . 'comments`
			(`parent`, `content`)
		VALUES(
			"' . (int)$news_id . '",
			"' . $this->sql->escape($content) . '"
		)';

		$this->sql->execute($statement);

		CacheTracker::triggerDataKeyInvalidation(array('comment' => $news_id));
	}

	/**
	 * dataKeyWrites()
	 * Optional method to define the data keys this library can write to.
	 * At the moment this is purely for code organization and overview.
	 *
	 * @return array The data keys the library can write to.
	 */
	public static function dataKeyWrites()
	{
		return array('news', 'comment');
	}
}