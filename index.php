<?php
require_once 'common.php';







// Navigation:
$current_year = (!empty($_GET['year'])) ? $_GET['year'] : date('Y');
$current_month = (!empty($_GET['month'])) ? $_GET['month'] : date('m');
$page->addBodyContent("<h1>".date('F', strtotime("2010-$current_month-01"))." $current_year</h1>");

// Years
$page->addBodyContent("<ul class='menu navigation years'>");
foreach ($db->fetchAll("SELECT YEAR(date_and_time) AS year FROM images GROUP BY YEAR(date_and_time)") as $year) {
    $years[$year['year']] = $year['year'];
}
foreach ($db->fetchAll("SELECT YEAR(date_and_time) AS year FROM journal_entries GROUP BY YEAR(date_and_time)") as $year) {
    $years[$year['year']] = $year['year'];
}
$years[0] = '????';
$years = array_unique($years);
asort($years);
foreach ($years as $y_num=>$y_name) {
    $selected = ($y_name == $current_year) ? $selected = " class='selected'" : '';
    $page->addBodyContent("<li><a$selected href='".WEBROOT."/$y_num'>$y_name</a></li>");
}
$page->addBodyContent(" </ul>");

// Months
$all_months = array('00'=>'(Unknown)', '01'=>'Jan.','02'=>'Feb.','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'Aug.','09'=>'Sept.','10'=>'Oct.','11'=>'Nov.','12'=>'Dec.');
$page->addBodyContent("<ul class='menu navigation months'>");
foreach ($all_months as $m_num=>$m_name) {
    $img_count = $db->fetchOne("SELECT COUNT(*) FROM images WHERE YEAR(date_and_time)=".$db->esc($current_year)." AND MONTH(date_and_time)=".$db->esc($m_num)."");
    $txt_count = $db->fetchOne("SELECT COUNT(*) FROM journal_entries WHERE YEAR(date_and_time)=".$db->esc($current_year)." AND MONTH(date_and_time)=".$db->esc($m_num)."");
    $page->addBodyContent("<li>");
    if ($m_num == $current_month) {
        $page->addBodyContent("<a class='selected' href='".WEBROOT."/$current_year-$m_num' title='$img_count images, $txt_count journal entries.'>$m_name (".($img_count+$txt_count)." posts)</a>");
    } else {
        $page->addBodyContent("<a href='".WEBROOT."/$current_year-$m_num' title='$img_count images, $txt_count journal entries.'>$m_name (".($img_count+$txt_count).")</a>");
    }
    $page->addBodyContent("</li>");
}
$page->addBodyContent(" </ul>");










if (isset($_GET['tags'])) {
	$tagList = '';
    foreach (array_map('trim', explode(',',$_GET['tags'])) as $t) {
    	$tagList .= "'$t', ";
    }
    $tagList = substr($tagList,0,-2);
	$page->addBodyContent("<div class='success span-24 last'>You are currently viewing all journal entries and images tagged as <strong>$tagList</strong>.</div>");
}













$x=1;
$entries = array();
if (isset($_GET['tags'])) {
    $journalSelectSql = "SELECT je.id, je.auth_level, je.entry_text, je.title, je.date_and_time
    	FROM journal_entries je
    	JOIN tags_to_journal_entries tje ON (je.id = tje.journal_entry)
    	JOIN tags t ON (t.id = tje.tag)
    	WHERE t.title IN ($tagList)
    	ORDER BY date_and_time ASC
    ";
    $imagesSql = "SELECT i.id, i.date_and_time, i.caption, i.auth_level
    	FROM images i
    	JOIN tags_to_images ti ON (i.id = ti.image)
    	JOIN tags t ON (t.id = ti.tag)
    	WHERE t.title IN ($tagList)
    	ORDER BY date_and_time ASC
    ";
} else {
	$journalSelectSql = "SELECT * FROM journal_entries
		WHERE YEAR(date_and_time)=".$db->esc($current_year).
		" AND MONTH(date_and_time)=".$db->esc($current_month)."
		ORDER BY date_and_time ASC
	";
	$imagesSql = "SELECT * FROM images
		WHERE YEAR(date_and_time)=".$db->esc($current_year)." 
		AND MONTH(date_and_time)=".$db->esc($current_month)." 
		ORDER BY date_and_time ASC
	";
}



// Get journal entries:
foreach ($db->fetchAll($journalSelectSql) as $e) {
	// Check auth
    if ( $e['auth_level'] > $auth->getAuthData('auth_level') ) {
    	continue;
    }
    // Entry text
	$entryText = "'''".date('g:iA',strtotime($e['date_and_time'])).".''' ".$e['entry_text'];
	// Tags
    $tag_data = '';
    $tags = $db->fetchAll("SELECT id, title FROM tags JOIN tags_to_journal_entries
        ON (tags.id=tags_to_journal_entries.tag) 
        WHERE tags_to_journal_entries.journal_entry='".$db->esc($e['id'])."'");
    foreach ($tags as $tag) {
        $tag_data .= '<a href="'.WEBROOT.'/tags/'.$tag['title'].'">'.$tag['title']."</a>, ";
    }
    $tag_data = substr($tag_data, 0, -2); // Strip trailing comma-space.
    // Edit link
    $editLink = "<a
        href='".WEBROOT."/admin/journal.php?id=".$e['id']."&amp;return_to=".urlencode(WEBROOT.'/'.$current_year.'-'.$current_month."#entry-".$e['id'])."'
	class='edit-link'
        title='Edit entry #".$e['id']." (authorisation required).'>
            Edit
        </a>";
	// Title
	$title =  (!empty($e['title'])) ? ', '.$e['title'] : '';
    $title = "<a name='entry-".$e['id']."' href='#entry-".$e['id']."' title='permalink'>
			".date('g:iA',strtotime($e['date_and_time']))."$title</a>";

    
    $entry = array(
    	'tags'          => $tag_data,
		'body'          => wikiformat($entryText)." $editLink",
		'auth_level'    => $e['auth_level'],
    	'edit'          => $editLink,
    	'class'         => 'journal',
    	'date_and_time' => $e['date_and_time'],
    	'title'         => $title
	);    
    $entries[$entry['date_and_time'].$x] = $entry;
    $x++;
}







// Get images:
foreach ($db->fetchAll($imagesSql) as $i) {
	// Check auth
    if ( $i['auth_level'] > $auth->getAuthData('auth_level') ) {
    	continue;
    }
    // Tags
    $tag_data = '';
    $tags = $db->fetchAll("SELECT id, title FROM tags JOIN tags_to_images
        ON (tags.id=tags_to_images.tag)
        WHERE tags_to_images.image='".$db->esc($i['id'])."'");
    foreach ($tags as $tag) {
        $tag_data .= '<a href="'.WEBROOT.'/tags/'.$tag['title'].'">'.$tag['title']."</a>, ";
    }
    if (!empty($tag_data)) {
	    $tag_data = 'Tags: '.substr($tag_data, 0, -2).'.'; // Strip trailing comma-space etc.
	}
    // Edit link
    $editLink = "<a href='".WEBROOT."/admin/images.php?action=edit_image&amp;id=".$i['id']."&amp;return_to=".urlencode(WEBROOT.'/'.$current_year.'-'.$current_month)."'
	class='edit-link' title='Edit metadata for image #".$i['id']." (authorisation required).'>
    Edit</a>";
    // Time
    $time = date('g:iA',strtotime($i['date_and_time']));
    $time .= (!empty($i['caption'])) ? ':' : '';
    // Body
    $linkUrl = WEBROOT."/images/".$i['id'];
    $imgUrl = WEBROOT."/images/".$i['id']."/view";
    $body = "<a href='$linkUrl' title='Click for larger view.'>
  		<img src='$imgUrl' alt='Image.' />
  	</a>
  	<span class='caption'>
  		".wikiformat("'''$time''' ".$i['caption']."  $tag_data")." $editLink
  	</span>";

    
    $entry = array(
    	'tags'          => $tag_data,
		'body'          => $body,
		'auth_level'    => $i['auth_level'],
    	'edit'          => $editLink,
    	'class'         => 'image',
    	'date_and_time' => $i['date_and_time'],
    	'title'         => ''
	);    
    $entries[$entry['date_and_time'].$x] = $entry;
    $x++;
}









// Put it all together.
ksort($entries);
$page->addBodyContent("<div class='content'>");
if (count($entries) < 1) {
    $page->addBodyContent("<p class='notice'>No journal entries nor images were found.</p>");
}
$day = '';
foreach ($entries as $key=>$entry) {
    $new_day = date('l, F j<\s\u\p>S</\s\u\p> Y', strtotime($entry['date_and_time']));
    if ($day != $new_day) {
        //if (!empty($day)) {
        //    $page->addBodyContent("</div><!-- .day -->"); // If we're already in a day, i.e. not at the begining of the month.
        //}
        $day = $new_day;
        $page->addBodyContent("<h2 class=''>$day</h2>");
    }
    $page->addBodyContent("
	<div class='entry {$entry['class']}'>{$entry['body']}</div>");
}
//$page->addBodyContent("</div><!-- .day -->"); 
$page->addBodyContent("</div>"); 










// Get a random image.
$or_where = ($auth->checkAuth()) ? "OR auth_level<=".$db->esc($auth->getAuthData('auth_level')) : "";
$image = $db->fetchRow("SELECT * FROM images WHERE auth_level=0 $or_where ORDER BY RAND() LIMIT 1");
$page->addBodyContent("
<div class='menu random-image entry image'>
	<h3>A Random Image</h3>
		<a href='".WEBROOT."/images/{$image['id']}'>
			<img src='".WEBROOT."/images/{$image['id']}/thumb' />
		</a>
	<span class='caption'>
		".wikiformat($image['caption'])."
	</span>
</div>
");

















$page->addBodyContent('</div><!-- end div.container -->');
$page->display();

