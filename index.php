<?php
/* $Id$ */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * Channel 27 - A heirarchical, page-centric CMS.  Copyright 2006 Samuel Wilson.
 *
 *  Authors:  Samuel Wilson, Canberra, Australia, <sam@co-operista.com>.
 *
 *  Version:  1.3  (Your constructive criticism is appreciated, please see our
 *            project page on http://sourceforge.net/projects/channel27/ to post
 *            bug reports and feature requests.)
 *
 *  Licence:  This program is free software; you can redistribute it and/or
 *            modify it under the terms of the GNU General Public License as
 *            published by the Free Software Foundation; either version 2 of the
 *            License, or (at your option) any later version.
 *
 *            This program is distributed in the hope that it will be useful,
 *            but WITHOUT ANY WARRANTY; without even the implied warranty of
 *            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *            GNU General Public License for more details.
 *
 *            You should have received a copy of the GNU General Public License
 *            along with this program; if not, write to the Free Software
 *            Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 *            MA  02110-1301  USA
 *
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * **/
 
session_start();
include 'config.php';
include 'library.php';

//----------------------------------------------------------------------------//
//------------------ Get this page, or else send 404 error. ------------------//
//----------------------------------------------------------------------------//
if ($_GET['id'] == '') $id = 1; else $id = $_GET['id'];
$sql = "SELECT * FROM pages WHERE id='".esc($id)."' AND is_published='1' LIMIT 1";
$result = mysql_query($sql);
$Page = mysql_fetch_assoc($result);
if (mysql_num_rows($result) == 0) {
	header('HTTP/1.0 404 Not Found');
	$Page = array('title'    => 'Error 404: Page Not Found',
      		 'auth_level'    => 0,
		     'error_message' =>
		                    "<p>The page that you have requested does not exist.</p>");
}

//----------------------------------------------------------------------------//
//-------------- check that the current user can view this page --------------//
//----------------------------------------------------------------------------//
if ($_SESSION['username'] && $_SESSION['password']) {
	$sql = ("SELECT * FROM users, groups 
		WHERE users.username = '".esc($_SESSION['username'])."'
		AND users.password = MD5('".esc($_SESSION['password'])."')
		AND groups.level = users.level LIMIT 1");
	if (!$result = mysql_query($sql)) {
		$Page['error_message'] .= "<p>".mysql_error()."</p><pre>$sql</pre>";
	}
	if (mysql_num_rows($result) > 0) {
		$User = mysql_fetch_assoc($result);
		$User['logged_in'] = true;
	}
}
if ($Page['auth_level'] != '0') { // if not a public page.
	if ($User) {
		if ($User['level'] < $Page['auth_level']) {
			$Page = array();
			$Page['parent_id'] = '1';
			$Page['title'] = "Access Denied";
			$Page['error_message'] = "You are not authorised to view this page.";
		}
	} else {
		$Page = array();
		$Page['id'] = 3;
		$Page['parent_id'] = '1';
		$Page['title'] = "Not Logged In";
		$Page['message'] = "<p>Please <a href='?id=3'>log in</a>.</p>";
		$Page['auth_level'] = '0';
	}
}

//----------------------------------------------------------------------------//
//--------Include a file for $body if neccessary ------------//
//----------------------------------------------------------------------------//
if ($Page['include_file']) {
	$include_file = "modules/".$Page['include_file'];
	if (file_exists($include_file)) {
		require_once($include_file);
	} else {
		$Page['error_message'] = "<em>".$include_file."</em> does not exist.";
	}
}

//----------------------------------------------------------------------------//
//-------- MAKE PATH ------------------
//----------------------------------------------------------------------------//
if ($Page['id']) {
	$Page['breadcrumb'] = MakePath($Page['id']);
}

//----------------------------------------------------------------------------//
//------------------------- Build TOC if neccessary. -------------------------//
//----------------------------------------------------------------------------//
$result = mysql_query("SELECT id, title FROM pages
                       WHERE parent_id = '".esc($id)."'
                       AND id != '".esc($id)."'
                       AND auth_level <= '".$User['level']."'");
if (mysql_num_rows($result) > 0) {
	$Page['TOC'] = "<ol>";
	while ($row=mysql_fetch_assoc($result)) {
		$Page['TOC'] .= "<li><a href='?id=".$row['id']."'>".$row['title']."</a></li>";
	}
	$Page['TOC'] .= "</ol>";
}


//----------------------------------------------------------------------------//
//--------Output the HTML page------------
//----------------------------------------------------------------------------//
require_once('template.php');
?>