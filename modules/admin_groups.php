<?php
/* $Id$ */

//----------------------------------------------------------------------------//
//                              Rename a group.                                  //
//----------------------------------------------------------------------------//
if ($_POST['rename_group']) {
	$result = mysql_query("UPDATE groups SET name='".esc($_POST['group_name'])."' WHERE level=".esc($_POST['level']));
	if (mysql_affected_rows()>0) {
		$Page['message'] .= "<p>Group renamed.</p>";
	} else {
		$Page['error_message'] .= "<p>".mysql_error()."</p>";
	}
}

//----------------------------------------------------------------------------//
//                           List all groups.                                 //
//----------------------------------------------------------------------------//
$result = mysql_query("SELECT * FROM groups ORDER BY level DESC, name ASC");
if (mysql_num_rows($result)>0) {
	$Page['body'] .= "<table>\n      <tr><th>Level</th><th>Name</th></tr>\n";
	while ($row = mysql_fetch_assoc($result)) {
		$Page['body'] .= "      <tr>\n".
			"        <td>".$row['level']."</td>\n".
			"        <td>".$row['name']."</td>\n".
			"      </tr>\n";
	}
	$Page['body'] .= "    </table>\n\n";
}

//----------------------------------------------------------------------------//
//                      Display 'rename group' form.                           //
//----------------------------------------------------------------------------//
//Get group information, and build <select>
$sql = "SELECT * FROM groups";
$result = mysql_query($sql);
$num_teams = mysql_num_rows($result);
$group_select_element = ("<select name='level'>\n");
while ($row = mysql_fetch_assoc($result)) {
	$group_select_element .= "<option value='".$row['level']."'>".$row['name']."</option>\n";
}
$group_select_element .= "</select>\n";

$Page['body'] .= (
	"<form action='' method='post'>\n".
	"  <h3>Rename Group</h3>".
	"    <div class='subsection'>\n".
	"      <div class='input' style='width:50%'><label>From</label>$group_select_element</div>\n".
	"      <div class='input' style='width:50%'><label>To</label><input type='text' name='group_name' /></div>\n".
	"    </div>\n".
	"    <p class='submit'><input type='submit' name='rename_group' value='Rename' /></p>\n".
	"</form>\n"
);

?>