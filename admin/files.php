<?php
require_once 'config.php';
$page->setTitle("Files");

$page->addBodyContent("<ol>");
$filedir = "/home/aswilson/samwilson.id.au-files"; // without trailing slash.
exec("find $filedir -print", $files);
foreach ($files as $file) {
	if (is_file($file) && substr($file,-10)!='.thumb.jpg') {
		$file = substr($file, strlen($filedir));
		$page->addBodyContent("<li>");
		$rows = $db->fetchAll("SELECT * FROM files WHERE filename='".$db->esc($file)."'");
		if (count($rows)!=0) $page->addBodyContent("&#x2611; ");
		else $page->addBodyContent("&#x2610; ");
		$page->addBodyContent("<a href='file_manage.php?filename=$file'>$file</a></li>");
	}
}
$page->addBodyContent("<ol>");







/*
$num_files = 10;
if (isset($_POST['submit'])) {
	require_once "HTTP/Upload.php";
	$upload_to = "/home/aswilson/samwilson.id.au-files";
	$upload = new HTTP_Upload("en");
	for ($f=1; $f<=$num_files; $f++) {
		$file = $upload->getFiles("file_$f");
		if ($file->isValid()) {
		    $moved = $file->moveTo($upload_to);
		    if (PEAR::isError($moved)) {
		        $page->addBodyContent("<p>$f: ".$moved->getMessage()."</p>");
		    } else {
		        $page->addBodyContent("<p>$f: <code>".$file->getProp('real')."</code> was moved to <code>$upload_to</code></p>");
		    }
		} elseif ($file->isError()) {
		    $page->addBodyContent("<p>$f: ".$file->errorMsg()."</p>");
		}
	}
}
$page->addBodyContent("<form enctype='multipart/form-data' action='' method='post'>");
for ($f=1; $f<=$num_files; $f++) {
	$page->addBodyContent("<input type='file' name='file_$f' /><br />");
}
$page->addBodyContent("<input type='submit' name='submit' value='Continue &raquo;' /></form>");
*/




$page->display();
?>