<?php
/* $Id$ */

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
		$password_sql
		email='".esc($_POST['email'])."'
		WHERE id = ".esc($User['id'])."
	");
	$result = mysql_query($sql);
	if (mysql_affected_rows() > 0) {
		$Page['message'] .= "<p>Changes saved.</p>";
	} else {
		$Page['error_message'] .= "<p>There was a bit of a problem:
			".mysql_error().".</p><pre>$sql</pre>";
	}
}

//----------------------------------------------------------------------------//
//                      Display 'edit user' form.                            //
//----------------------------------------------------------------------------//

$result = mysql_query("SELECT * FROM users WHERE id=".esc($User['id'])." LIMIT 1");
if (mysql_num_rows($result) <= 0) {
	$Page['error_message'] .= "<p>No user by that ID was found; just where <em>do</em> you get your information?!</p>";
} else {
	$the_user = mysql_fetch_assoc($result);

	$Page['body'] .= (
		"<form action='?id=".$Page['id']."&user_id=".$the_user['id']."' method='post'>\n".
		"      <h3>Logged in as ".$the_user['username']."</h3>\n".
		"      <div class='subsection'>".
		"	     <div class='input' style='width:50%'>".
		"          <label>First Name</label>".
		"          <input type='text' name='first_name' value='".$the_user['first_name']."'>\n".
		"        </div>\n".
		"	     <div class='input' style='width:50%'>".
		"          <label>Surname</label>".
		"          <input type='text' name='surname' value='".$the_user['surname']."'>\n".
		"        </div>\n".
		"      </div>\n".
		"      <div class='subsection'>".
		"        <div class='input' style='width:33%'>\n".
		"          <label >Email</label>".
		"          <input type='text' name='email' value='".$the_user['email']."'>\n".
		"        </div>\n".
		"	     <div class='input' style='width:33%'>".
		"          <label>Password (leave blank to not change)</label>".
		"          <input type='password' name='password'>\n".
		"        </div>\n".
		"        <div class='input' style='width:33%'>\n".
		"          <label>Password (again, to verify)</label>".
		"          <input type='password' name='password_verification'>\n".
		"        </div>\n".
		"	   </div>\n".
		
		"      <p class='submit'>\n".
		"        <input type='submit' name='edit_user' value='Save' />\n".
		"      </p>\n".
		"    </form>\n\n"
	);
}

?>