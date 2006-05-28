<?php

$result = mysql_query("SELECT * FROM archives");
if (mysql_num_rows($result)>0) {
	$Page['body'] .= "<dl>";
	while ($row = mysql_fetch_assoc($result)) {
		$Page['body'] .= "<dt><a href=''>".$row['name']."</a></dt>".
			"<dd>".$row['description']."</dd>";
	}
	$Page['body'] .= "</dl>";
}

?>