<?php
namespace Evil\Libraries;

/**
 * Pagination
 * Provides methods to deal with generating paginations.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 * @todo Refactor and expand, probably too limited?
 * @todo More paging styles?
 */
class Pagination
{
	/**
	 * Pagination::generatePagination()
	 * Generate a pagination array.
	 *
	 * @param int $total_rows The total amount of rows in result set.
	 * @param int $pages_display The pages to display at one time.
	 * @param int $per_page The items to display per page.
	 * @param int $current_page  The current page the user is on.
	 * @return Array An array of pagination text/links.
	 */
	public function generatePagination($total_rows, $pages_display, $per_page, $current_page)
	{
		if ($total_rows <= $per_page)
			return false;

		if ($current_page < 0)
			$current_page = 0;

		$max_pages = ceil($total_rows / $per_page);

		if ($max_pages == 1)
			return array();

		if ($current_page >= $max_pages)
			$current_page = $max_pages;

		$pagination = array();

		if ($current_page > 0)
		{
			$pagination['First'] = 0;
			$pagination['Previous'] = $current_page - 1; // - 2 because $current_page is visual and computers count from 0.
		}

		// Calculate pages to show.
		if ($pages_display / 2 >= $current_page)
			$lower = 1;
		else if ($current_page + $pages_display / 2 >= $max_pages + 1) // + 1 because we want the last page included.
			$lower = $max_pages - $pages_display + 1; // + 1 because we want the last page included in the pagination.
		else
			$lower = $current_page - $pages_display / 2;

		if ($lower < 1)
			$lower = 1;

		if ($current_page >= $max_pages + 1 - $pages_display / 2) // Upper bound is beyond max pages.
			$upper = $max_pages + 1;
		else
			$upper = $lower + ($pages_display > $max_pages ? $max_pages : $pages_display);

		for ($x = $lower; $x < $upper; $x++)
			$pagination[$x] = $x - 1; // Because computers count from 0

		if ( $current_page != $max_pages - 1 )
		{
			$pagination['Next'] = $current_page + 1;
			$pagination['Last'] = $max_pages - 1;
		}

		return $pagination;
	}
}