<?php
require_once 'config.php';
$page->setTitle('Bookkeeping');
$page->addBodyContent(getSummaryTable('payment').getSummaryTable('receipt'));
$page->display();

function getSummaryTable($transaction_type) {

    $cats = get_bookkeeping_cats($transaction_type);
    $out = '<table class="bookkeeping-journal"><caption>Summary of All '.ucfirst($transaction_type).'s</caption><tr class="tophead"><th></th>';
    foreach ($cats as $cat) {
    	$out .= "<th>$cat</th>";
    }
    $out .= "<th>Totals</th></tr>";
    $sql = "SELECT MONTHNAME(date) AS month, MONTH(date) AS nmonth, YEAR(date) AS year, SUM(amount) AS total
    	FROM bookkeeping WHERE transaction_type='$transaction_type' GROUP BY MONTHNAME(date), YEAR(date) ORDER BY date DESC";
    $res = mysql_query($sql);
    while ($month = mysql_fetch_assoc($res)) {
	    $year = $month['year'];
        $out .= "<tr><td><a href='bookkeeping_journal.php?transaction_type=$transaction_type&y=$year&m=".$month['nmonth']."'>".$month['month']." $year</a></td>";
        foreach ($cats as $cat) {
            $sql = "SELECT date, category, SUM(amount) AS total FROM bookkeeping
                WHERE transaction_type='$transaction_type' AND MONTHNAME(date)='".$month['month']."' AND YEAR(date)='".$month['year']."'
                AND category='$cat' GROUP BY category";
            $cattotal = mysql_fetch_assoc(mysql_query($sql));
            if (isset($cattotal['total'])) $total = $cattotal['total'];
            else $total = "0.00";
            $out .= "<td>$total</td>";
        }
        $out .= "<td>".$month['total']."</td></tr>";
    }
    $out .= "<tr class='last-row'><td colspan='".(count($cats)+2)."'></td></tr></table>";
    return $out;
}

function get_bookkeeping_cats($transaction_type) {
	$sql = "SELECT category FROM bookkeeping WHERE transaction_type='$transaction_type' GROUP BY category";
	$r = mysql_query($sql);
	while ($cat = mysql_fetch_assoc($r)) {
	    $cats[] = $cat['category'];
	}
	return $cats;
}

?>