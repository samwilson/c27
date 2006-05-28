<?php

/**
 * Build variaous sorts of 'breadcrumb' navigation trails from a given node in
 * the hierarchy.  (Author: Sam Wilson, 2002.)
 */
function MakePath($page_id, $with_links = 1, $delim = " &raquo; ", $with_title = 1) {
	$sql = "SELECT * FROM pages WHERE id='".esc($page_id)."'";
	$result = mysql_query($sql);
	$row = mysql_fetch_assoc($result);
    $title = $row['title'];
    $PathTitleArray = array($row['title']);
    $PathIDArray = array($row['id']);
    $PathArray = array ();
    while ($row['id'] > 1) {
        $ParentID = $row['parent_id'];
        $sql = "SELECT * FROM pages WHERE id='".esc($row['parent_id'])."'";
        $row = mysql_query($sql);
        if ($row) {
        	$row = mysql_fetch_assoc($row);
			$ParentTitle = $row['title'];
			array_push ($PathTitleArray, $ParentTitle);
			array_push ($PathIDArray, $ParentID);
		}
    }
    for ($i=0; $i < count($PathTitleArray); $i++) {
    	if ($with_links) {
	        array_unshift ($PathArray, "<a href='index.php?id=$PathIDArray[$i]'>$PathTitleArray[$i]</a>$delim");
		} else {
			array_unshift ($PathArray, $PathTitleArray[$i].$delim);
		}
    }
    array_pop($PathArray);
    $PathString = implode("", $PathArray);
    if ($with_title) {
		$PathString = $PathString.$title;
	}
    return $PathString;
}

/**
 * Quote variables to make them safe for using in SQL.
 * From http://au.php.net/manual/en/function.mysql-real-escape-string.php
 */
function esc($value) {
	// Stripslashes
	if (get_magic_quotes_gpc()) {
		$value = stripslashes($value);
	}
	// Quote if not a number or a numeric string
	if (!is_numeric($value)) {
		$value = mysql_real_escape_string($value);
	}
	return $value;
}

?>