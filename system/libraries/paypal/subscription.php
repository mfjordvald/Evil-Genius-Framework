<?php
namespace Evil\Libraries\PayPal;

/**
 * IPN - Subscription
 * PayPal subscription Instant Payment Notification handler.
 *
 * @package Evil Genius Framework
 * @author Martin Fjordvald
 * @copyright Evil Genius Media
 * @uses CURL
 */
class Subscription extends IPN
{
	/**
	 * Subscription::__construct()
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
	 * Subscription::verifyInformation()
	 * Verifies that the payment is valid.
	 *
	 * @param array $data The payment data array.
	 * @return void
	 */
	protected function verifyInformation($data)
	{
		switch($data['txn_type'])
		{
			case 'subscr_signup':
				$this->verifySignup($data);
				break;
			case 'subscr_payment':
				$this->verifyPayment($data);
				break;
			case 'subscr_cancel':
				$this->verifyCancel($data);
				break;
			case 'subscr_eot':
				$this->verifyEOT($data);
				break;
			default:
				$this->throwError('Not Supported');
				break;
		}
	}

	/**
	 * Subscription::verifySignup()
	 * Internal helper method.
	 * Verifies that a signup event is valid.
	 *
	 * @param array $data The payment data array.
	 * @return void
	 */
	protected function verifySignup($data)
	{
		$is_trial = isset($data['amount1']) || isset($data['amount2']) ? true : false;
		$seller   = $data['receiver_email'];
		$item     = $data['item_number'];

		if ($is_trial)
		{
			$this->throwError('Not Supported');
		}
		else
		{
			if ( !$this->verifyWithPayPal($data) )
				$this->throwError('Invalid Payment');

			if ( !$this->isValidSeller($seller) )
				$this->throwError('Invalid Recipient');

			if ( !$this->isValidItem($item) )
				$this->throwError('Invalid Item');
		}
	}

	/**
	 * Subscription::verifyPayment()
	 * Internal helper method.
	 * Verifies that a subscription payment is valid.
	 *
	 * @param array $data The payment data array.
	 * @return void
	 */
	protected function verifyPayment($data)
	{
		$seller   = $data['receiver_email'];
		$item     = $data['item_number'];
		$status   = $data['payment_status'];
		$currency = $data['mc_currency'];
		$quantity = $data['quantity'];
		$paid     = $data['mc__gross'];

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

	/**
	 * Subscription::verifyCancel()
	 * Internal helper method.
	 * Verifies that a cancel event is valid.
	 *
	 * @param array $data The payment data array.
	 * @return void
	 */
	protected function verifyCancel($data)
	{
		$seller = $data['receiver_email'];
		$item   = $data['item_number'];

		if ( !$this->verifyWithPayPal($data) )
			$this->throwError('Invalid Payment');

		if ( !$this->isValidSeller($seller) )
			$this->throwError('Invalid Recipient');

		if ( !$this->isValidItem($item) )
			$this->throwError('Invalid Item');
	}

	/**
	 * Subscription::verifyEOT()
	 * Internal helper method.
	 * Verifies that an end-of-term event is valid.
	 *
	 * @param array $data The payment data array.
	 * @return void
	 */
	protected function verifyEOT($data)
	{
		$seller = $data['receiver_email'];
		$item   = $data['item_number'];

		if ( !$this->verifyWithPayPal($data) )
			$this->throwError('Invalid Payment');

		if ( !$this->isValidSeller($seller) )
			$this->throwError('Invalid Recipient');

		if ( !$this->isValidItem($item) )
			$this->throwError('Invalid Item');
	}
}