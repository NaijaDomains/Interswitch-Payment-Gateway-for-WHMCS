<?php
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

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

use WHMCS\Database\Capsule;
// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

//$subpdtid = 6204; // collegpay your product ID
$subpdtid = $_SESSION["product_id"]; // your product ID
$submittedamt = $_SESSION["amount"];
$submittedref = $_SESSION['txn_ref'];
$nhash=$_SESSION['mac_key'];
        //CP $nhash = "E187B1191265B18338B5DEBAF9F38FEC37B170FF582D4666DAB1F098304D5EE7F3BE15540461FE92F1D40332FDBBA34579034EE2AC78B1A1B8D9A321974025C4" ; // the mac key sent to you
        $hashv = $subpdtid.$submittedref.$nhash;  // concatenate the strings for hash again
$thash = hash('sha512',$hashv); 

$parami = array(
        "productid"=>$subpdtid,
        "transactionreference"=>$submittedref,
        "amount"=>$submittedamt
);
$payparams = http_build_query($parami);

$url = "https://webpay.interswitchng.com/paydirect/api/v1/gettransaction.json?". $payparams; // json
//FROM OUTSIUDE (NOTE SSL) = "https://stageserv.interswitchng.com/test_paydirect/api/v1/gettransaction.json?$ponmo"; // json
//stageserv.interswitchng.com stageserv.interswitchng.com note the variables appended to the url as get values for these parameters

$headers = array(
        "GET /HTTP/1.1",
        "Host:webpay.interswitchng.com",
        "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1",
        //"Content-type:  multipart/form-data",
        //"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8", 
        "Accept-Language: en-us,en;q=0.5",
        //"Accept-Encoding: gzip,deflate",
        //"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
        "Keep-Alive: 300",
        "Connection: keep-alive",
        "Hash: " . $thash
    );        

$ch = curl_init();  //INITIALIZE CURL///////////////////////////////
//               
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 
curl_setopt($ch, CURLOPT_POST, false );
//
$data = curl_exec($ch);  //EXECUTE CURL STATEMENT///////////////////////////////
$json = null;

$Invoiceid=explode("-",$submittedref);
$amount = floatval($submittedamt)/100;


