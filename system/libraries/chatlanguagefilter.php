<?php
namespace Evil\Library;

/**
 * ChatLanguageFilter
 * Provides detection of chat-language.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @author Peter Schofield
 * @copyright Evil Genius Media
 * @todo Generalize excluded words, slang, emoticons and punctuation.
 */
class ChatLanguageFilter
{
	/**
	 * ChatLanguageFilter::detect()
	 * Attempts to detect chat-language.
	 *
	 * @param string $text The string to check for chat-language.
	 * @return bool|array False if no chat-language. Array with points and words otherwise.
	 */
	public function detect($text)
	{
		if (strtoupper($text) == $text)
		{
			return array(
				'points' => 9001,
				'words'  => array('All words were capitalized')
			);
		}

		$this->points = 0;       // Current points a text has.
		$this->limit  = 6;       // How many points before taking action.
		$this->words  = array(); // Words that was detected as chat-language

		$text = $this->sanitizeText($text);

		$words = explode(' ', $text);
		$words = array_filter($words, function($text) {
			return !empty($text) && !ctype_digit($text) && !ctype_punct($text);
		});

		foreach ($words as $word)
		{
			$points = 0;
			$points += $this->checkRepeats($word);
			$points += $this->checkLength($word);
			$points += $this->checkNoVowels($word);
			$points += $this->checkShortend($word);
			$points += $this->checkSubstituted($word);

			if ($points)
				$this->addPoints($word, $points);
		}

		if ( $this->points >= $this->limit )
		{
			return array(
				'points' => $this->points,
				'words'  => $this->words
			);
		}

		return false;
	}

	/**
	 * ChatLanguageFilter::checkNoVowels()
	 * Checks if a word contains no vowels.
	 *
	 * @param string $word The word to check.
	 * @return int Point value to assign.
	 */
	protected function checkNoVowels($word)
	{
		$exempted = array(
			'r4', 'ttds', 'm3',
			'ds', 'nds', 'psx', 'ps1', 'ps2', 'gc', 'n64', 'dc'
		);

		if ( !in_array($word, $exempted) && !preg_match('/[a|e|i|o|u|y]/', $word) )
			return 3;
		else
			return 0;
	}

	/**
	 * ChatLanguageFilter::checkSubstituted()
	 * Checks if a word contains letters substituted with numbers.
	 *
	 * @param string $word The word to check.
	 * @return int Point value to assign.
	 */
	protected function checkSubstituted($word)
	{
		$substituted = array(
			'b4', 'l8', 'l8r', 'l8tr', 'any1', 'som1',
			'some1', 'sum1', 'sux0rs', 'suz0rz', 'gr8', 'w8', 'h8'
		);

		if ( in_array($word, $substituted) )
			return 4;
		else
			return 0;
	}

	/**
	 * ChatLanguageFilter::checkShortend()
	 * Checks whether a word has been shortened or bastardized.
	 *
	 * @param string $word The word to check.
	 * @return int Point value to assign.
	 */
	protected function checkShortend($word)
	{
		$shortened = array(
			'omg', 'lol', 'rofl', 'lmao', 'roflmao',
			'thx', 'thanx', 'tnk', 'hav', 'u', 'r', 'dis', 'lolz',
			'pls', 'plz', 'lyk', 'cuz', 'awesum', 'zomg', 'sekz',
			'tho', 'ownz', 'sux', 'omg', 'laik', 'liek', 'liek',
			'lul', 'lulz', 'dat', 'ur', 'teh', 'suxorz', 'suxors'
		);

		if ( in_array($word, $shortened) )
			return 4;
		else
			return 0;
	}

	/**
	 * ChatLanguageFilter::checkLength()
	 * Checks if a word has not enough letters.
	 *
	 * @param string $word The word to check.
	 * @return int Point value to assign.
	 */
	protected function checkLength($word)
	{
		if ( strlen($word) == 1 && !in_array($word, array('a', 'i')) )
			return 3;
		else
			return 0;
	}

	/**
	 * ChatLanguageFilter::checkRepeats()
	 * Checks for consonants repeated thrice or more.
	 *
	 * @param string $word The word to check.
	 * @return int Point value to assign.
	 */
	protected function checkRepeats($word)
	{
		$last    = '';
		$repeats = $consonants = $points =  0;

		for ($i = 0; $i < strlen($word); $i++)
		{
			// Only counting non-vowels allows words such as "oooooh" or "aaaaaah".
			if ( !in_array($word[$i], array('a', 'e', 'i', 'o', 'u', 'y') ) )
			{
				if ($word[$i] != $last)
				{
					$last = $word[$i];
					$repeats = 1;
				}
				else
				{
					$repeats++;
				}

				if ($repeats > 2)
					$points += $repeats;

				$consonants++;
			}
			else
			{
				$repeats = 1;
				$last = $word[$i];
				$consonants = 0;
			}

			if ($consonants > 4)
				$points += $consonants;
		}

		return $points;
	}

	/**
	 * ChatLanguageFilter::addPoints()
	 * Adds points to the total.
	 *
	 * @param string $word The word that was flagged.
	 * @param int $points The points to add.
	 * @return void
	 */
	protected function addPoints($word, $points)
	{
		$this->words[] = $word;
		$this->points += $points;
	}

	/**
	 * ChatLanguageFilter::sanitizeText()
	 * Sanitizes the text for checking.
	 *
	 * @param string $string The text to sanitize.
	 * @return string The sanitized text.
	 */
	protected function sanitizeText($string)
	{
		$string = strtolower($string);

		// preg_replace rules
		$rules = array(
			'#\v#',  // New lines
			'#\t#',  // Tabs
			'#\s+#', // Excess whitespace
		);

		// Remove most emoticons.
		$string = str_replace(array(
			':)', ';)', '=)',
			':]', ';]', '=]',
			':(', ';(', '=(',
			':[', ';[', '=[',
			':d', ';d', '=d',
			':/', ';/', '=/',
			':p', ';p', '=p',
		), ' ', $string);

		// Remove most punctuation.
		$string = str_replace(array('.', ',', ':', ';', '!', '(', ')', '?', '-', '•'), ' ', $string);

		$string = strip_tags($string);
		$string = preg_replace($rules, ' ', $string);

		return trim($string);
	}
}