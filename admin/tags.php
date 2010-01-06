<?php
require_once 'common.php';
$page->setTitle('Tags');









require_once 'HTML/QuickForm.php';
$form = new HTML_QuickForm();
if ( isset($_REQUEST['action'])
     && $_REQUEST['action'] == 'edit'
     && isset($_REQUEST['id'])
     && is_numeric($_REQUEST['id'])
) {
    $defaults = $db->fetchAll(
        "SELECT * FROM tags WHERE id='".$db->esc($_REQUEST['id'])."' LIMIT 1"
    );
    $form->addElement('hidden', 'id');
    $form->setDefaults($defaults[0]);
}
$form->addElement('hidden', 'return_to', $_SERVER['PHP_SELF']);
$form->addElement('header', null, 'Add or edit a tag');
$form->addElement('text', 'title', 'Title:', array('class' => 'span-21 last'));
$form->addElement('submit', 'save', 'Save');
$page->addBodyContent('<div class="span-24 last">'.$form->toHtml().'</div>');
if ($form->isSubmitted() && $form->validate()) {
    $db->save('tags', $form->getSubmitValues());
}











$page->addBodyContent("<ol class='columnar'>");
foreach ($db->fetchAll("SELECT * FROM tags") as $tag) {
	$page->addBodyContent("<li class='span-5'>
	{$tag['title']} <a href='?action=edit&id={$tag['id']}'>[e]</a>
	<a href='?action=delete&table_name=tags&id={$tag['id']}' class='delete'>[d]</a>
	</li>");
}
$page->addBodyContent("</ol>");








$page->addBodyContent('</div><!-- end div.container -->');
$page->display();