if (curl_errno($ch)) 
{ 
        print "Error: " . curl_error($ch) . "</br></br>";

        $errno = curl_errno($ch);
        $error_message = curl_strerror($errno);
        print $error_message . "</br></br>";;

        print_r($headers);

}
else 
{  
        // Show me the result
        $json = json_decode($data, TRUE);

        curl_close($ch);    //END CURL SESSION///////////////////////////////
	
//print_r($json);
	 if($json['ResponseCode']=="Z0" || $json['ResponseCode']=="Z1"){
	     
	     	try {
    Capsule::connection()->transaction(
        function ($connectionManager)
        {
            /** @var \Illuminate\Database\Connection $connectionManager */
            $connectionManager->table('tbl_interswitch')->insert(
                [
                    'userid' => $_SESSION['userid'],
                    'gateway' => 'interswitch',
                    'description' => $_SESSION['description'],
				'amountin' => ($_SESSION["amount"]/100),
				'transid' => $_SESSION['txn_ref'],
				'invoiceid' => $_SESSION['invoiceId'],
				'status' => 'failed',
                ]
            );
        }
    );
} catch (\Exception $e) {
    echo "Uh oh! Inserting didn't work, but I was able to rollback. {$e->getMessage()}";
}?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
     <meta http-equiv="content-type" content="text/html; charset=utf-8" />  
  <meta name="description" content="Web Hosting Services in Nigeria, Business Hosting Provider, VPS Hosting, Dedicated Servers and Shared Hosting, Web Design Premium WordPress Themes"/>

    <title>Invoice #<?php echo $invoiceId;?> - NaijaDomains.com</title>    <!-- Bootstrap -->
<link href="/clients/assets/css/bootstrap.min.css" rel="stylesheet">

<style type="text/css">
	body {    background-color: #efefef;
}
.invoice-container {
    margin: 15px auto;
    padding: 70px;
    max-width: 850px;
    background-color: #fff;
    border: 1px solid #ccc;
    -moz-border-radius: 6px;
    -webkit-border-radius: 6px;
    -o-border-radius: 6px;
    border-radius: 6px;
}
</style>
</head>
<body>
<div class="container-fluid invoice-container">
	<div class="panel panel-danger">
            <div class="panel-heading">
            <h3 class="panel-title"><strong>Error</strong></h3>
        </div>
                <div class="panel-body text-center">
            Your transaction was not successful. 
            <br> Reason:<b><?php echo $json['ResponseDescription'];?></b> 
            <br> Response Code:  <b><?php echo $json['ResponseCode'];?></b> 
            <br> Reference No: <?php echo $submittedref;?>
            
					<br> Invoice Number: <b>#<?php echo $Invoiceid['1'];?></b>
					<br />Total Amount : <b>N <?php echo format_as_currency($submittedamt/100);?></b>
              
             <br>
            <a href="<?php echo $gatewayParams['systemurl'];?>viewinvoice.php?id=<?php echo $Invoiceid['1'];?>" class="btn btn-primary">Return to Invoice</a> <a href="<?php echo $gatewayParams['systemurl'];?>clientarea.php" class="btn btn-primary">Back to Client Area</a>
            
        </div>
        </div>
	</div>
	</body>
</html>
<?php

	     //header("location:".$gatewayParams['systemurl']."viewinvoice.php?id=".$_SESSION['invoiceId']."&paymentfailed=true");
	 };
	
	if($json["ResponseCode"]==00||$json["ResponseCode"]==10||$json["ResponseCode"]==11||$json["ResponseCode"]==16){
		
	/**
     * Validate Callback Invoice ID.
     *
     * Checks invoice ID is a valid invoice number.
     *
     * Performs a die upon encountering an invalid Invoice ID.
     *
     * Returns a normalised invoice ID.
     */
    $invoiceId = checkCbInvoiceID($Invoiceid['1'], $gatewayModuleName);
		
	/**
     * Check Callback Transaction ID.
     *
     * Performs a check for any existing transactions with the same given
     * transaction number.
     *
     * Performs a die upon encountering a duplicate.
     */
    checkCbTransID($json["MerchantReference"]);
		
    if ($gatewayParams['convertto']) {
        $result = select_query("tblclients", "tblinvoices.invoicenum,tblclients.currency,tblcurrencies.code", array("tblinvoices.id" => $invoiceId), "", "", "", "tblinvoices ON tblinvoices.userid=tblclients.id INNER JOIN tblcurrencies ON tblcurrencies.id=tblclients.currency");
        $data = mysql_fetch_array($result);
        $invoice_currency_id = $data['currency'];
        $converto_amount = convertCurrency($amount, $gatewayParams['convertto'], $invoice_currency_id);
        $amount = format_as_currency($converto_amount);
    }
		
		/**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int $invoiceId         Invoice ID
     * @param string $transactionId  Transaction ID
     * @param float $paymentAmount   Amount paid (defaults to full balance)
     * @param float $paymentFee      Payment fee (optional)
     * @param string $gatewayModule  Gateway module name
     */
    addInvoicePayment($Invoiceid['1'], $json["MerchantReference"], $amount, 0, $gatewayModuleName);

		logTransaction($gatewayModuleName, $json, 'Transaction was successful');
		try {
    Capsule::connection()->transaction(
        function ($connectionManager)
        {
            /** @var \Illuminate\Database\Connection $connectionManager */
            $connectionManager->table('tbl_interswitch')->insert(
                [
                    'userid' => $_SESSION['userid'],
                    'gateway' => 'interswitch',
                    'description' => $_SESSION['description'],
				'amountin' => ($_SESSION["amount"]/100),
				'transid' => $_SESSION['txn_ref'],
				'invoiceid' => $_SESSION['invoiceId'],
				'status' => 'success',
                ]
            );
        }
    );
} catch (\Exception $e) {
    echo "Uh oh! Inserting didn't work, but I was able to rollback. {$e->getMessage()}";
}
		?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
     <meta http-equiv="content-type" content="text/html; charset=utf-8" />  
  <meta name="description" content="Web Hosting Services in Nigeria, Business Hosting Provider, VPS Hosting, Dedicated Servers and Shared Hosting, Web Design Premium WordPress Themes"/>

    <title>Invoice #<?php echo $Invoiceid['1'];?> - NaijaDomains.com</title>    <!-- Bootstrap -->

    <!-- Bootstrap -->
<link href="/clients/assets/css/bootstrap.min.css" rel="stylesheet">

<style type="text/css">
	body {    background-color: #efefef;
}
.invoice-container {
    margin: 15px auto;
    padding: 70px;
    max-width: 850px;
    background-color: #fff;
    border: 1px solid #ccc;
    -moz-border-radius: 6px;
    -webkit-border-radius: 6px;
    -o-border-radius: 6px;
    border-radius: 6px;
}
</style>

</head>
<body>
<div class="container-fluid invoice-container">
	<div class="panel panel-success">
            <div class="panel-heading">
            <h3 class="panel-title"><strong>Successful</strong></h3>
        </div>
                <div class="panel-body text-center">
            Your transaction was successful. 
            <br> Reason:<b><?php echo $json['ResponseDescription'];?></b> 
            <br> Reference Number:  <b><b><?php echo $json['RetrievalReferenceNumber'];?></b> 
            <br> Reference No: <b><?php echo $json['PaymentReference'];?>
             <br> Transaction Date: <b><?php echo $json['TransactionDate'];?>
           <br> Invoice Number: <b>#<?php echo $Invoiceid['1'];?></b>
			<br />Total Amount : <b>N <?php echo format_as_currency($submittedamt/100);?></b>
              
             <br>
            <a href="<?php echo $gatewayParams['systemurl'];?>viewinvoice.php?id=<?php echo $Invoiceid['1'];?>" class="btn btn-primary">Return to Invoice</a> <a href="<?php echo $gatewayParams['systemurl'];?>clientarea.php" class="btn btn-primary">Back to Client Area</a>
        </div>
        </div>
	</div>
	</body>
</html>
        
	<?php	
		
		//header("location:".$gatewayParams['systemurl']."viewinvoice.php?id=".$InvoiceID."&paymentsuccess=true");
		//exit();
	}else{
		$output = "ResponseCode: " . $json['ResponseCode']
            . "\r\Description: " .  $json['ResponseDescription']
            . "\r\nStatus: Transaction Unsuccessful";
        logTransaction($gatewayModuleName, $json, $output);
		try {
    Capsule::connection()->transaction(
        function ($connectionManager)
        {
            /** @var \Illuminate\Database\Connection $connectionManager */
            $connectionManager->table('tbl_interswitch')->insert(
                [
                    'userid' => $_SESSION['userid'],
                    'gateway' => 'interswitch',
                    'description' => $_SESSION['description'],
				'amountin' => ($_SESSION["amount"]/100),
				'transid' => $_SESSION['txn_ref'],
				'invoiceid' => $_SESSION['invoiceId'],
				'status' => 'failed',
                ]
            );
        }
    );
} catch (\Exception $e) {
    echo "Uh oh! Inserting didn't work, but I was able to rollback. {$e->getMessage()}";
}
		?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
     <meta http-equiv="content-type" content="text/html; charset=utf-8" />  
  <meta name="description" content="Web Hosting Services in Nigeria, Business Hosting Provider, VPS Hosting, Dedicated Servers and Shared Hosting, Web Design Premium WordPress Themes"/>

    <title>Invoice #<?php echo $invoiceId;?> - NaijaDomains.com</title>    <!-- Bootstrap -->
<link href="/clients/assets/css/bootstrap.min.css" rel="stylesheet">

<style type="text/css">
	body {    background-color: #efefef;
}
.invoice-container {
    margin: 15px auto;
    padding: 70px;
    max-width: 850px;
    background-color: #fff;
    border: 1px solid #ccc;
    -moz-border-radius: 6px;
    -webkit-border-radius: 6px;
    -o-border-radius: 6px;
    border-radius: 6px;
}
</style>
</head>
<body>
<div class="container-fluid invoice-container">
	<div class="panel panel-danger">
            <div class="panel-heading">
            <h3 class="panel-title"><strong>Error</strong></h3>
        </div>
                <div class="panel-body text-center">
            Your transaction was not successful. 
            <br> Reason:<b><?php echo $json['ResponseDescription'];?></b> 
            <br> Response Code:  <b><?php echo $json['ResponseCode'];?></b> 
            <br> Reference No: <?php echo $submittedref;?>
            
					<br> Invoice Number: <b>#<?php echo $Invoiceid['1'];?></b>
					<br />Total Amount : <b>N <?php echo format_as_currency($submittedamt/100);?></b>
              
             <br>
            <a href="<?php echo $gatewayParams['systemurl'];?>viewinvoice.php?id=<?php echo $Invoiceid['1'];?>" class="btn btn-primary">Return to Invoice</a> <a href="<?php echo $gatewayParams['systemurl'];?>clientarea.php" class="btn btn-primary">Back to Client Area</a>
            
        </div>
        </div>
	</div>
	</body>
</html>
	<?php
		
		  //header("location:".$gatewayParams['systemurl']."viewinvoice.php?id=".$Invoiceid['1']."&paymentfailed=true");
    
	}
	

}
session_write_close();
?>