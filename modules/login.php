<?php
/* $Id$ */

//----------------------------------------------------------------------------//
//                              Do login.                                     //
//----------------------------------------------------------------------------//
if ($_POST['login']) {
	$result = mysql_query("SELECT * FROM users
		WHERE username='".esc($_POST['username'])."'
		AND password=MD5('".esc($_POST['password'])."') LIMIT 1");
	if (mysql_num_rows($result) > 0) {
		$User = mysql_fetch_assoc($result);
		$User['logged_in'] = true;
		$_SESSION['username'] = $_POST['username'];
		$_SESSION['password'] = $_POST['password'];
		$Page['message'] .= "<p>You are now logged in.</p>";
	} else {
		$Page['error_message'] .= "<p>Login failed.</p>";
	}
}

//----------------------------------------------------------------------------//
//                         Display login form.                                //
//----------------------------------------------------------------------------//
if (!$User['logged_in']) {
	$Page['style'] .= "form.login {text-align:center}";
	$Page['body'] .= "
		<form action='?id=".$Page['id']."' method='post' class='login'>
		  <p>Username:<br />
			<input type='text' name='username' /></p>
			<p>Password:<br />
			<input type='password' name='password' /></p>
			<p class='submit'><input type='submit' name='login' value='login' /></p>
		</form>";
}

?>