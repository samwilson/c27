<?php
require_once 'common.php';
$page->setTitle('Bookkeeping Journal');

if (isset($_POST['save'])) {
    $id = $db->save('bookkeeping_journal', $_POST);
}

if (isset($_GET['transaction_type'])) {
	$transaction_type=$_GET['transaction_type'];
} else {
	$transaction_type = 'payment';
	$_GET['transaction_type'] = $transaction_type;
}
if (!isset($_GET['y'])) $curr_year=date('Y');  else $curr_year=$_GET['y'];
if (!isset($_GET['m'])) $curr_month=date('m'); else $curr_month=$_GET['m'];

$title = ucfirst($transaction_type).' Journal for '.date('F Y',strtotime($curr_year.'-'.$curr_month));
$page->setTitle($title);
$page->addBodyContent(getJournalHeader($curr_year, $curr_month).getJournalTable($transaction_type, $curr_year, $curr_month));

if (isset($_GET['print'])) {
	$page->setBody("<h1>$title</h1>".getJournalTable($transaction_type, $curr_year, $curr_month));
}

$page->display();

/*******************************************************************************
Functions only below here
*******************************************************************************/

function getJournalHeader($curr_year, $curr_month) {
    $next_year = $curr_year+1;
    $prev_year = $curr_year-1;
    
    // Build labels
    if ($curr_month>6) {
        $prev_label = substr($curr_year-1,2).'/'.substr($curr_year,2);
        $curr_label = substr($curr_year,2).'/'.substr($curr_year+1,2);
        $next_label = substr($curr_year+1,2).'/'.substr($curr_year+2,2);
    } else {
        $prev_label = substr($curr_year-2,2).'/'.substr($curr_year-1,2);
        $curr_label = substr($curr_year-1,2).'/'.substr($curr_year,2);
        $next_label = substr($curr_year,2).'/'.substr($curr_year+1,2);
    }

    $base_uri = $_SERVER['PHP_SELF']."?transaction_type=".$_GET['transaction_type'];
    $months = array(7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec',1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun');
    $out = "<div class='bookkeeping-journal-nav'>
        <p>
        <span class='prev'><a href='$base_uri&y=$prev_year&m=$curr_month'>&laquo; $prev_label</a></span>
        <strong class='curr'>$curr_label ".$months[$curr_month*1]."</strong> <a href='$base_uri&y=$curr_year&m=$curr_month&print' class='quiet'>[print]</a>
        <span class='next'><a href='$base_uri&y=$next_year&m=$curr_month'>$next_label &raquo;</a></span>
        </p>";
    $out .= "<ol class=''>";
    foreach ($months as $month_num=>$month_name) {
        if ($curr_month>6  && $month_num>6) $y=$curr_year;
        if ($curr_month<=6 && $month_num>6) $y=$curr_year-1;
        if ($curr_month>6 && $month_num<=6) $y=$curr_year+1;
        if ($curr_month<=6 && $month_num<=6) $y=$curr_year;
        if ($curr_month==$month_num) $out .= "<li class='curr'><a>$month_name</a></li>";
        else $out .= "<li><a href='$base_uri&m=$month_num&y=$y'>$month_name</a></li>";
    }
    $out .= "</ol></div>";
    return($out);
} // end _bookkeeping_get_journal_header()

