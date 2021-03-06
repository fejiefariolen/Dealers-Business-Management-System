<?php
session_start();
include '../db/connection.php';

// generate id
$letter = 'CLT';
function numberletter() 
{
          $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
          srand((double)microtime()*1000000);
          $i = 0;
          $passii = '' ;
          while ($i <= 8) {
            $num = rand() % 33;
            $tmp = substr($chars, $num, 1);
            $passii = $passii . $tmp;
            $i++;
          }
          return $passii;
}
$ccnumbers = numberletter();
$confirmation = $letter.'-'.$ccnumbers;

// ACTIVITY LOGS
  $activity  = 'SALES INVOICE CREATED: '.$_POST['engine2'].' ';
  $insertact = $dbConn->query("INSERT INTO logs (activity,user_id) VALUES ('".$activity."','".$_SESSION['username']."')");  

// INSERT SOLD STOCKS
$insertsolditems = $dbConn->query("INSERT INTO sold_items (customer_id,type,category_name,model_name,engine,chassis,note,transid) VALUES ('".$_POST['customer_id']."','terms','".$_POST['category_name']."','".$_POST['model_name']."','".$_POST['engine2']."','".$_POST['chassis2']."','".$_POST['note']."','".$confirmation."')");

//GETTING CURRENT SETTINGS

$getcurrent = $dbConn->query("SELECT * FROM settings");
$discurrent = $getcurrent->fetch(PDO::FETCH_ASSOC);

// GETTING PRICE OF THE STOCKS 
$getprice = $dbConn->query("SELECT * FROM stocks where engine_number = '".$_POST['engine2']."' and chassis = '".$_POST['chassis2']."' ");
$disprice = $getprice->fetch(PDO::FETCH_ASSOC);

// GETTING DETAILS FOR THE CUSTOMER
$getfullname = $dbConn->query("SELECT * FROM customerlists where customerid = '".$_POST['customer_id']."' ");
$display = $getfullname->fetch(PDO::FETCH_ASSOC);

//INSERT TRANSACTIONS

$inserttrans = $dbConn->query("INSERT INTO transaction (trans_id,customerid,model_id,datepayment,total_paid,amount,user_id,branch)  VALUES ('".$confirmation."','".$_POST['customer_id']."','".$disprice['model_id']."','".date("Y-m-d")."','".$_POST['downpayment']."','".$_POST['downpayment']."','".$_SESSION['username']."','".$_SESSION['branch']."') ");

// computations

$contract_price = ($disprice['price']) * (1 + ($discurrent['monthly_rate'] * $_POST['terms1'])) / $_POST['terms1'] * $_POST['terms1'];

$contract_balance = $contract_price - $_POST['downpayment'];

$monthly_installment = $contract_balance / $_POST['terms1'];

// UPDATE ACCOUNTS 

$updateaccounts = $dbConn->query("UPDATE accounts SET model_id = '".$disprice['model_id']."',status = 'current',monthly_installment = '".$monthly_installment."',totalpaid = '".$_POST['downpayment']."',contract_price = '".$contract_price."',datepayment = '".date("Y-m-d")."',terms = '".$_POST['terms1']."',downpayment = '".$_POST['downpayment']."' where account_id = '".$_POST['customer_id']."'");
//UPDATE STOCKS

$updatestocks = $dbConn->query("UPDATE stocks SET status = 'sold' where model_id = '".$disprice['model_id']."' ");

