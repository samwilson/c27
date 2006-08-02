<?php
/* $Id: admin_user.php 3 2006-05-29 00:13:56Z samwilson $ */

//----------------------------------------------------------------------------//
//                          Edit this user.                                 //
//----------------------------------------------------------------------------//
if ($_POST['edit_user']) {
	if ($_POST['password'] != "") {
		if ($_POST['password'] == $_POST['password_verification']) {
			$password_sql = "password = MD5('".esc($_POST['password'])."'),";
		} else {
			$Page['error_message'] .= "<p>Password mis-match; please try again.</p>";
			$password_sql = "";
		}
	} else {
		$password_sql = "";
	}
	$sql = ("UPDATE users SET
		first_name='".esc($_POST['first_name'])."',
		surname='".esc($_POST['surname'])."',
		email='".esc($_POST['email'])."',
		username='".esc($_POST['username'])."',
		$password_sql
		level='".esc($_POST['level'])."',
		status='".esc($_POST['status'])."'
		WHERE id = ".esc($_GET['user_id'])."
	");
	$result = mysql_query($sql);
	if (mysql_affected_rows() > 0) {
		$Page['message'] .= "<p>Changes saved.</p>";
	} else {
		$Page['error_message'] .= "<p>There was a bit of a problem: ".mysql_error().".</p>";
	}
}

//----------------------------------------------------------------------------//
//                          Delete this user.                                 //
//----------------------------------------------------------------------------//
if ($_GET['action'] == 'delete-ok' && $_GET['user_id']) {
	$sql = "DELETE FROM users WHERE id=".esc($_GET['user_id'])." LIMIT 1";
	$result = mysql_query($sql);
	if (mysql_affected_rows() < 0) {
		$Page['error_message'] .= "<p>Something went wrong.</p><p>".mysql_error()."</p><pre>$sql</pre>";
	} else {
		$Page['message'] .= "<p>User deleted.</p><p><a href='?id=13'>[Return to list of users]</a></p>";
	}
}
if ($_POST['delete_user']) {
	$result = mysql_query("SELECT * FROM users WHERE id=".esc($_GET['user_id'])." LIMIT 1");
	if (mysql_num_rows($result) <= 0) {
		$Page['error_message'] .= "<p>No user by that ID was found; just where <em>do</em> you get your information, sir?!</p>";
	} else {
		$the_user = mysql_fetch_assoc($result);
		$Page['message'] .= "<p>Are you sure you want to delete ".
			"<code>".$the_user['username']."</code>? This action cannot be".
			" undone.</p>\n<p class='confirm'>".
			"<a href='?id=".$Page['id']."&action=delete-ok&user_id=".$_GET['user_id']."'>[Yes]</a>".
			" <a href='?id=".$Page['id']."&user_id=".$_GET['user_id']."'>[No]</a></p>";
	}
}

//----------------------------------------------------------------------------//
//                      Display 'edit user' form.                            //
//----------------------------------------------------------------------------//
if ($_GET['user_id'] && $_GET['action'] != 'delete-ok') {

	$result = mysql_query("SELECT * FROM users WHERE id=".esc($_GET['user_id'])." LIMIT 1");
	if (mysql_num_rows($result) <= 0) {
		$Page['error_message'] .= "<p>No user by that ID was found; just where <em>do</em> you get your information, sir?!</p>";
	} else {
		$the_user = mysql_fetch_assoc($result);

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
			"<form action='?id=".$Page['id']."&user_id=".$the_user['id']."' method='post'>\n".
			"      <h3>Edit User</h3>\n".
			"      <div class='subsection'>".
			"	     <div class='input' style='width:33%'>".
			"          <label>First Name</label>".
			"          <input type='text' name='first_name' value='".$the_user['first_name']."'>\n".
			"        </div>\n".
			"	     <div class='input' style='width:33%'>".
			"          <label>Surname</label>".
			"          <input type='text' name='surname' value='".$the_user['surname']."'>\n".
			"        </div>\n".
			"        <div class='input' style='width:33%'>\n".
			"          <label >Email</label>".
			"          <input type='text' name='email' value='".$the_user['email']."'>\n".
			"        </div>\n".
			"	   </div>\n".
			"      <div class='subsection'>".
			"	     <div class='input' style='width:25%'>".
			"          <label>Username (readonly)</label>".
			"          <input type='text' name='username' class='readonly' readonly='readonly' value='".$the_user['username']."'>\n".
			"        </div>\n".
			"        <div class='input' style='width:25%'>\n".
			"          <label>Group</label>".
			"          $group_select_element\n".
			"        </div>\n".
			"	     <div class='input' style='width:25%'>".
			"          <label>Password (leave blank to not change)</label>".
			"          <input type='password' name='password'>\n".
			"        </div>\n".
			"        <div class='input' style='width:25%'>\n".
			"          <label>Password (again, to verify)</label>".
			"          <input type='password' name='password_verification'>\n".
			"        </div>\n".
			"	   </div>\n".
			
			"      <p class='submit'>\n".
			"        <input type='submit' name='edit_user' value='Save' />\n".
			"        <input type='submit' name='delete_user' value='Delete this user' />\n".
			"        <a href='?id=13'>[Return to user list]</a>\n".
			"      </p>\n".
			"    </form>\n\n"
		);
	}
} else if ($_GET['action'] != 'delete-ok') {
	$Page['error_message'] .= "<p>You did not specicy a user to edit.</p>";
}

?>