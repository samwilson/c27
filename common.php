<?php

// Include the configuration file.
$configFilename = dirname(__FILE__) . '/config.php';
if (!file_exists($configFilename)) {
    header("Content-Type:text/plain");
    die("Configuration file not found.\nLooking for: $configFilename");
}
require_once $configFilename;

// Set up database.
require_once dirname(__FILE__) . '/lib/Database.php';
$db = new Database($dsn);

// Set up HTML page object.
require_once 'HTML/Page2.php';
$page = new HTML_Page2();
$page->setDoctype('XHTML 1.0 Strict');
$page->addStylesheet(WEBROOT.'/lib/blueprint/screen.css', 'text/css', 'screen, projection');
$page->addStylesheet(WEBROOT.'/lib/blueprint/print.css', 'text/css', 'print');
$page->addStylesheet(WEBROOT.'/lib/blueprint/plugins/fancy-type/screen.css', 'text/css', 'screen, projection');
$page->addStylesheet(WEBROOT.'/lib/blueprint/plugins/tabs/screen.css', 'text/css', 'screen, projection');
$page->addStylesheet(WEBROOT.'/lib/blueprint/plugins/buttons/screen.css', 'text/css', 'screen, projection');
$page->addRawHeaderData("\t\t".'<!--[if lt IE 8]>'."\n".
"\t\t\t".'<link rel="stylesheet" href="'.WEBROOT.'/lib/css/blueprint/ie.css" type="text/css" media="screen, projection">'."\n".
"\t\t".'<![endif]-->'."\n");
$page->addStylesheet(WEBROOT.'/screen.css','text/css','screen');
$page->setTitle(SITETITLE);
$page->addHeadLink($openid_server, "openid.server");
$page->addHeadLink($openid_delegate, "openid.delegate");

// Javascript stuff.
$page->addScript(WEBROOT.'/common.js');
$page->setAttribute('onload', 'onBodyLoad()');

// Set up structure for BlueprintCSS.
$page->addBodyContent('<div class="container">');
// Don't forget to add the following at the end of every script:
#$page->addBodyContent('</div><!-- end div.container -->');
#$page->display();


require_once 'HTML/CSS.php';
$css = new HTML_CSS();
$page->addStyleDeclaration($css);