// //CHECK PAYMENTS
// $checkaccount = $dbConn->query("SELECT * FROM accounts where terms  == months");
// if () {
//   # code...
// }
function convert_number_to_words($number) {
    
    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
    $negative    = 'negative ';
    $decimal     = ' point ';
    $dictionary  = array(
        0                   => 'zero',
        1                   => 'one',
        2                   => 'two',
        3                   => 'three',
        4                   => 'four',
        5                   => 'five',
        6                   => 'six',
        7                   => 'seven',
        8                   => 'eight',
        9                   => 'nine',
        10                  => 'ten',
        11                  => 'eleven',
        12                  => 'twelve',
        13                  => 'thirteen',
        14                  => 'fourteen',
        15                  => 'fifteen',
        16                  => 'sixteen',
        17                  => 'seventeen',
        18                  => 'eighteen',
        19                  => 'nineteen',
        20                  => 'twenty',
        30                  => 'thirty',
        40                  => 'fourty',
        50                  => 'fifty',
        60                  => 'sixty',
        70                  => 'seventy',
        80                  => 'eighty',
        90                  => 'ninety',
        100                 => 'hundred',
        1000                => 'thousand',
        1000000             => 'million',
        1000000000          => 'billion',
        1000000000000       => 'trillion',
        1000000000000000    => 'quadrillion',
        1000000000000000000 => 'quintillion'
    );
    
    if (!is_numeric($number)) {
        return false;
    }
    
    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        trigger_error(
            'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
            E_USER_WARNING
        );
        return false;
    }

    if ($number < 0) {
        return $negative . convert_number_to_words(abs($number));
    }
    
    $string = $fraction = null;
    
    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }
    
    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= $hyphen . $dictionary[$units];
            }
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . convert_number_to_words($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= convert_number_to_words($remainder);
            }
            break;
    }
    
    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $number) {
            $words[] = $dictionary[$number];
        }
        $string .= implode(' ', $words);
    }
    
    return $string;
}
//CONDITION FOR INSERT SOLD ITEMS
if ($insertsolditems) 
		{
      ?>
      <div class="row animated fadeInDown">
<div class="col-lg-12">
            <div class="widget-container fluid-height clearfix">
              <div class="heading">
                <center><a onclick="javascript:window.print();" class="btn btn-lg btn-teal hidden-print">
                  Print <i class="fa fa-print"></i>
                </a></center>
              </div>
              <br>
              <div class="widget-content padded clearfix">
              <div class="col-md-4">
                <table border="1" width="330px" height="100%" >
                  <th colspan="2">In settlement of the following</th>
                  
                  <tbody >
                    <tr>
                    <td>Invoice No.</td>
                    <td>Amount</td>
                    </tr>
                    <tr>
                    <td></td>
                    <td></td>
                    </tr>
                    <tr>
                    <td><?php echo $disprice['model_name']." ".$disprice['category_name']?>
                    <br>Engine: <?php echo $_POST['engine2'];?><br>
                    Chassis: <?php echo $_POST['chassis2'];?></td>
                    <td><?php echo number_format($_POST['downpayment'],2);?></td>
                    </tr>
                   
                    <tr>
                    <td>Total Payment</td>
                    <td><?php echo number_format($_POST['downpayment'],2);?></td>
                    </tr>
                   
                    
                  </tbody>
                </table>
                <br>
                <br>
                <br>
                <table>
                  <tr><td><b>Account ID: <?php echo $_POST['customer_id']; ?></b></td></tr>
                  <tr><td></td></tr>
                  <tr><td><u><b>NOTE: This Receipt is not valid for claim of input taxes. This official receipt shall be valid until May 11,2020.</b></u></td></tr>
                  <tr>
                  <td><br></td>
                  </tr>
                  <tr>
                  <td>Track your accounts here : <b>www.cltmotorbikes.com.ph/track</b></td>
                  </tr> 
                </table>
              </div>
              <div class="col-md-8">
             
              <table width="100%" height="100%" cellpadding="12">
                <tr>
                  <td>
                  <img src="images/logo.png">
            </td>
            <td><h3>CLT MOTORBIKES</h3>Operated by: CLT motorbikes INC<br>Door FJ Northpoint Bussiness Center M.C. Briones St. Highway. Maguikay, Mandaue City<br>TIN: 12398-12312-123-123- VAT EXEMPT<br>TEL. Nos: 345-1234/495-123123</td>
                </tr>
                <tr>
                <td style="color:red;">NO: <?php echo $confirmation;?></td>
                  <td></td>
          </tr>
          <tr>
            <td>OFFICIAL RECEIPT</td>
            <td>Date: <?php echo date("m-d-Y");?></td>
          </tr>
          <tr>
            <td colspan="2"><pre>RECEIVED from    <b><?php echo $display['firstname']." ". $display['lastname'];?></b>      with TIN: <?php echo $display['tin'];?> <b></b>
            <br>and address of  <b><?php echo $display['address'];?></b>      engaged in the      
            <br>business style of     , the sum of <b><?php echo convert_number_to_words($_POST['downpayment']);?></b>
            <br><b> pesos only </b> 
            <br><b>(P <?php echo number_format($_POST['downpayment'],2);?>)</b> in partial/ fullpayment for</pre></td>
          </tr>
           <tr>
            <td colspan="2">___<u><?php echo $_SESSION['fullname'];?></u>____</td>
            
          </tr>
          <tr>
            <td colspan="2">Authorized Signature</td>
          </tr> 
              </table>
              </div>
              </div>
            </div>
          </div>
          </div>

   
<div class="row"></div>
<?php
		}
		else
		{
			echo '<div class="alert alert-danger alert-dismissable">
                      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                      <strong>Something Wrong :</strong>
                    </div>' ;
			//header("location:../?display=settings");
		}

?>