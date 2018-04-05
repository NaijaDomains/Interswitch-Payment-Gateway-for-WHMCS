<?php
session_start();
/**
 * WHMCS Payment Gateway Module for Interswitch
 *
 * Payment Gateway modules allow you to integrate payment solutions with the
 * WHMCS platform.
 *
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/
 * 
 * @ Version  : 1.0
 * @ Author   : Victor TIN 
 * @ Release  : 2018-04-04
 * @ Website  : http://www.naijadomains.com
 
 * @copyright Copyright (c) Stormcell
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function interswitch_MetaData()
{
    return array(
        'DisplayName' => 'Interswitch WebPay',
        'APIVersion' => '1.0', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 *
 * @return array
 */
function interswitch_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Interswitch WebyPay (Mastercard, Visa, Verve)',
        ),
        // a text field type allows for single line text input
        'product_id' => array(
            'FriendlyName' => 'Product ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '1076',
            'Description' => 'Product Id provided by WebPay (test product_id: 1076)',
        ),
		 'pay_item_id' => array(
            'FriendlyName' => 'Pay Item ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '101',
            'Description' => ' Item Id provided by WebPay (test pay_item_id: 101)',
        ),
        // a password field type allows for masked text input
        'mac_key' => array(
            'FriendlyName' => 'Mac Key',
            'Type' => 'text',
            'Default' => '',
		 	'Size' => '50',
            'Description' => 'MAC provided by WebPay',
        ),
		'test_payment_url' => array(
            'FriendlyName' => 'TEST Payment URL',
            'Type' => 'text',
            'Default' => 'https://sandbox.interswitchng.com/collections/w/pay',
            'Description' => 'Test url provided by WebPay for development',
        ),
		
		'live_payment_url' => array(
            'FriendlyName' => 'LIVE Payment URL',
            'Type' => 'text',
            'Default' => 'https://webpay.interswitchng.com/collections/w/pay',
            'Description' => 'Live url provided by WebPay for production',
        ),
	
       
        // the radio field type displays a series of radio button options
        'apimodde' => array(
            'FriendlyName' => 'API Mode',
            'Type' => 'radio',
            'Options' => 'Development,Production',
			'Default' =>'Development',
            'Description' => 'Tick this box to request manage API mode',
        ),
        
    );
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @return string
 */
function interswitch_link($params)
{
    // Gateway Configuration Parameters
    $product_id = $params['product_id'];
    $pay_item_id = $params['pay_item_id'];
	$mac_key = $params['mac_key'];
    $apimodde = $params['apimodde'];
    $test_payment_url = $params['test_payment_url'];
    $live_payment_url = $params['live_payment_url'];
   
	
	 

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
	$totalAmount=$params['amount'];
	$referenceId= 'NDWHM-'.$params['invoiceid'].'-'.intval( "0" . rand(1,9) . rand(0,9) . rand(0,9));
    $description = $params["description"];
    $amount = round($params['amount'])*100;
    $currencyCode = $params['currency'];

	
	//print_r($params['clientdetails']);
    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
	 $customer_id = $params['clientdetails']['userid'];
    $lastname = $params['clientdetails']['lastname'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = rtrim($params['systemurl'],"/");
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
  	
    $url = ($apimodde=='Development')?$test_payment_url:$live_payment_url;
	
	$_SESSION['amount']=$amount;
	$_SESSION['product_id']=$product_id;
	$_SESSION['pay_item_id']=$pay_item_id;
	$_SESSION['mac_key']=$mac_key;
	$_SESSION['userid']=$customer_id;
	$_SESSION['invoiceId']=$invoiceId;
	$_SESSION['description']=$description;
	$_SESSION['txn_ref'] = $referenceId;
	
    $postfields = array();
	$postfields['product_id'] = $product_id;
    $postfields['cust_id'] = $customer_id;
	$postfields['cust_name'] = $firstname.' '.$lastname;
	$postfields['pay_item_id'] = $pay_item_id;
	$amount_posted=$postfields['amount'] = $amount;
	$postfields['currency'] = 566;
	$site_redirect_url=$postfields['site_redirect_url'] = $systemUrl.'/modules/gateways/callback/' . $moduleName . '.php';
    $txnref=$postfields['txn_ref'] = $referenceId;
   	$postfields['pay_item_name'] = $description;
   	$postfields['site_name'] = $companyName; 
	$postfields['channel_provider'] = $companyName; 
   
	  //  $hashv  = $txn_ref . $product_id . "101" . $amount . $site_redirect_url . $mac;
	
	$hashv  = $txnref . $product_id . $pay_item_id . $amount_posted . $site_redirect_url . $mac_key;// concatenate the strings for hash again
	$thash = hash('sha512',$hashv); 
	
	$postfields['hash'] = $thash;
	
    $htmlOutput = '<form method="post" action="' . $url . '">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
    }
	
    $htmlOutput .= '<button type="submit" class="btn btn-primary">' . $langPayNow . '</button><br />';
	$htmlOutput .='<img src="'.$systemUrl . '/modules/gateways/' . $moduleName . '/interswitch_logo.png" width="120" />';
	
	$htmlOutput .= '<input type="submit" name="Requery" class="btn btn-primary" value="Confirm Transaction" />';
	$htmlOutput .= '</form>';
    return $htmlOutput;
	
	
}