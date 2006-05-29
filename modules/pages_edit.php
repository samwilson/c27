<?php
/* $Id$ */

if ($_POST['is_published'] == "on") $is_published = 1;
else $is_published = 0;

$body = $_POST['body'];

if ($_POST['update']) {
	$sql = ("UPDATE pages SET
		date_modified = NOW(),
		last_modified_by = ".$User['id'].",
		parent_id = '".esc($_POST['parent_id'])."',
		auth_level = '".esc($_POST['auth_level'])."',
		title = '".esc($_POST['title'])."',
		include_file = '".esc($_POST['include_file'])."',
		style = '".esc($_POST['style'])."',
		summary = '".esc($_POST['summary'])."',
		body = '".esc($body)."',
		is_published = ".esc($is_published)."
		WHERE id = '".esc($_GET['edit_id'])."'");
	if (!$result = mysql_query($sql)) {
		$Page['error_message'] .= "<p>".mysql_error()."</p><pre>$sql</pre>";
	} else {
		$Page['body'] .= ("<p>Page updated.<br>Return to
			<a href='index.php?id=".$_GET['edit_id']."'>".$_POST['title']."</a>
			(ID: ".$_GET['edit_id'].")</p>");
	}

} else if ($_POST['insert']) {
	$sql = ("INSERT pages SET
		date_created = NOW(),
		author = ".$User['id'].",
		last_modified_by = ".$User['id'].",
		parent_id = '".esc($_POST['parent_id'])."',
		auth_level = '".esc($_POST['auth_level'])."',
		title = '".esc($_POST['title'])."',
		include_file = '".esc($_POST['include_file'])."',
		style = '".esc($_POST['style'])."',
		summary = '".esc($_POST["summary"])."',
		is_published = ".esc($is_published).",
		body = '".esc($body)."'");
	$result = mysql_query($sql);
	$new_id = mysql_insert_id();
	$Page['body'] .= ("<p>New page inserted.<br>".
		"<br>Go to <a href='index.php?id=$new_id'>".
		$_POST['title']."</a>(ID: $new_id)</p>");

} else if ($_POST['delete']) {
	$sql = ("DELETE FROM pages WHERE id = '".esc($_POST['edit_id'])."' LIMIT 1");
	$result = mysql_query($sql);
	$Page['body'] .= "<p>".$_POST['title']." has been deleted.</p>";
		
} else {
	if (!$_GET['edit_id']) {
		$EditPage = array("parent_id"=>0);
	} else {
		$sql = "SELECT * FROM pages WHERE id = ".esc($_GET['edit_id'])." LIMIT 1";
		$result = mysql_query($sql);
		if (mysql_num_rows($result)) {
			$EditPage = mysql_fetch_assoc($result);
		} else {
			$Page['error_message'] = "No page with ID ".$_GET['edit_id']." exists.";
			$Page = array("parent_id"=>0);
		}
	}
	
	if ($EditPage['is_published']) { $is_published_checked = "checked"; }

//********************************************************//
//********************************************************//
	//Get group information, and build <select>
	$sql = "SELECT * FROM groups";
	$result = mysql_query($sql);
	$num_teams = mysql_num_rows($result);
	$team_select_element = ("<select id='auth_level' name='auth_level'>\n");
	for ($team=0; $team<$num_teams; $team++) {
		$row = mysql_fetch_assoc($result);
		if ($row['level'] == $EditPage['auth_level']) {
			$selected = 'selected';
		} else {
			$selected = '';
		}
		$team_select_element .= "<option value='".$row['level']."' $selected>".
			$row['level']." - ".$row['name']."</option>\n";
	}
	$team_select_element .= "</select>\n";
//********************************************************//
//********************************************************//
	//Get include files, and build <select>
	$inc_files = array(0=>"");
	if ($handle = opendir('modules/')) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != ".." AND $file!="rte") {
				$inc_files[] = $file;
			}
		}
		closedir($handle);
	}
	sort($inc_files, SORT_STRING);
	$inc_select_element = "<select id='include_file' name='include_file'>\n";
	foreach($inc_files as $file) {
		if ($file == $EditPage['include_file']) {
			$selected = 'selected';
		} else {
			$selected = '';
		}
		$inc_select_element .= "<option value='$file' $selected>$file</option>\n";
	}
	$inc_select_element .= "</select>\n";
//********************************************************//
//********************************************************//
//Get parent page information, and build <select>
	$sql = "SELECT id FROM pages";
	$result = mysql_query($sql);
	if ($result) {
		$num_rows = mysql_num_rows($result);
		$parent_select_element = ("<select name='parent_id'>\n");
		for ($row_num=0; $row_num<$num_rows; $row_num++) {
			$row = mysql_fetch_assoc($result);
			$parents_path[$row['id']] = MakePath($row['id'], 0);
		}
		asort ($parents_path);
		foreach ($parents_path as $parent_id => $parent_path) {
			if ($parent_id == $EditPage['parent_id']) {
				$selected = 'selected';
			} else {
				$selected = '';
			}
	
			$parent_select_element .= (
				"<option value='$parent_id' $selected>$parent_path</option>
			");
		}
		$parent_select_element .= "</select>\n";
	}
//********************************************************//
//********************************************************//
	
	$Page['style'] .= "TEXTAREA {width:100%}";
	
	$Page['body'] .= ("
<form action='?id=".$Page['id']."&edit_id=".$EditPage["id"]."' method='post'>
<a name='view'></a>");
	if (isset($EditPage['id'])) {
  		$Page['body'] .= "<span style='color: red'>You are editing page ".$EditPage["id"]."</span>";
	}
	$Page['body'] .= ("
Parent Page: $parent_select_element
Authorisation Level:$team_select_element <span style='font-size:smaller'></span><br />
Title: <input type=\"text\" name=\"title\" size=\"40\" value=\"".$EditPage["title"]."\">
Include File: $inc_select_element<br>
Style: <textarea name=\"style\" rows=\"6\">".$EditPage["style"]."</textarea>
Summary (inline tags only): <textarea name=\"summary\" rows=\"6\">".$EditPage["summary"]."</textarea>
Body (Full HTML): <textarea name='body' rows='15' id='body-textarea'>".$EditPage["body"]."</textarea>
Published?<input type='checkbox' name='is_published' $is_published_checked>
");
	if ($EditPage['id']) {
		$Page['body'] .= ("
			<input type='submit' name='update' value='Update'>
			<input type='submit' name='delete' value='Delete - NO WARNING!'>
			<input type='submit' name='insert' 
				value='Insert As New'> <strong>Insert As New</strong>
			doesn't change the record that you are now viewing.
		");
	} else {
		$Page['body'] .= ("
			<input type='submit' name='insert' value='Insert New Page'>");
	}
	$Page['body'] .= ("
		</p></form>
	");
}
?>