<?php
require_once '../common.php';



if ( $auth->getAuthData('auth_level') < 10 ) {
	show_login_form();
}


// Main menu
$main_menu = array(
	'inbox.php' => 'Inbox',
	'emails.php' => 'Emails',
	'people.php' => 'People',
	'journal.php' => 'Journal',
	'bookkeeping.php' => 'Bookkeeping',
	'images.php' => 'Images',
	'feeds.php' => 'Feeds',
	'tags.php' => 'Tags');
$page->addBodyContent("<div style='margin-top:1em'><ul class='tabs' style='padding-left:2em'>");
foreach ($main_menu as $filename=>$title) {
	$selected = ($filename==basename($_SERVER['PHP_SELF'])) ? 'class="selected"' : '';
	$page->addBodyContent("<li><a $selected href='$filename'>$title</a></li>");
}
$page->addBodyContent("</ul></div>");










function getAuthLevelRadios($selected) {
	global $db;
	$out = "";
	$auth_levels = $db->fetchAll("SELECT * FROM auth_levels ORDER BY id");
	foreach ($auth_levels as $auth_level) {
		$sel = ($selected==$auth_level['id']) ? "checked='checked' " : "";
		$out .= "<input id='auth_level_{$auth_level['id']}' type='radio' name='auth_level' value='{$auth_level['id']}' $sel/>".
			"<label for='auth_level_{$auth_level['id']}'>{$auth_level['name']}</label>&nbsp;&nbsp; ";
	}
	return $out;
}





function getTagCheckboxes($table, $id = 0) {
	global $db;
	$out = "<fieldset><legend>Tags</legend><ol class='columnar tags'>";
	if ($table == 'images') {
		$sql = "SELECT id, title, IF(image IS NOT NULL, 'checked', '') AS checked 
		FROM tags LEFT OUTER JOIN tags_to_images ON (tags.id=tags_to_images.tag AND tags_to_images.image=$id)";
	} elseif ($table == 'journal_entries') {
		$sql = "SELECT id, title, IF(journal_entry IS NOT NULL, 'checked', '') AS checked 
		FROM tags LEFT OUTER JOIN tags_to_journal_entries ON (tags.id=tags_to_journal_entries.tag AND tags_to_journal_entries.journal_entry=$id)";		
	} else {
		die("Invalid table for tag lookup: $table");
	}
	foreach ($db->fetchAll($sql) as $tag) {
		$out .= "<li><label><input type='checkbox' name='tags[{$tag['id']}]' {$tag['checked']} />{$tag['title']}</label></li>";
	}
	$out .= "</ol></fieldset>";
	return $out;
}


?>