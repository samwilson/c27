<?php
require_once 'config.php';
$page->setTitle('Tags');

$page->addBodyContent("<ol class='columnar'>");
foreach ($db->fetchAll("SELECT * FROM tags") as $tag) {
	$page->addBodyContent("<li>{$tag['title']} <a href='?action=edit&table_name=tags&id={$tag['id']}'>[e]</a> <a href='?action=delete&table_name=tags&id={$tag['id']}' class='delete'>[d]</a></li>");
}

require_once 'HTML/QuickForm.php';
$form = new HTML_QuickForm();
$form->addElement('hidden','table_name','tags');
$form->addElement('hidden','action','add');
$form->addElement('hidden','return_to',$_SERVER['PHP_SELF']);
$form->addElement('header','','Add New Tag:');
$form->addElement('text','title');
$form->addElement('submit','save','Add');
$page->addBodyContent('<li>'.$form->toHtml().'</li>');

$page->addBodyContent("</ol>");

$page->display();
?>