// Authentication. 
require_once 'Auth.php';
$auth = new Auth('MDB2', array('dsn'=>$dsn, 'table'=>'users', 'db_fields'=>'auth_level'), 'login_form', false);
$auth->start();
if (isset($_GET['logout'])) {
	$auth->logout();
}
if (isset($_GET['login']) || isset($_GET['logout'])) {
	show_login_form();
}
if ($auth->checkAuth()) {
    $page->addBodyContent('
    <ul class="small" style="position:absolute; top:0; right:0; text-align:right">
        <li><a href="'.WEBROOT.'">'.SITETITLE.'</a></li>
        <li>Logged in as '.$auth->getUsername().'.</li>
        <li><a href="?logout">Logout</a></li>
    </ul>');
}



////////////////////////////////////////////////////////////////////////////////
//
// Functions only are below here.  It's the function library.
//
////////////////////////////////////////////////////////////////////////////////




function show_login_form() {
	global $auth, $page;
	$page->setBody("<div class='container'>
	<div class='span-6 prepend-9 append-9 last prepend-top'>");
	$page->addBodyContent(login_form($auth->getUsername(), $auth->getStatus(), $auth));
	$page->addBodyContent("</div>\n</div><!-- end div.container -->");
	$page->display();
	die();
}



function login_form($username = null, $status = null, &$auth = null) {
	require_once 'HTML/QuickForm.php';
	global $page,$css;
	$page->setTitle('SAM/LOGIN');
	
	
	if ($status==AUTH_EXPIRED) {
	    $status = 'Your session has expired. Please login again.';
    } elseif ($status== AUTH_IDLED) {
		$statusatus = 'You have been idle for too long.  Please login again.';
    } elseif ($status==AUTH_WRONG_LOGIN) {
        $status = 'Incorrect username or password.';
    } else if ($status==AUTH_SECURITY_BREACH) {
        $status = 'A security problem was detected.  Please login again.';
    } else {
    	$status = 'Please log in.';
    }
	$form = new HTML_QuickForm('login-form','post',$_SERVER['PHP_SELF']);
	$form->removeAttribute('name');
	$form->addElement('header','',$status);
	$form->addElement('text','username','Username: ',array('id'=>'focus-me'));
	$form->addElement('password','password','Password: ');
	$form->addElement('submit','login','Login');
	/*
	include_once 'Auth/Frontend/Html.php';
	ob_start();
	Auth_Frontend_Html::render($auth);
	$form = ob_get_contents();
	ob_end_clean();
	*/
	return $form;
}




function titlecase($str) {
	$str = str_replace('id','ID',$str);
	return ucwords(str_replace('_',' ',$str));
}





function wiki2latex($in) {
	$out = "\n$in\n";
		// Platform-independent newlines.
	$out = preg_replace("/(\r\n|\r)/", "\n", $out);
		// Turn applicable paragraphs into blockquotes.
	//$out = preg_replace('|\:(.+)|m', "\\begin{quotation}\n$1\n\n\\end{quotation}", $out);
	//$out = preg_replace('|</blockquote>\n\n<blockquote>|', "", $out);
		// Strong emphasis.
	$out = preg_replace("|'''(.*?)'''|s", '\\textbf{$1}', $out);
		// Emphasis.
	$out = preg_replace("/''(.*?)''/", '\\emph{$1}', $out);
		// Monospacing.
	//$out = preg_replace("|@@(.*?)@@|s", "<code>$1</code>", $out);
		// Proper full-stop spacing.
	//$out = preg_replace("|\.  |", ".&nbsp; ", $out);
		// Curly quotation marks.
	$out = preg_replace("/\"(.*)\"/", "&ldquo;$1&rdquo;", $out);
	//$out = preg_replace("/'(.*)'/s", "&lsquo;$1&rsquo;", $out);
		// Em Dashes.
	//$out = preg_replace("/---/", "&thinsp;&mdash;&thinsp;", $out);
		// Ellipses.
	$out = preg_replace("/\.\.\./", '\\dots', $out);
		// Three-star divider paragraph.
	$out = preg_replace("|<p>\*\*\*</p>|", "<p style='text-align:center'>* * *</p>", $out);
		// Links.
	$out = preg_replace("/\[\[([^|]*)\|([^\]]*)\]\]/", "<a href='$1'>$2</a>", $out);
	$out = preg_replace("|[^\"'](https?://([^\s]*))|", " <a href='$1'>$2</a>", $out);
		// Lists.
	$out = preg_replace("|\n\n<p>#|", "\n\n<ol>\n<p>#", $out); // begin ordered
	$out = preg_replace("|<p>#(.*)</p>\n\n|", "<p>#$1</p>\n</ol>\n\n", $out); // end ordered
	$out = preg_replace("|\n\n<p>\*|", "\n\n<ul>\n<p>*", $out); // begin unordered
	$out = preg_replace("|<p>\*(.*)</p>\n\n|", "<p>*$1</p>\n</ul>\n\n", $out); // end unordered
	$out = preg_replace("|<p>[*#](.*)</p>|", "<li>$1</li>", $out); // list items
	//$out = preg_replace("|</p>\n<p>*|", "</li>\n<li>", $out);
	//$out = preg_replace("|<li>(.*)</p>|", "<li>$1</li>\n</ul>", $out);
		// Ordered lists.
	//$out = preg_replace("|</p>\n<p>#|", "</li>\n<li>", $out);
	//$out = preg_replace("|<li>(.*)</p>|", "<li>$1</li>\n</ol>", $out);
		// Headings.
	$out = preg_replace("|<p>==(.*)==</p>|", "\n<h2>$1</h2>\n", $out);
	return wordwrap($out);
}




include('lib/php-typography/php-typography.php');
function wikiformat($in) {
	$out = "\n$in\n";
		// Platform-independent newlines.
	$out = preg_replace("/(\r\n|\r)/", "\n", $out);
		// Paragraphs.
	$out = preg_replace('|(.*)|', "<p>$1</p>", $out);
	$out = preg_replace("|^|", "<p>", $out);
	$out = preg_replace("|$|", "</p>\n\n", $out);
	//$out = preg_replace("|\n+\s*\n+|", "</p>\n\n<p>", $out);
		// Remove paragraphs if they contain nothing (including only whitespace).
	$out = preg_replace('|<p>\s*</p>|', '', $out);
		// Remove nested paragraphs (some pages already have paragraphs marked up).
	$out = preg_replace('|<p>\s*<p>|', '<p>', $out);
	$out = preg_replace('|</p>\s*</p>|', '</p>', $out);
		// Turn applicable paragraphs into blockquotes.
	$out = preg_replace('|<p>\:(.+)</p>|m', "<blockquote>\n<p>$1</p>\n</blockquote>", $out);
	$out = preg_replace('|</blockquote>\n\n<blockquote>|', "", $out);
		// Strong emphasis.
	$out = preg_replace("|'''(.*?)'''|s", "<strong>$1</strong>", $out);
		// Emphasis.
	$out = preg_replace("/''(.*?)''/", "<em>$1</em>", $out);
		// Monospacing.
	$out = preg_replace("|@@(.*?)@@|s", "<code>$1</code>", $out);
		// Proper full-stop spacing.
	//$out = preg_replace("|\.  |", ".&nbsp; ", $out);
		// Curly quotation marks.
	//$out = preg_replace("/\"(.*)\"/", "&ldquo;$1&rdquo;", $out);
	//$out = preg_replace("/'(.*)'/s", "&lsquo;$1&rsquo;", $out);
		// Em Dashes.
	//$out = preg_replace("/---/", "&thinsp;&mdash;&thinsp;", $out);
		// Ellipses.
	//$out = preg_replace("/\.\.\./", "&thinsp;&hellip;&thinsp;", $out);
		// Three-star divider paragraph.
	$out = preg_replace("|<p>\*\*\*</p>|", "<p style='text-align:center'>* * *</p>", $out);
		// Links.
	//$out = preg_replace("/\[\[([^|]*)\|([^\]]*)\]\]/", "<a href='$1'>$2</a>", $out);
	//$out = preg_replace("|[^\"'](https?://([^\s]*))|", " <a href='$1'>$2</a>", $out);
		// Lists.
	$out = preg_replace("|\n\n<p>#|", "\n\n<ol>\n<p>#", $out); // begin ordered
	$out = preg_replace("|<p>#(.*)</p>\n\n|", "<p>#$1</p>\n</ol>\n\n", $out); // end ordered
	$out = preg_replace("|\n\n<p>\*|", "\n\n<ul>\n<p>*", $out); // begin unordered
	$out = preg_replace("|<p>\*(.*)</p>\n\n|", "<p>*$1</p>\n</ul>\n\n", $out); // end unordered
	$out = preg_replace("|<p>[*#](.*)</p>|", "<li>$1</li>", $out); // list items
	//$out = preg_replace("|</p>\n<p>*|", "</li>\n<li>", $out);
	//$out = preg_replace("|<li>(.*)</p>|", "<li>$1</li>\n</ul>", $out);
		// Ordered lists.
	//$out = preg_replace("|</p>\n<p>#|", "</li>\n<li>", $out);
	//$out = preg_replace("|<li>(.*)</p>|", "<li>$1</li>\n</ol>", $out);
		// Headings.
	$out = preg_replace("|<p>==(.*)==</p>|", "\n<h2>$1</h2>\n", $out);
	
	$typo = new phpTypography();
	$out = $typo->process($out);

	return $out;
}




function wikiformat_doco() {
	return "<ul>
		<li>Links <code>[[</code>id<code>|</code>text<code>]]</code> and <code>http[s]://</code>blah.com (no trailing slash)</li>
		<li>Emphasis: <code>''</code>text<code>''</code>.  Strong emphasis: <code>'''</code>text<code>'''</code></li>
		<li>Lists: <code>*</code>text and <code>#</code>text (blank line before)</li>
		<li>Images: <code>[[img:</code>id<code>]]</code></li>
		<li>Headings: <code>==</code>heading<code>==</code> (there is only H2)</li>
		<li>Em dashes: <code>---</code>.&nbsp; Ellipses: <code>...</code>.&nbsp;  No spaces before or after.</li>
		<li>Quotations: <code>:</code> at the begining of one or more paragraphs.</li>
		<li>Divider: <code>***</code> on a line by itself.</li>
		</ul>";
}




function error($error) {
	require_once 'HTML/Page2.php';
	$page = new HTML_Page2();
	$title = 'Error ' . $error->getCode() . ': ' . $error->getType();
	$page->setTitle($title);
	$page->addStyleDeclaration("
		body { background-color:darkslategray; color:yellow; margin:3em }
		table { border:1px solid yellow; border-collapse:collapse }
		th { text-align:left; border-bottom:1px solid yellow }
	");
	//ob_start();
	//print_r($error->getBacktrace());
	//$backtrace = ob_get_clean();
	$page->addBodyContent("
		<h1>$title</h1>
		<p><strong>" . $error->getMessage() . "</strong></p>
		<h2>Debug Information</h2>
		<pre>" . $error->getDebugInfo() . "</pre>
		<h2>Backtrace</h2>
		<table style='width:100%'>
			<tr>
				<th>File</th>
				<th>Line</th>
				<th>Class</th>
				<th>Function</th>
			</tr>");
	foreach ($error->getBacktrace() as $bt) {
		$file = (isset($bt['file'])) ? $bt['file'] : '';
		$line = (isset($bt['line'])) ? $bt['line'] : '';
		$class = (isset($bt['class'])) ? $bt['class'] : '';
		$function = (isset($bt['function'])) ? $bt['function'] : '';
		$page->addBodyContent("<tr>
			<td>$file</td>
			<td>$line</td>
			<td>$class</td>
			<td>$function</td>
		</tr>");
	}
	$page->addBodyContent("</table>");
	$page->display();
	die();
}