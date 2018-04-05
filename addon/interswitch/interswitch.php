<?php

#############################################
#
#  Interswitch Transaction Addon Module
# @ Version  : 1.0
# @ Author   : Victor Tin
# @ Release  : 2018-03-28
# @ Website  : http://www.naijadomains.com
#
#############################################


if (!defined("WHMCS")) {
  exit("This file cannot be accessed directly");
}
use WHMCS\Database\Capsule;

function getClientDetail($clientID){
	$results=array();
	foreach (Capsule::table('tblclients')->where('id', $clientID)->get() as $col_name=>$dat_val) {
    $results[$col_name]=$dat_val;
}

	return $results;
}
function getOrderDetails($invoiceId){
	return Capsule::table('tblorders')->where('invoiceid', $invoiceId)->get();
}
function interswitch_config() {
    $configarray = array(
    "name" => "Interswitch Transaction",
    "description" => "List all transaction from interswitch",
    "version" => "1.0",
    "author" => "Naija Domains",
    "fields" => array(
        "product_id" => array ("FriendlyName" => "Product ID", "Type" => "text", "Size" => "25",
                              "Description" => "Textbox", "Default" => "6205", ),

        "requery_url" => array ("FriendlyName" => "Requery Url", "Type" => "text", "Size" => "25",
                              "Description" => "Interswitch text url", ),
        "mac_key" => array ("FriendlyName" => "Mack Key", "Type" => "text","text", "Size" => "25",
							"Description" => "Interswitch Mac key", ),
    ));
    return $configarray;
}

