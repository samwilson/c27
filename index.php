<?php
require_once 'common.php';







// Navigation:
$page->addBodyContent("<div class='prepend-top span-24 last'>");
$current_year = (!empty($_GET['year'])) ? $_GET['year'] : date('Y');
$current_month = (!empty($_GET['month'])) ? $_GET['month'] : date('m');

// Years
$page->addBodyContent("<ul class='tabs'>");
$years = array('0'=>'(Unknown)');
foreach ($db->fetchAll("SELECT YEAR(date_and_time) AS year FROM images GROUP BY YEAR(date_and_time)") as $year) {
    $years[$year['year']] = $year['year'];
}
foreach ($db->fetchAll("SELECT YEAR(date_and_time) AS year FROM journal_entries GROUP BY YEAR(date_and_time)") as $year) {
    $years[$year['year']] = $year['year'];
}
$years = array_unique($years);
asort($years);
foreach ($years as $y_num=>$y_name) {
    $selected = ($y_name == $current_year) ? $selected = " class='selected'" : '';
    $page->addBodyContent("<li><a$selected href='".WEBROOT."/$y_num'>$y_name</a></li>");
}
$page->addBodyContent(" </ul>");

// Months
$page->addBodyContent("<ul class='tabs'>");
$all_months = array('00'=>'(Unknown)', '01'=>'Jan.','02'=>'Feb.','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'Aug.','09'=>'Sept.','10'=>'Oct.','11'=>'Nov.','12'=>'Dec.');
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
$page->addBodyContent(" </ul></div>");















$x=1;
$entries = array();




// Get journal entries:
$sql = "SELECT * FROM journal_entries WHERE YEAR(date_and_time)=".$db->esc($current_year)." 
	AND MONTH(date_and_time)=".$db->esc($current_month)." ORDER BY date_and_time ASC";
//$res = mysql_query($sql);
//while ($e = mysql_fetch_assoc($res)) {
foreach ($db->fetchAll($sql) as $e) {
    if ( $e['auth_level']==0 || $e['auth_level']<=$auth->getAuthData('auth_level') ) {
        $entry['body'] = "<div class='entry_text'>".wikiformat($e['entry_text'])."</div>";
    } else {
        $entry['body'] = "<p class='quiet'>Please log in to view this entry.</p>";
    }
    $tag_data = '';
    $tags = $db->fetchAll("SELECT title FROM tags JOIN tags_to_journal_entries
        ON (tags.id=tags_to_journal_entries.tag) 
        WHERE tags_to_journal_entries.journal_entry='".$db->esc($e['id'])."'");
    foreach ($tags as $tag) {
        $tag_data .= '<a href="'.WEBROOT.'/tags/'.$tag['title'].'">'.$tag['title']."</a>, ";
    }
    $tag_data = substr($tag_data, 0, -2); // Strip trailing comma-space.
    $entry['auth_level'] = $e['auth_level'];
    $entry['metadata'] = "<a
        href='".WEBROOT."/admin/journal.php?id=".$e['id']."&amp;return_to=".urlencode(WEBROOT.'/'.$current_year.'-'.$current_month."#entry-".$e['id'])."'
	class='edit-link'
        title='Edit entry #".$e['id']." (authorisation required).'>
            [Edit]
        </a> [$tag_data]";
    $entry['class'] = 'journal';
    $entry['date_and_time'] = $e['date_and_time'];
    $title =  (!empty($e['title'])) ? '&mdash; '.$e['title'] : '';
    $entry['title'] = "
		<a name='entry-".$e['id']."' href='#entry-".$e['id']."' title='permalink'>
			".date('g:iA',strtotime($e['date_and_time']))."
		</a> $title";
    $entries[$entry['date_and_time'].$x] = $entry;
    $x++;
}







// Get images:
$sql = "SELECT * FROM images WHERE YEAR(date_and_time)=".$db->esc($current_year)." 
	AND MONTH(date_and_time)=".$db->esc($current_month)." ORDER BY date_and_time ASC";
//$res = mysql_query();
//while ($image = mysql_fetch_assoc($res)) {
foreach ($db->fetchAll($sql) as $image) {
    $tag_data = '';
    $tags = $db->fetchAll("SELECT title FROM tags JOIN tags_to_images
        ON (tags.id=tags_to_images.tag)
        WHERE tags_to_images.image='".$db->esc($image['id'])."'");
    foreach ($tags as $tag) {
        $tag_data .= '<a href="'.WEBROOT.'/tags/'.$tag['title'].'">'.$tag['title']."</a>, ";
    }
    $tag_data = substr($tag_data, 0, -2); // Strip trailing comma-space.
    $entry = array(
            'class' => 'centre',
            'date_and_time' => $image['date_and_time'],
            'title' => '',
            'metadata' => "<a href='".WEBROOT."/admin/images.php?action=edit_image&amp;id=".$image['id']."&amp;return_to=".urlencode(WEBROOT.'/'.$current_year.'-'.$current_month)."'
			 class='edit-link' title='Edit metadata for image #".$image['id']." (authorisation required).'>
                         [Edit]</a> [$tag_data]",
            'auth_level' => $image['auth_level']
    );
    if ($image['auth_level']==0 || $image['auth_level']<=$auth->getAuthData('auth_level') ) {
        $entry['body'] = "
            <div class=''>
                <div class=''>
                    <a href='".WEBROOT."/images/".$image['id']."/full'
                       title='Date: ".$image['date_and_time'].".  Click for larger view.'>
                        <img src='".WEBROOT."/images/".$image['id']."/view' alt='Image.' />
                    </a>
                </div>
                <div class='caption'>
                    ".date('g:iA',strtotime($image['date_and_time'])).': '.wikiformat($image['caption'])."
                </div>
            </div>";
    } else {
        $entry['body'] = "<p class='quiet'>Please log in to view this image.</p>";
    }
    $entries[$entry['date_and_time'].$x] = $entry;
    $x++;
}






// Put it all together.
ksort($entries);
$page->addBodyContent("<div class='span-18'>");
if (count($entries) < 1) {
    $page->addBodyContent("<p class='notice'>No journal entries nor images were found.</p>");
}
$day = '';
foreach ($entries as $key=>$entry) {
    /*$new_day = date('l, F j<\s\u\p>S</\s\u\p> Y',strtotime($entry['date_and_time']));
	if ($day!=$new_day) {
		if (!empty($day)) {
			$page->addBodyContent("\t</div><!-- end .day -->\n\t<div class='day'>"); // If we're already in a day, i.e. not at the begining of the month. 
		}
		$day = $new_day;
		$page->addBodyContent("<h2>$day</h2>");
	}
    */
    $page->addBodyContent("
	<div class='entry {$entry['class']}'>
		<h3 class=''>{$entry['title']}</h3>
            {$entry['body']}
		<p class='small quiet'>{$entry['metadata']} [{$entry['auth_level']}]</p>
		<hr />
	</div>
            ");
}
//$page->addBodyContent("</div><!-- end .day -->"); 
$page->addBodyContent("</div><!-- end .blog-entries -->"); 










// Get a random image.
$or_where = ($auth->checkAuth()) ? "OR auth_level<=".$db->esc($auth->getAuthData('auth_level')) : "";
$image = $db->fetchRow("SELECT * FROM images WHERE auth_level=0 $or_where ORDER BY RAND() LIMIT 1");
$page->addBodyContent("
<div class='span-6 last'>
    <div class='box centre'>
        <h3>A Random Image</h3>
        <p>
            <a href='".WEBROOT."/images/{$image['id']}'>
                <img src='".WEBROOT."/images/{$image['id']}/view'
                     alt='".htmlspecialchars($image['caption'])."'
                     title='".htmlspecialchars($image['caption'])."'
                     style='width:100%'
                />
            </a>
        </p>
        <p class='small quiet'>
            ".wikiformat($image['caption'])."
        </p>
    </div>
</div>
");














$page->addBodyContent("
<div class='span-24 last small quiet centre'>
	<ul>
		<li>Thanks to:
			<a href='http://pear.php.net'>PEAR</a> contributors;
			<a href='http://kingdesk.com/projects/php-typography/'>KINGdesk's PHP Typography</a>.
		</li>
		<li><a href='" . WEBROOT . "/admin'>Site Administration</a></li>
	</ul>
</div>");









$page->addBodyContent('</div><!-- end div.container -->');
$page->display();

