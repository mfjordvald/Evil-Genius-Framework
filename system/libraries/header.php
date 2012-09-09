<?php
namespace Evil\Libraries;

/**
 * Header
 * Helps generate headers.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class Header
{
	// Expand this
	protected $status_headers = array
	(
		'100' => 'HTTP/1.1 100 Continue',
		'101' => 'HTTP/1.1 101 Switching Protocols',
		'200' => 'HTTP/1.1 200 OK',
		'201' => 'HTTP/1.1 201 Created',
		'202' => 'HTTP/1.1 202 Accepted',
		'203' => 'HTTP/1.1 203 Non-Authoritative Information',
		'204' => 'HTTP/1.1 204 No Content',
		'205' => 'HTTP/1.1 205 Reset Content',
		'206' => 'HTTP/1.1 206 Partial Content',
		'300' => 'HTTP/1.1 300 Multiple Choices',
		'301' => 'HTTP/1.1 301 Moved Permanently',
		'302' => 'HTTP/1.1 302 Found',
		'303' => 'HTTP/1.1 303 See Other',
		'304' => 'HTTP/1.1 304 Not Modified',
		'306' => 'HTTP/1.1 305 Use Proxy',
		'307' => 'HTTP/1.1 307 Temporary Redirect',
		'400' => 'HTTP/1.1 400 Bad Request',
		'401' => 'HTTP/1.1 401 Unauthorized',
		'402' => 'HTTP/1.1 402 Payment Required',
		'403' => 'HTTP/1.1 403 Permission Denied',
		'404' => 'HTTP/1.1 404 Not Found',
		'405' => 'HTTP/1.1 405 Method Not Allowed',
		'406' => 'HTTP/1.1 406 Not Acceptable',
		'407' => 'HTTP/1.1 407 Proxy Authentication Required',
		'408' => 'HTTP/1.1 408 Request Timeout',
		'409' => 'HTTP/1.1 409 Conflict',
		'410' => 'HTTP/1.1 410 Gone',
		'411' => 'HTTP/1.1 411 Length Required',
		'412' => 'HTTP/1.1 412 Precondition Failed',
		'413' => 'HTTP/1.1 413 Request Entity Too Large',
		'414' => 'HTTP/1.1 414 Request-URI Too Long',
		'415' => 'HTTP/1.1 415 Unsupported Media Type',
		'416' => 'HTTP/1.1 416 Requested Range Not Satisfiable',
		'417' => 'HTTP/1.1 417 Expectation Failed',
		'500' => 'HTTP/1.1 500 Internal Server Error',
		'501' => 'HTTP/1.1 501 Not Implemented',
		'502' => 'HTTP/1.1 502 Bad Gateway',
		'503' => 'HTTP/1.1 503 Service Temporarily Unavailable',
		'505' => 'HTTP/1.1 505 HTTP Version Not Supported'
	);

	protected $status_keywords = array
	(
		'moved permanently' => 301,
		'moved'             => 302,
		'forbidden'         => 403,
		'not found'         => 404,
		'unavailable'       => 503
	);

	/**
	 * Header::status()
	 * Wrapper for statusHeader()
	 *
	 * @param $headers int|string Status code in either numeric code or text description format.
	 * @return bool
	 */
	public function status($header)
	{
		$this->statusHeader($header);
	}

	/**
	 * Header::custom()
	 * Wrapper for customHeader()
	 *
	 * @param int $status Numeric status code to send.
	 * @param string $message Message to send in header.
	 * @return bool
	 */
	public function custom($status, $message)
	{
		$this->customHeader($status, $message);
	}

	/**
	 * Header::statusHeader()
	 * Set a status header.
	 *
	 * @param int|string $headers Status code in either numeric code or text description format.
	 * @return bool
	 */
	public function statusHeader($header)
	{
		if ( headers_sent() )
			throw new HeaderException('Cannot send header: Headers already sent.');

		if ( is_numeric($headers) )
			$this->statusNumeric($headers);
		else
			$this->statusString($headers);

		return true;
	}

	/**
	 * Header::customHeader()
	 * Set a non-standard status header.
	 *
	 * @param int $status Numeric status code to send.
	 * @param string $message Message to send in header.
	 * @return bool
	 */
	public function customHeader($status, $message)
	{
		if ( headers_sent() )
			throw new HeaderException('Cannot send header: Headers already sent.');

		header('HTTP/1.1 ' . $status . ' ' . $message);

		return true;
	}

	/**
	 * Header::statusString()
	 * Do a status header based on text description.
	 *
	 * @param string $string The description of the status header.
	 * @return void
	 */
	protected function statusString($string)
	{
		$string = strtolower($string);

		if ( !empty($this->status_keywords[$string]) )
		{
			$status = $this->status_keywords[$string];
			header($this->status_headers[$status]);
		}
	}

	/**
	 * Header::statusNumeric()
	 * Do a status header based on the numeric status code.
	 *
	 * @param int $number The status code of the status header.
	 * @return void
	 */
	protected function statusNumeric($number)
	{
		if ( !empty($this->status_headers[$number]) )
			header($this->status_headers[$number]);
	}

	/**
	 * Header::redirect()
	 * Do a redirect.
	 *
	 * @param string $url The URL to redirect to.
	 * @param integer $mode The status header code to use in the redirect.
	 * @return void
	 */
	public function redirect($url, $mode = 301)
	{
		if ( headers_sent() )
			throw new HeaderException('Cannot redirect: Headers already sent.');

		if ( empty($mode) )
			$mode = 301;

		$this->statusHeader($mode);

		header('Location: ' . $url);
	}
}

/**
 * HeaderException
 * Exception class for header errors.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class HeaderException extends \Exception
{
	/**
	 * __construct()
	 *
	 * @param string $message
	 * @param integer $code
	 * @return void
	 */
	public function __construct ($message = '', $code = 0)
	{
		parent::__construct($message, $code);
	}
}