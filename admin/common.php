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
	'images.php' => 'Images',
	'feeds.php' => 'Feeds',
	'tags.php' => 'Tags');
$page->addBodyContent("<div class='span-24 last prepend-top'><ul class='tabs'>");
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
		$out .= "<input id='auth_level_{$auth_level['id']}' type='radio' style='display:inline' name='auth_level' value='{$auth_level['id']}' $sel/>".
			"<label for='auth_level_{$auth_level['id']}'>{$auth_level['name']}</label>  ";
	}
	return $out;
}





function getTagCheckboxes($table, $id) {
	global $db;
	$out = "<fieldset><legend>Tags</legend><ol class='columnar'>";
	$sql = "SELECT id, title, IF(image IS NOT NULL, 'checked', '') AS checked FROM tags LEFT OUTER JOIN tags_to_$table ON (tags.id=tags_to_$table.tag AND tags_to_$table.image=$id)";
	foreach ($db->fetchAll($sql) as $tag) {
		$out .= "<li><label><input type='checkbox' name='tags[{$tag['id']}]' {$tag['checked']} />{$tag['title']}</label></li>";
	}
	$out .= "</ol></fieldset>";
	return $out;
}


?>