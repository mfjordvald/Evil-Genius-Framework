<?php
namespace Evil\Libraries\PayPal;

/**
 * IPN - Web payment
 * PayPal web payment Instant Payment Notification handler.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 * @uses CURL
 */
class WebPayment extends IPN
{
	/**
	 * WebPayment::__construct()
	 * Get class to minimum required working state.
	 *
	 * @param Validate $validate      Validate library.
	 * @param string   $seller_email  The seller email.
	 * @param string   $sandbox_email The sandbox seller email.
	 * @param string   $verifier_url  The paypal verifier url.
	 * @param string   $sandbox_url   The paypal sandbox verifier  url.
	 * @return void
	 */
	public function __construct(\Evil\Libraries\Validate $validate, $seller_email, $sandbox_email, $verifier_url = null, $sandbox_url = null)
	{
		parent::__construct($validate, $seller_email, $sandbox_email, $verifier_url, $sandbox_url);
	}

	/**
	 * WebPayment::receivePayment()
	 * Start the payment validation and finalization process.
	 *
	 * @return void
	 */
	public function receivePayment()
	{
		$this->processPreHooks  ($_POST);
		$this->verifyInformation($_POST);

		$item = trim($_POST['item_number']);

		if ( isset($this->items[$item]) )
			$this->items[$item]($_POST);
		else
			$this->throwError('No Item Action');

		$this->processPostHooks($_POST);
	}

	/**
	 * WebPayment::addItem()
	 * Adds an item to our catalogue of valid products.
	 *
	 * @param int          $id Item ID.
	 * @param closure      $function The anonymous method to execute upon successful sale.
	 * @param string|array $type Type is NOT supported for webpayment events.
	 * @return void
	 */
	public function addItem($id, $function, $type = null)
	{
		if ( !is_null($type) )
			$this->throwError('Not Supported');

		$this->items[$id] = $function;
	}

	/**
	 * WebPayment::verifyInformation()
	 * Verifies that the payment is valid.
	 *
	 * @param array $data The payment data array.
	 * @return void
	 */
	protected function verifyInformation($data)
	{
		$seller   = $data['receiver_email'];
		$item     = $data['item_number'];
		$status   = $data['payment_status'];
		$currency = $data['mc_currency'];
		$quantity = $data['quantity'];
		$paid     = $data['mc_gross'];

		if ( !$this->verifyWithPayPal($data) )
			$this->throwError('Invalid Payment');

		if ( !$this->isValidSeller($seller) )
			$this->throwError('Invalid Recipient');

		if ( !$this->isCompletedPayment($status) )
			$this->throwError('Incomplete Payment');

		if ( !$this->isValidItem($item) )
			$this->throwError('Invalid Item');

		if ( !$this->isValidCurrency($item, $currency) )
			$this->throwError('Invalid Currency');

		if ( !$this->isValidPrice($item, $quantity, $currency, $paid) )
			$this->throwError('Insufficient Payment');
	}
}