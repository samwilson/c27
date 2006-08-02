<?php
/* $Id: admin_users.php 3 2006-05-29 00:13:56Z samwilson $ */

//----------------------------------------------------------------------------//
//                              Add a user.                                  //
//----------------------------------------------------------------------------//
if ($_POST['add_user']) {

	$result = mysql_query("SELECT * FROM users WHERE username = '".esc($_POST['username'])."'");
	if (mysql_affected_rows()>0) {
		$Page['error_message'] .= "<p>That username has already been taken, please choose another.</p>";
	} else {

		if ($_POST['password'] != $_POST['password-verification']) {
			$Page['error_message'] .= "<p>Passwords do not match; please try again.</p>";
		} else {
		
			if ($_POST['username'] == "") {
				$Page['error_message'] .= "<p>You must specify a username.</p>";
			} else {
			
				$sql = ("INSERT INTO users SET 
					first_name='".esc($_POST['first_name'])."',
					surname='".esc($_POST['surname'])."',
					email='".esc($_POST['email'])."',
					username='".esc($_POST['username'])."',
					level=".esc($_POST['level']).",
					password = MD5('".esc($_POST['password'])."')
				");
				$result = mysql_query($sql);
				if (mysql_affected_rows()>0) {
					$Page['message'] .= "<p>User added.</p>";
				} else {
					$Page['error_message'] .= "<p>".mysql_error()."</p><pre>$sql</pre>";
				}

			}
		
		} // end if passwords don't match.
		
	} // end if username taken.
	
} // end add user.

//----------------------------------------------------------------------------//
//                      Display 'add a user' form.                            //
//----------------------------------------------------------------------------//

//Get group information, and build <select>
$sql = "SELECT * FROM groups";
$result = mysql_query($sql);
$num_teams = mysql_num_rows($result);
$group_select_element = ("<select name='level'>\n");
while ($row = mysql_fetch_assoc($result)) {
	if ($the_user['level'] == $row['level']) {
		$selected = "selected='selected'";
	} else {
		$selected = "";
	}
	$group_select_element .= "<option value='".$row['level']."' $selected>".$row['name']."</option>\n";
}
$group_select_element .= "</select>\n";

$Page['body'] .= (
	"<form action='?id=".$Page['id']."' method='post'>\n".
	"      <h3>Add User</h3>\n".
	"      <div class='subsection'>".
	"	     <div class='input' style='width:33%'>".
	"          <label>First Name</label>".
	"          <input type='text' name='first_name'>\n".
	"        </div>\n".
	"	     <div class='input' style='width:33%'>".
	"          <label>Surname</label>".
	"          <input type='text' name='surname'>\n".
	"        </div>\n".
	"        <div class='input' style='width:33%'>\n".
	"          <label >Email</label>".
	"          <input name='email'>\n".
	"        </div>\n".
	"	   </div>\n".
	"      <div class='subsection'>".
	"	     <div class='input' style='width:25%'>".
	"          <label>Username</label>".
	"          <input type='text' name='username'>\n".
	"        </div>\n".
	"        <div class='input' style='width:25%'>\n".
	"          <label>Group</label>".
	"          $group_select_element\n".
	"        </div>\n".
	"	     <div class='input' style='width:25%'>".
	"          <label>Password</label>".
	"          <input type='password' name='password'>\n".
	"        </div>\n".
	"        <div class='input' style='width:25%'>\n".
	"          <label>Password (again, to verify)</label>".
	"          <input type='password' name='password-verification'>\n".
	"        </div>\n".
	"	   </div>\n".
	
	"      <p class='submit'><input type='submit' name='add_user' value='Add User' /></p>\n".
	"    </form>\n\n"
);

//----------------------------------------------------------------------------//
//                           List all users.                                  //
//----------------------------------------------------------------------------//
$result = mysql_query("SELECT * FROM groups");
if (mysql_num_rows($result)>0) {
	while ($row = mysql_fetch_assoc($result)) {
		$groups[$row['level']] = $row['name'];
	}
}
$result = mysql_query("SELECT * FROM users");
if (mysql_num_rows($result)>0) {
	$Page['body'] .= "<table>\n      <tr><th>Username</th><th>Name</th><th>Email Address</th><th>Group</th><th>&nbsp;</th></tr>\n";
	while ($row = mysql_fetch_assoc($result)) {
		$Page['body'] .= "      <tr>\n".
			"        <td>".$row['username']."</td>\n".
			"        <td>".$row['first_name']." ".$row['surname']."</td>\n".
			"        <td><a href='mailto:".$row['email']."'>".$row['email']."</a></td>\n".
			"        <td>".$groups[$row['level']]."</td>\n".
			"        <td><a href='?id=8&user_id=".$row['id']."'>[Edit]</a></td>\n".
			"      </tr>\n";
	}
	$Page['body'] .= "    </table>\n\n";
}

?>