function getJournalTable($transaction_type, $curr_year, $curr_month) {
	global $db;
        
    // Categories
    $cats = get_bookkeeping_cats($transaction_type);
    $cat_headers = "";
    foreach ($cats as $cat) {
    	$cat_headers .= "<th>$cat</th>";
    }

	if ($transaction_type=='payment') {
		$receipt_or_invoice = 'Receipt?';
	} else {
		$receipt_or_invoice = 'Invoice?';
	}
    // Journal table
    $table = "<tr class='tophead'><th>Date</th><th>Comments</th>$cat_headers<th>Method</th><th>$receipt_or_invoice</th>
        <th colspan='2'>Business Use Component</th></tr>";
    $sql = "SELECT * FROM bookkeeping_journal
                     WHERE transaction_type='$transaction_type' AND MONTH(date)='$curr_month' AND YEAR(date)='$curr_year'
                     ORDER BY date ASC";
    $res = mysql_query($sql);
    if ($res) {
		while ($row = mysql_fetch_assoc($res)) {
			if ($row['has_receipt_or_invoice']==1) {
				$row['has_receipt_or_invoice'] = 'Yes';
			} else {
				$row['has_receipt_or_invoice'] = 'No';
			}
			$table .= "<tr><td>".date('jS',strtotime($row['date']))."</td><td>".stripslashes($row['comments'])."</td>";
			foreach ($cats as $cat) {
				if ($row['category']==$cat) $table .= "<td>".$row['amount']."</td>";
				else $table .= "<td></td>";
			}
			$table .= "<td>".$row['method']."</td>
					   <td>".$row['has_receipt_or_invoice']."</td>
					   <td>".(100-$row['private_use_component'])."%</td>
					   <td>".number_format((1-($row['private_use_component']/100))*$row['amount'], 2)."</td>
					   </tr>";
		}
	}
    // Column totals:
    $table .= "<tr class='bottomhead'><th colspan='2'>Totals:</th>";
    foreach ($cats as $cat) {
        $sql = "SELECT date, category, SUM(amount) AS total FROM bookkeeping_journal
            WHERE transaction_type='$transaction_type' AND MONTH(date)='$curr_month' AND YEAR(date)='$curr_year'
            AND category='$cat' GROUP BY category";
        $cattotal = mysql_fetch_assoc(mysql_query($sql));
        $cattotal = (isset($cattotal)) ? $cattotal['total'] : '0.00';
        $table .= "<th>$cattotal</th>";
    }
    $total_business_use = '';
    $res = mysql_query("SELECT SUM((1-(private_use_component/100))*amount) AS total FROM bookkeeping_journal
            WHERE transaction_type='$transaction_type' AND MONTH(date)='$curr_month' AND YEAR(date)='$curr_year'");
    if ($res) {
		$total_business_use = mysql_fetch_assoc($res);
		$total_business_use = number_format($total_business_use['total'],2);
	}
    $table .= "<th></th><th></th><th></th><th>$total_business_use</th></tr>";

    $cat_colspan = round(count($cats)/2);
    $amt_colspan = count($cats) - $cat_colspan;
    $out = ("<form method='post' action='?transaction_type=$transaction_type&m=$curr_month&y=$curr_year#form'>".
        "<table class='bookkeeping-journal'>$table
        <tr class=''><th colspan='2'></th>$cat_headers<th colspan='4'></th></tr>");
    if (!isset($_GET['print'])) {
	    $out .= ("
        <tr class='tophead' style='background-color:lightgreen'>
            <th>Date</th>
            <th>Comments</th>
            <th colspan='$cat_colspan'>Category</th>
            <th colspan='$amt_colspan'>Amount</th>
            <th>Method</th>
            <th>$receipt_or_invoice</th>
            <th colspan='2'>Private Use Component (%)</th>
        </tr>
        <tr style='background-color:lightgreen' class='last-row'>
            <td><a name='form'></a><input type='text' name='date' id='give-me-focus' size='10' value='".date('Y-m-d')."' /></td>
            <td><input type='text' name='comments' size='1' /></td>
            <td colspan='$cat_colspan'><input type='text' name='category' size='1' /></td>
            <td colspan='$amt_colspan'><input type='text' name='amount' size='1' /></td>
            <td><input type='text' name='method' size='1' value='Cash' /></td>
            <td style='text-align:center'><input class='checkbox' type='checkbox' size='1' name='has_receipt_or_invoice'/></td>
            <td colspan='2'><input type='text' name='private_use_component' size='1' value='100' /></td>
        </tr>
        </table>
        <p class='submit'>
        	<input type='hidden' name='transaction_type' value='$transaction_type' />
        	<input type='submit' name='save' value='Add &raquo;' />
        </p>
        </form>");
    }
	return $out;
}

function get_bookkeeping_cats($transaction_type) {
	$cats = array();
	$sql = "SELECT category FROM bookkeeping_journal WHERE transaction_type='$transaction_type' GROUP BY category";
	$r = mysql_query($sql);
	if ($r) {
		while ($cat = mysql_fetch_assoc($r)) {
			$cats[] = $cat['category'];
		}
	}
	return $cats;
}
?>