<?php
require_once 'common.php';
$page->setTitle('Feeds');

$entries = array();
require_once 'XML/Feed/Parser.php';
$res = mysql_query("SELECT * FROM feeds");
while ($feed_data = mysql_fetch_assoc($res)) {
	$ch = curl_init();
	$timeout = 5; // set to zero for no timeout
	curl_setopt ($ch, CURLOPT_URL, $feed_data['url']);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$feed_source = curl_exec($ch);
	curl_close($ch);
	$feed = null;
	if (!empty($feed_source)) {
		try {
			$feed = new XML_Feed_Parser($feed_source);
		} catch (XML_Feed_Parser_Exception $e) {
			$page->addBodyContent(
				"<div class='error'>The data from
				<a href='".$feed_data['url']."'>".$feed_data['url']."</a> is
				not a valid feed, and throws the exception:
				<pre><code>".$e->getMessage()."</code></pre>
				</div>");
		}
	}
	if ($feed) {
		$feeds[$feed_data['url']] = $feed->title;
		error_reporting(E_ERROR);
		foreach ($feed as $entry) {
			if ($entry->published) $date = $entry->published;
			else if ($entry->date) $date = $entry->date;
			else $date = null;
			$entries[] = array(
				'site' => $feed->title,
				'title' => $entry->title,
				'link' => $entry->link,
				'date' => $date
			);
		}
		error_reporting(E_ALL);
	}
}

// Obtain a list of columns
foreach ($entries as $key => $row) {
    $dateArray[$key]  = $row['date'];
}

// Sort the data with volume descending, edition ascending
// Add $data as the last parameter, to sort by the common key
array_multisort($dateArray, SORT_DESC, $entries);

$page->addBodyContent("<div class='span-24 last'>
	<ul>");
$last_date = 0;
$bkgclr = '#eee';
for ($i=0; $i < $number_of_feed_enteries_to_show; $i++) {
	$entry = $entries[$i];
	$date_text = date('F jS',$entry['date']);
	if ($last_date!=$date_text) {
		if ($bkgclr!='white') {
			$bkgclr = 'white';
		} else {
			$bkgclr = '#eee';
		}
	}
	$last_date = $date_text;
	$page->addBodyContent("<li style='background-color:$bkgclr'>
		<strong>".$entry['site'].":</strong>
		<a target='_blank' href='".$entry['link']."'>".$entry['title']."</a>
		($date_text)
		</li>");
}
$page->addBodyContent("    </ul>
</div>
<div class='span-24 last'>
".$db->getHtmlTable('feeds','select * from feeds')->toHtml()."
</div>");

$page->addBodyContent("</div><!-- end .container -->");
$page->display();
