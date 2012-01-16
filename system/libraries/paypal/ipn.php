<?php
namespace Evil\Library\PayPal;

/**
 * IPN
 * PayPal Instant Payment Notification handler.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 * @uses CURL
 */
abstract class IPN
{
	protected $settings       = array(
		'seller_email'         => '',
		'sandbox_email'        => '',
		'verifier_url'         => 'https://www.paypal.com/cgi-bin/webscr',
		'sandbox_url'          => 'https://www.sandbox.paypal.com/cgi-bin/webscr'
	);

	protected $items          = array();
	protected $pre_hooks      = array();
	protected $post_hooks     = array();
	protected $currencies     = array();
	protected $prices         = array();

	protected $error_codes    = array(
		'Invalid Seller Email' => 450,
		'Invalid Currency'     => 451,
		'Insufficient Payment' => 452,
		'Invalid Item'         => 453,
		'Invalid Payment'      => 455,
		'Invalid Recipient'    => 456,
		'Incomplete Payment'   => 200,
		'No Item Action'       => 457,
		'Not Supported'        => 458,
		'Socket Error'         => 459,
		'Curl Missing'         => 460,
	);

	protected $error_messages = array(
		'Invalid Seller Email' => 'The seller email cannot be empty and must be a valid email address.',
		'Invalid Currency'     => 'The currency used is not valid for this item.',
		'Insufficient Payment' => 'The money paid is not enough.',
		'Invalid Item'         => 'The item ID is not a valid item.',
		'Invalid Payment'      => 'Invalid payment detected, high risk of fraud attempt.',
		'Invalid Recipient'    => 'Invalid payment recipient detected, high risk of fraud attempt.',
		'Incomplete Payment'   => 'Payment has not yet been completed.',
		'No Item Action'       => 'No appropiate item action was found.',
		'Not Supported'        => 'This function is not yet supported.',
		'Socket Error'         => 'A connection to the paypal server could not be established.',
		'Curl Missing'         => 'A necessary component "Curl" is not installed.',
	);

	/**
	 * IPN::__construct()
	 * Get class to minimum required working state.
	 *
	 * @param Controller $controller The framework controller.
	 * @param Arguments $arguments The framework arguments object.
	 * @return void
	 */
	public function __construct($controller, $arguments)
	{
		if ( !function_exists('curl_init') )
			$this->throwError('Curl Missing');

		if ( !is_null($arguments->get('Validate')) )
			$this->validate = $arguments->get('Validate');
		else
			$this->validate = $controller->loadLibrary('Validate');

		if ( !is_null($arguments->get('Seller Email')) )
			$this->settings['seller_email'] = strtolower($arguments->get('Seller Email'));
		elseif ( !is_null($arguments->get(0)) )
			$this->settings['seller_email'] = strtolower($arguments->get(0));

		if ( !is_null($arguments->get('Sandbox Email')) )
			$this->settings['sandbox_email'] = strtolower($arguments->get('Sandbox Email'));
		elseif ( !is_null($arguments->get(1)) )
			$this->settings['sandbox_email'] = strtolower($arguments->get(1));

		if ( !is_null($arguments->get('Verifier URL')) )
			$this->settings['verifier_url'] = strtolower($arguments->get('Verifier URL'));
		elseif ( !is_null($arguments->get(2)) )
			$this->settings['verifier_url'] = strtolower($arguments->get(2));

		if ( !is_null($arguments->get('Sandbox URL')) )
			$this->settings['sandbox_url'] = strtolower($arguments->get('Sandbox URL'));
		elseif ( !is_null($arguments->get(3)) )
			$this->settings['sandbox_url'] = strtolower($arguments->get(3));

		$this->sandbox = !empty($_POST['test_ipn']);

		try
		{
			if ( empty($this->settings['seller_email']) )
				$this->throwError('Invalid Seller Email');

			$this->validate->isEmail($this->settings['seller_email']);
		}
		catch (\Evil\Library\ValidationException $e)
		{
			$this->throwError('Invalid Seller Email');
		}
	}

