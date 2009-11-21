<?php

// The title of the whole site.  Used in a bunch of places.  Or one, at least.
define('SITETITLE', 'A Channel 27 Site');

// Hide all errors.
error_reporting(0);

// Timezone.
// See http://php.net/manual/en/function.date-default-timezone-set.php for a
// list of valid timezones.
date_default_timezone_set('Time/Zone');

// The DATADIR is used all over the place, hence is global and static.  Note
// that there is NO trailing slash.
define('DATADIR','/home/user/sitedata');

// The Data Source Name should be usable with PEAR's MDB2 class.
$dsn = "mysql://username:password@example.org/database_name";

// If the system-wide copy of PEAR doesn't contain all we need, you might need
// to install a local copy.  Enter the path to it here.
set_include_path(get_include_path().PATH_SEPARATOR."/home/user/pear/php/");

// The absolute URL to the root of the website, without a trailing slash.
define('WEBROOT', 'http://example.org/sub/directory');

// If you want to use your site as a OpenID delegate, enter the server and
// delegate details here.
$openid_delegate = "http://example.org/openid/server";
$openid_delegate = "http://example.org/openid/delegate";

// Mail server information.
$mail_server['server']   = 'mail.example.org';
$mail_server['port']     = 143;
$mail_server['username'] = 'user@example.org';
$mail_server['password'] = 'p4zzw0rD';
$mail_server['inbox']    = 'INBOX.ToArchive';
