<?php
require_once 'common.php';
$current_year  = (isset($_GET['year']) && $_GET['year']  != '') ? $_GET['year']  : date('Y');
$current_month = (isset($_GET['month']) && $_GET['month'] != '') ? $_GET['month'] : date('m');






// Title
$exactDate = false;
if ($current_year > 0 && $current_month > 0) {
	$title = date('F', strtotime("2010-$current_month-01")).' '.$current_year;
	$exactDate = true;
} elseif ($current_year > 0 && $current_month == '00') {
	$title = $current_year.' (Month Unknown)';
} elseif ($current_year == '0000' && $current_month == '00') {
	$title = 'Year and Month Unknown';
} elseif ($current_year == '0000' && $current_month > 0) {
	$title = date('F', strtotime("2010-$current_month-01")).' (Year Unknown)';
}
$page->setTitle($title.' :: '.SITETITLE);
$page->addBodyContent("<h1>$title</h1>");









// Navigation:
// Years
$page->addBodyContent("<ul class='menu navigation years'>");
foreach ($db->fetchAll("SELECT YEAR(date_and_time) AS year FROM images GROUP BY YEAR(date_and_time)") as $year) {
	//$year['year'] = ($year['year'] == '0') ? '0000' : $year['year'];
    $years[$year['year']] = $year['year'];
}
foreach ($db->fetchAll("SELECT YEAR(date_and_time) AS year FROM journal_entries GROUP BY YEAR(date_and_time)") as $year) {
	$year['year'] = ($year['year'] == '0') ? '0000' : $year['year'];
    $years[$year['year']] = $year['year'];
}
$naText = '(<abbr title="Not Available">N/A</abbr>)';
$years = array_unique($years);
asort($years);
foreach ($years as $y_num=>$y_name) {
	$y_name = ($y_num==0) ? $naText : $y_name;
	$y_num = ($y_num==0) ? '0000' : $y_num;
    $selected = ($y_num == $current_year) ? $selected = " class='selected'" : '';
    $page->addBodyContent("<li><a$selected href='".WEBROOT."/$y_num'>$y_name</a></li>");
}
$page->addBodyContent(" </ul>");

// Months
$all_months = array('00'=>$naText, '01'=>'Jan.','02'=>'Feb.','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'Aug.','09'=>'Sept.','10'=>'Oct.','11'=>'Nov.','12'=>'Dec.');
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
	$tagLabel = '';
	$tags = array_map('trim', explode(',',$_GET['tags']));
    foreach ($tags as $t) {
    	$tagList .= "'".$db->esc($t)."', ";
    	$tagLabel .= "<li>$t</li>";
    }
    $tagList = substr($tagList,0,-2);
    $tagLabel = substr($tagLabel,0,-2);
	$tagLabelPrefix = (count($tags) > 1) ? ' with any of the following' : '';
	$page->addBodyContent(
		"<div class='success message content'>
		You are currently viewing all journal entries and images tagged$tagLabelPrefix:
		<ul>$tagLabel</ul>
		</div>"
	);
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
    if ( $e['auth_level'] != 0 && $e['auth_level'] > $auth->getAuthData('auth_level') ) {
    	continue;
    }
	// Title
	$title =  (!empty($e['title'])) ? ', '.$e['title'] : '';
    $title = "<a name='entry-".$e['id']."' href='#entry-".$e['id']."'>
			".date('g:iA',strtotime($e['date_and_time']))."$title</a>";
    // Entry text
	$entryText = "'''".date('g:iA',strtotime($e['date_and_time'])).".''' ".$e['entry_text'];
	$entryText = "'''".$title.":''' ".$e['entry_text'];
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

    
    $entry = array(
    	'tags'          => $tag_data,
		'body'          => wikiformat($entryText),
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
    if ( $i['auth_level'] != 0 && $i['auth_level'] > $auth->getAuthData('auth_level') ) {
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
	    $tag_data = substr($tag_data, 0, -2).'.'; // Strip trailing comma-space etc.
	}
    // Edit link
    $returnTo = urlencode(WEBROOT.'/'.$current_year.'-'.$current_month.'#image-'.$i['id']);
    $editLink = "<a href='".WEBROOT."/admin/images.php?action=edit_image&amp;id=".$i['id']."&amp;return_to=$returnTo'
	class='edit-link' title='Edit metadata for image #".$i['id']." (authorisation required).'>
    Edit</a> <a href='".WEBROOT."/admin/images.php?delete=".$i['id']."&amp;return_to=$returnTo'
    class='edit-link' title='Delete this image (authorisation required)'>
    Delete</a>";
    // Time
    $time = '<a href="'.urldecode($returnTo).'">'.date('g:iA',strtotime($i['date_and_time'])).'</a>';
    $time .= (!empty($i['caption'])) ? ':' : '';
    // Body
    $linkUrl = WEBROOT."/images/".$i['id'];
    $imgUrl = WEBROOT."/images/".$i['id']."/view";
    $body = "<a href='$linkUrl' name='image-".$i['id']."' title='Click for larger view.'>
  		<img src='$imgUrl' alt='Please see caption, below.' />
  	</a>
  	<span class='caption'>
  		".wikiformat("'''$time''' ".$i['caption'])."
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
    $page->addBodyContent("<p class='notice message'>No journal entries or images were found for the requested time period.</p>");
}
$day = '';
foreach ($entries as $key=>$entry) {
    $new_day = date('l, F j<\s\u\p>S</\s\u\p> Y', strtotime($entry['date_and_time']));
    if ($day != $new_day && $exactDate) {
        $day = $new_day;
        $page->addBodyContent("<h2 class=''>$day</h2>");
    }
    $keywords = (!empty($entry['tags'])) ? 'Tags: '.$entry['tags'].' &nbsp; &nbsp; ' : '';
    $page->addBodyContent(
    	"<div class='entry {$entry['class']}'>
    		{$entry['body']}
    		<p class='metadata'>$keywords ".$entry['edit']."</p>
		</div>"
	);
}
//$page->addBodyContent("</div><!-- .day -->"); 
$page->addBodyContent("</div>"); 










// Get a random image.
$or_where = ($auth->checkAuth()) ? "OR auth_level<=".$db->esc($auth->getAuthData('auth_level')) : "";
$image = $db->fetchRow(
	"SELECT id, YEAR(date_and_time) AS year, MONTH(date_and_time) AS month, caption
	FROM images WHERE auth_level=0 $or_where ORDER BY RAND() LIMIT 1"
);
$page->addBodyContent("
<div class='menu random-image entry image'>
		<a href='".WEBROOT."/{$image['year']}-{$image['month']}#image-{$image['id']}'>
			<img src='".WEBROOT."/images/{$image['id']}/thumb' />
	<span class='caption'>
		".wikiformat("'''A randomly-selected image:''' ".$image['caption'])."
	</span>
		</a>
</div>
");

















$page->addBodyContent('</div><!-- end div.container -->');
$page->display();