	/**
	 * IPN::getLibraryName()
	 * Helper method to determine the type of payment.
	 *
	 * @return string The type of the payment.
	 */
	public static function getLibraryName($type)
	{
		switch($type)
		{
			case 'web_accept':
				return 'webpayment';
				break;
			case 'subscr_signup':
			case 'subscr_payment':
			case 'subscr_modify':
			case 'subscr_failed':
			case 'subscr_cancel':
			case 'subscr_eot':
				return 'subscription';
				break;
			case 'recurring_payment_profile_created':
			case 'recurring_payment':
				return 'recurring';
				break;
			case 'new_case':
			case 'adjustment':
				return 'dispute';
				break;
			case '—':
				return 'chargeback';
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * IPN::receivePayment()
	 * Start the payment validation and finalization process.
	 *
	 * @return void
	 */
	public function receivePayment()
	{
		$this->processPreHooks  ($_POST);
		$this->verifyInformation($_POST);

		$item = trim($_POST['item_number']);
		$type = trim($_POST['txn_type']);

		if ( isset($this->items[$item][$type]) )
			$this->items[$item][$type]($_POST);
		else
			$this->throwError('No Item Action');

		$this->processPostHooks($_POST);
	}

	/**
	 * IPN::addItem()
	 * Adds an item to our catalogue of valid products.
	 *
	 * @param int          $id Item ID.
	 * @param closure      $function The anonymous method to execute upon successful sale.
	 * @param string|array $type The type of payment made, webpayment, chargeback, dispute, etc. See PayPal documentation.
	 * @return void
	 */
	public function addItem($id, $function, $type = false)
	{
		if ( is_array($type) )
		{
			foreach($type as $entry)
					$this->items[$id][$entry] = $function;
		}
		else
		{
			if (!$type)
				$this->throwError('No Item Action');

			$this->items[$id][$type] = $function;
		}
	}

	/**
	 * IPN::addHook()
	 * Add a hook to be processed at some point in the process.
	 *
	 * @param string  $position Either "pre" or "post", defaults to pre.
	 * @param closure $function The anonymous function to execute.
	 * @return void
	 */
	public function addHook($position, $function)
	{
		switch ($position)
		{
			default:
			case 'pre':
				$this->pre_hooks[] = $function;
				break;
			case 'post':
				$this->post_hooks[] = $function;
				break;
		}
	}

	/**
	 * IPN::setValidCurrency()
	 * Set the valid currency for an item.
	 * If single currency just specify currency.
	 * If multiple currencies use an array in the format of
	 *
	 * array(
	 *   'DKK',
	 *   'GBP'
	 * )
	 *
	 * @param int          $item The item ID for which to set currency as valid.
	 * @param string|array $currency A string or array of strings of currencies to be set as valid.
	 * @return void
	 */
	public function setValidCurrency($item, $currency)
	{
		if ( is_array($currency) )
		{
			array_walk($currency, function ($currency) {
				return strtoupper($currency);
			});

			$this->currencies[$item] = $currency;
		}
		else
		{
			$this->currencies[$item] = strtoupper($currency);
		}
	}

	/**
	 * IPN::setValidPricePerUnit()
	 * Set the valid prices for an item.
	 * If single currency just specify price.
	 * If multiple currencies use an array in the format of
	 *
	 * array(
	 *   'DKK' => 90.00,
	 *   'GBP' => 10.00
	 * )
	 *
	 * @param int         $item The item ID for which to set a valid price.
	 * @param float|array $price A float or array of floats with prices that are to be valid.
	 * @return void
	 */
	public function setValidPricePerUnit($item, $price)
	{
		if ( is_array($price) )
		{
			$price = array_flip($price);
			array_walk($price, function ($price) {
				return strtoupper($price); // We want the currency in uppercase.
			});

			$this->prices[$item] = array_flip($price);
		}
		else
		{
			$this->prices[$item] = strtoupper($price);
		}
	}

	/**
	 * IPN::verifyWithPayPal()
	 * Performs a double check with PayPal that the IPN is valid.
	 *
	 * @param array $data The payment data array.
	 * @return bool
	 */
	protected function verifyWithPayPal($data)
	{
		$post = 'cmd=_notify-validate';
		foreach($data as $key => $value)
			$post .= '&' . urlencode($key) . '=' . urlencode($value);

		$url = $this->sandbox ? $this->settings['sandbox_url'] : $this->settings['verifier_url'];

		$connection = curl_init();
		curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($connection, CURLOPT_URL, $url);
		curl_setopt($connection, CURLOPT_POST, true);
		curl_setopt($connection, CURLOPT_POSTFIELDS, $post);
		curl_setopt($connection, CURLOPT_HEADER, true);
		curl_setopt($connection, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Content-Length: ' . strlen($post) ) );
		$response = curl_exec($connection);

		if( curl_errno($connection) )
			$this->throwError('Socket Error');

		curl_close($connection);

		return $response !== 'INVALID';
	}

	/**
	 * IPN::verifyInformation()
	 * Stub method for extending classes to verify that the payment is valid.
	 *
	 * @param array $data The payment data array.
	 * @return void
	 */
	abstract protected function verifyInformation($data);

	/**
	 * IPN::processPreHooks()
	 * Executes any pre-validation hooks added to the request.
	 *
	 * @param array $data The payment data array.
	 * @return void
	 */
	protected function processPreHooks($data)
	{
		for($x = 0; $x < count($this->pre_hooks); $x++)
			$this->pre_hooks[$x]($data);
	}

	/**
	 * IPN::processPostHooks()
	 * Executes any post-validation hooks added to the request.
	 *
	 * @param array $data The payment data array.
	 * @return void
	 */
	protected function processPostHooks($data)
	{
		for($x = 0; $x < count($this->post_hooks); $x++)
			$this->post_hooks[$x]($data);
	}

	/**
	 * IPN::isValidSeller()
	 * Internal helper method.
	 * Checks whether the seller email matches our records.
	 *
	 * @param string $seller The seller email to check.
	 * @return bool
	 */
	protected function isValidSeller($seller)
	{
		$email = $this->sandbox ? $this->settings['sandbox_email'] : $this->settings['seller_email'];
		return strtolower($seller) === strtolower($email);
	}

	/**
	 * IPN::isCompletedPayment()
	 * Internal helper method.
	 * Checks whether paypal says the payment is finalized.
	 *
	 * @param string $status The payment status.
	 * @return bool
	 */
	protected function isCompletedPayment($status)
	{
		return $status === 'Completed';
	}

	/**
	 * IPN::isValidItem()
	 * Internal helper method.
	 * Checks whether this item ID exists in our product catalogue.
	 *
	 * @param int $item The item ID to check.
	 * @return bool
	 */
	protected function isValidItem($item)
	{
		return isset($this->currencies[$item], $this->prices[$item]);
	}

	/**
	 * IPN::isValidCurrency()
	 * Internal helper method.
	 * Checks whether the payment was made in an allowed currency.
	 *
	 * @param int    $item The item for which to add a currency.
	 * @param string $currency Which currency is allowed.
	 * @return bool
	 */
	protected function isValidCurrency($item, $currency)
	{
		$currency = strtoupper($currency);

		if ( is_array($this->currencies[$item]) )
			return in_array($currency, $this->currencies[$item]);
		else
			return $this->currencies[$item] === $currency;
	}

	/**
	 * IPN::isValidPrice()
	 * Internal helper method.
	 * Calculates whether or not the payment made is big enough.
	 *
	 * @param int    $item The price of each item.
	 * @param int    $quantity The amount of items sold.
	 * @param string $currency Which currency the payment was made in.
	 * @param float  $paid How much money was paid.
	 * @return bool
	 */
	protected function isValidPrice($item, $quantity, $currency, $paid)
	{
		// We can assume the currency is already valid.
		if ( is_array($this->prices[$item]) )
			$price = $this->prices[$item][$currency];
		else
			$price = $this->prices[$item];

		return $quantity * $price <= $paid;
	}

	/**
	 * IPN::throwError()
	 * Throw a class exception.
	 *
	 * @param string $type The error type.
	 * @return void
	 */
	protected function throwError($type)
	{
		throw new IPNException($this->error_messages[$type], $this->error_codes[$type]);
	}
}

/**
 * IPNException
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 */
class IPNException extends \Exception
{
	/**
	 * __construct()
	 *
	 * @param string  $message
	 * @param integer $code
	 * @return void
	 */
	public function __construct($message = '', $code = 449)
	{
		parent::__construct($message, $code);
	}
}