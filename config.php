<?php
/* $Id$ */

$Config['sitename'] = "Channel 27";
$Config['dbuser']   = "";
$Config['dbpass']   = "";
$Config['dbhost']   = "localhost";
$Config['dbname']   = "channel27";

//----------------------------------------//
//  Don't edit anything below here.       //
//----------------------------------------//
$conn = mysql_connect($Config['dbhost'], $Config['dbuser'], $Config['dbpass']);
if (!$conn) {
   echo "ERROR: Unable to connect to DB: " . mysql_error();
   exit;
}
if (!mysql_select_db($Config['dbname'])) {
   echo "ERROR: Unable to select database <em>$dbname</em>: " . mysql_error();
   exit;
}
?>
