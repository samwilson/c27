<?php
require_once 'common.php';






if ( isset($_GET['format']) && $_GET['format']=='latex' && isset($_GET['year']) ) {
    header("Content-type:text/plain");
    $sql = "SELECT * FROM journal_entries WHERE YEAR(date_and_time)='".$db->esc($_GET['year'])."' ORDER BY date_and_time ASC";
    $posts = $db->fetchAll($sql);
    foreach ($posts as $post) {
        echo date("\\\\\s\e\c\\t\i\o\\n{l, F j\\\\\\t\ex\\t\s\u\p\e\\r\s\\c\\r\i\p\\t{S}}\n\\\\\\t\e\x\\t\b\f{g:iA.}", strtotime($post['date_and_time']));
        echo wiki2latex($post['entry_text'])."\n\n";

    }
    die();
}









if (isset($_POST['save_journal_entry'])) {
    $id = $db->save('journal_entries', $_POST);

    // Save tags
    $db->query("DELETE FROM tags_to_journal_entries WHERE journal_entry=".$db->esc($id));
    $tags = array_map('trim', explode(',',$_POST['tags']));
    foreach ($tags as $tag) {
        if (!empty($tag)) {
            $exists = mysql_num_rows(mysql_query("SELECT * FROM tags WHERE LOWER(title) LIKE LOWER('".$db->esc($tag)."')"));
            if (!$exists) {
                mysql_query("INSERT INTO tags SET title='".$db->esc($tag)."'");
                $tag_id = mysql_insert_id();
            } else {
                $tag_id = $db->fetchAll("SELECT id FROM tags WHERE LOWER(title) LIKE LOWER('".$db->esc($tag)."')");
                $tag_id = $tag_id[0]['id'];
            }
            $db->save(
                'tags_to_journal_entries',
                array('tag' => $tag_id, 'journal_entry' => $id)
            );
        }
    }


}








$title = "Journal";
$page->setTitle($title);

if (isset($_GET['id'])) {
    $data = $db->fetchAll("SELECT * FROM journal_entries WHERE id=".$db->esc($_GET['id'])." LIMIT 1");
    $data = $data[0];
    // Tags:
    $tag_data = "";
    $tags = $db->fetchAll("SELECT title FROM tags JOIN tags_to_journal_entries ON (tags.id=tags_to_journal_entries.tag) WHERE tags_to_journal_entries.journal_entry='".$db->esc($_GET['id'])."'");
    foreach ($tags as $tag) {
        $tag_data .= $tag['title'].", ";
    }
    $tag_data = substr($tag_data, 0, -2); // Strip trailing comma-space.
} else {
    $data = array(
        'date_and_time' => date('Y-m-d H:i:s'),
        'entry_text'    => '',
        'auth_level'    => 10,
        'title'         => '',
        'id'            => 0
    );
    $tag_data = '';
}

$id_input_field = (isset($_GET['id'])) ? "<input type='hidden' name='id' value='".$_GET['id']."' />" : '';
$entry_date = (isset($_GET['id'])) ? substr($data['date_and_time'],0,7) : date('Y-m');
$return_to = (isset($_GET['return_to'])) ? $_GET['return_to'] : WEBROOT."/$entry_date";

$page->addBodyContent("
<div>
    <form action='journal.php' method='post' style='width:50%; margin:auto'>

        <p class='hide'>
            $id_input_field
            <input type='hidden' id='return_to' name='return_to' value='$return_to' />
        </p>

        <div style=''>
            <input type='text' class='text' size='17' style='width:200px; display:block; float:left' name='date_and_time' value=\"".$data['date_and_time']."\" />
            <input class='text' style='width:300px; display:block;float:right' type='text' id='title' name='title' value=\"".$data['title']."\" />
        </div>

        <div class='span-12 last'>
            <textarea class='text' rows='24' style='height:300px; width:98%' name='entry_text'>".$data['entry_text']."</textarea>
        </div>

        <div class='span-12 last'>
            <input class='span-12' type='text' name='tags' value=\"$tag_data\" style='width:98%' />
        </div>

        <div class='span-8'>
            ".getAuthLevelRadios($data['auth_level'])."
        </div>
        <div class='span-4 last' style='text-align:right'>
            <input type='submit' class='button positive center' name='save_journal_entry' value='Save'>
        </div>
        <div class='span-12 last prepend-top'>
            ".wikiformat_doco()."
            <ul>
            <li>No trailing punctuation on title; a colon will be added.</li>
            </ul>
        </div>
    </form>
</div>
");

$page->addBodyContent('</div><!-- end div.container -->');
$page->display();


?>