function interswitch_activate() {
   # Create Custom DB Table
  $query = "CREATE TABLE IF NOT EXISTS `tbl_interswitch` (
  `id` int(10) NOT NULL,
  `userid` int(10) NOT NULL,
  `currency` int(10) NOT NULL,
  `gateway` text NOT NULL,
  `date` datetime DEFAULT NULL,
  `description` text NOT NULL,
  `amountin` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fees` decimal(10,2) NOT NULL DEFAULT '0.00',
  `amountout` decimal(10,2) NOT NULL DEFAULT '0.00',
  `rate` decimal(10,5) NOT NULL DEFAULT '1.00000',
  `transid` text NOT NULL,
  `invoiceid` int(10) NOT NULL DEFAULT '0',
  `status` ENUM('success', 'fail')
) ENGINE=MyISAM DEFAULT CHARSET=latin1; ";
  $result =Capsule::select($query);
    # Return Result
	 if($result){
    return array('status'=>'success','description'=>'Interswithc Module activated, This module helps you to check Transaction from interswitch payment gateway and also requery to check transaction status');
	 }else{
    return array('status'=>'error','description'=>'There was a problem activating interswitch module ');
	 }
    

}

function interswitch_deactivate() {

    # Return Result
    return array('status'=>'success','description'=>'Interswithc Module deactivated successful');
    return array('status'=>'error','description'=>'There was a problem deactivating interswitch module ');
    
}
function interswitch_output($vars) {

    $modulelink = $vars['modulelink'];
    $product_id = $vars['product_id'];
    $requery_url = $vars['requery_url'];
    $mac_key = $vars['mac_key'];
    $LANG = $vars['_lang'];
	$requeryLink=$modulelink.'&action=requery';
	
		// list transactions
	
	$totalCount=Capsule::table('tbl_interswitch')->select('id')->count();
	
	// number of rows to show per page
$per_page = 10;
// find out total pages
$totalpages = ceil($totalCount / $per_page);
  
// get the current page or set a default
if (isset($_GET['p']) && is_numeric($_GET['p'])) {
   // cast var as int
   $currentpage = (int) $_GET['p'];
} else {
   // default page num
   $currentpage = 1;
} // end if	
	
// if current page is greater than total pages...
if ($currentpage > $totalpages) {
   // set current page to last page
   $currentpage = $totalpages;
} // end if
// if current page is less than first page...
if ($currentpage < 1) {
   // set current page to first page
   $currentpage = 1;
} // end if

// the offset of the list, based on current page 
$offset = ($currentpage - 1) * $per_page;
	
	
if(!isset($_GET['action'])):
	$sql="select * from tbl_interswitch ORDER BY date desc LIMIT ".$offset.",".$per_page."";

	$invoices = Capsule::select($sql);
	echo '<div class="tablebg">
	<table width="100%" border="0" cellpadding="3" cellspacing="0"><tbody><tr>
<td width="50%" align="left">'.$totalCount.' Records Found, Page '.$currentpage.' of '.$totalpages.'</td>
</tr></tbody></table>


<table id="sortabletbl1" class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
<tbody><tr><th>Client Name</th><th>Date</th><th>Gateway</th><th>Description</th><th>Amount In</th><th>Trans ID</th><th>Invoice ID </th> <th>Status</th><th>Action </th></tr>';
	foreach ($invoices as $invoice) {
		echo '<tr><td><a href="clientssummary.php?userid='.$invoice->userid.'">'.getClientDetail($invoice->userid)[0]->firstname.' '.getClientDetail($invoice->userid)[0]->lastname.'</a></td><td>'.$invoice->date.'</td><td>'.ucwords($invoice->gateway).'</td><td>'.$invoice->description.'</td><td>'.$invoice->amountin.'</td><td>'.$invoice->transid.'</td><td><a href="invoices.php?action=edit&id='.$invoice->invoiceid.'">'.$invoice->invoiceid.'</a></td><td>'.$invoice->status.'</td><td><form action="'.$requeryLink.'" method="post"><input type="hidden" name="txn_ref" value="'.$invoice->transid.'"/><input type="hidden" name="amount" value="'.$invoice->amountin.'"/><input type="submit" name="Requery" class="btn btn-primary" value="Requery"/></form><a href="'.$modulelink.'&action=delete&id='.$invoice->id.'" class="btn btn-primary">Delete</a></td></tr>';
		//print_r(getClientDetail($invoice->userid));
		
		//print_r($invoice);
	}
echo'</tbody></table>';
	
 echo "<ul class='pager'>";
// if not on page 1, don't show back links
if ($currentpage > 1) {
   // get previous page num
   $prevpage = $currentpage - 1;
   // show < link to go back to 1 page
   echo "<li class='previous'><a href='{$modulelink}&p=$prevpage'>Previous</a></li>";
} // end if 

if ($currentpage != $totalpages) {
   // get next page
   $nextpage = $currentpage + 1;
    // echo forward link for next page 
   echo " <li class='previous'><a href='{$modulelink}&p=$nextpage'>Next</a><li>";
   
} 
echo '
</ul>
</div>';
	
	// perform requery action
	elseif(isset($_GET['action'])):
	// perform deletet action
	if($_GET['action']=='delete'&& isset($_GET['id'])){
			// make sure id is integer
		
		$id=(int)$_REQUEST['id'];
		
		//query db to delete
		try {
    Capsule::table('tbl_interswitch')
        ->where('id', '=', $id)
        ->delete();
	echo "<div class='successbox'>Transaction deleted</div>";
} catch(\Illuminate\Database\QueryException $ex){
			echo "<div class='infobox'>".$ex->getMessage()."</div>";
} catch (Exception $e) {
    echo "<div class='infobox'>".$e->getMessage()."</div>";
}
		
		 echo '<a href="'.$modulelink.'" class="btn btn-primary">Back to Interswitch Transaction</a>';
	}
	
	if($_GET['action']=='requery'&& isset($_POST['Requery'])){
		$subpdtid=$product_id;
		//$subpdtid = 6204;
		$mac=$mac_key;
	
		$submittedamt = round($_POST["amount"])*100;	//submitted amount (eg 8353349)
		$submittedref = $_POST["txn_ref"];	//submitted transaction reference (eg 250OID100000164)
	  
		
		//Calculate HASH
		//$mac = "D3D1D05AFE42AD50818167EAC73C109168A0F108F32645C8B59E897FA930DA44F9230910DAC9E20641823799A107A02068F7BC0F4CC41D2952E249552255710F" ; // the mac key sent to you. Always constant.
		$string_to_hash = $subpdtid.$submittedref.$mac;  // concatenate the strings ("Prod_ID"."txn_ref"."mac") for hash again
        $hash = hash('sha512',$string_to_hash); 	//hash to be passed in header
		
		$query_elements = array(
			"productid"=>$subpdtid,
			"transactionreference"=>$submittedref,
			"amount"=>$submittedamt
		);
		$link_query_values = http_build_query($query_elements);
		
		$url =  $requery_url. $link_query_values; // json
		
		//header details. Put hash here
		$headers = array(
						"GET /HTTP/1.1",
						"Host: webpay.interswitchng.com",
						"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1",
						//"Content-type:  multipart/form-data",
						//"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8", 
						"Accept-Language: en-us,en;q=0.5",
						//"Accept-Encoding: gzip,deflate",
						//"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
						"Keep-Alive: 300",
						"Connection: keep-alive",
						"Hash: $hash"	//hash value
                    );        
	   //print_r2($headers);
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
		if (curl_errno($ch)) 
		{ 
			print "Error: " . curl_error($ch) . "</br></br>";
			
			$errno = curl_errno($ch);
			$error_message = curl_strerror($errno);
			print $error_message . "</br></br>";;
			
			//print_r($headers);
		}
		else 
		{  
			// Show me the result
			$json = json_decode($data, TRUE);
				   
			curl_close($ch);    //END CURL SESSION///////////////////////////////
			
		
			// Display Array Elements///////////////
			echo "Transaction Amount: ".($submittedamt/100)."</br>";
			echo "Card Number: ".$json["CardNumber"]."</br>";
			echo "Transaction Reference: ".$submittedref."</br>";
			echo "Payment Reference: ".$json["PaymentReference"]."</br>";
			echo "Retrieval Reference Number: ".$json["RetrievalReferenceNumber"]."</br>";
			echo "Lead Bank CBN Code:".$json["LeadBankCbnCode"]."</br>";
			//echo "Lead Bank Name: ".$json["LeadBankName"]."</br>";
			//echo "Split Accounts: ".$json["SplitAccounts"]."</br>";
			echo "Transaction Date: ".$json["TransactionDate"]."</br>";
			echo "Response Code: ".$json["ResponseCode"]."</br>";
			echo "Response Description: ".$json["ResponseDescription"]."</br>";	
			// //////Display Array Elements////////////
		}
                echo '<a href="'.$modulelink.'" class="btn btn-primary">Back to Interswitch Transaction</a>';
		session_write_close();
	}
	endif;
	
	
}