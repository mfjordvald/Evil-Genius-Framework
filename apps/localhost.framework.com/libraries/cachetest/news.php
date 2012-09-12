<?php
namespace Evil\Libraries\CacheTest;

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
	 * @param SQL          $database      A SQL object.
	 * @param CacheTracker $cache_tracker A CacheTracker object.
	 * @return void
	 */
	public function __construct(\Evil\Libraries\SQL\SQL $database, \Evil\Core\CacheTracker $cache_tracker)
	{
		$this->database = $database;
		$this->cache_tracker = $cache_tracker;
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
			`' . $this->database->prefix . 'news`';

		if ( is_numeric($news_id) )
		{
			$statement .= '
			WHERE
				`id` = ' . (int)$news_id . '
			LIMIT 1';

			return $this->database->fetch_assoc($statement);
		}
		else
		{
			$statement .= '
			LIMIT ' . (int)$limit;

			return $this->database->fetch_assoc_array($statement);
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
			`' . $this->database->prefix . 'comments`
		WHERE
			`parent` = ' . (int)$news_id;

		return $this->database->fetch_assoc_array($statement);
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
			`' . $this->database->prefix . 'news`
			(`title`, `content`)
		VALUES(
			"' . $this->database->escape($title) . '",
			"' . $this->database->escape($content) . '"
		)';

		$this->database->execute($statement);

		$this->cache_tracker->triggerDataKeyInvalidation(array('news' => null));
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
			`' . $this->database->prefix . 'comments`
			(`parent`, `content`)
		VALUES(
			"' . (int)$news_id . '",
			"' . $this->database->escape($content) . '"
		)';

		$this->database->execute($statement);

		$this->cache_tracker->triggerDataKeyInvalidation(array('comment' => $news_id));
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