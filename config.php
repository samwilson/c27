<?php
/* $Id$ */

$Config['sitename'] = "The Silly Room.";
$Config['dbuser']   = "root";
$Config['dbpass']   = "";
$Config['dbhost']   = "localhost";
$Config['dbname']   = "thearchiveroom";

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
