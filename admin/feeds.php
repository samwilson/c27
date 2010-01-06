<?php
require_once 'common.php';
$page->setTitle('Feeds');

$entries = array();
require_once 'XML/Feed/Parser.php';
$feeds = $db->fetchAll("SELECT * FROM feeds");
foreach ($feeds as $feedData) {
    $ch = curl_init();
    $timeout = 4; // set to zero for no timeout.
    curl_setopt ($ch, CURLOPT_URL, $feedData['url']);
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
                <a href='".$feedData['url']."'>".$feedData['url']."</a> is
                not a valid feed, and throws the exception:
                <pre><code>".$e->getMessage()."</code></pre>
                </div>"
            );
        }
    }
    if ($feed) {
        $feeds[$feedData['url']] = $feed->title;
        error_reporting(E_ERROR);
        foreach ($feed as $entry) {
            if ($entry->published) $date = $entry->published;
            else if ($entry->date) $date = $entry->date;
            else if ($entry->updated) $date = $entry->updated;
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
$dateArray = array();
foreach ($entries as $key => $row) {
    $dateArray[$key]  = $row['date'];
}

// Sort the data with volume descending, edition ascending
// Add $data as the last parameter, to sort by the common key
array_multisort($dateArray, SORT_DESC, $entries);

$page->addBodyContent("<div class='span-24 last'>");
$last_date = false;
$bkgclr = '#eee';
$numEntriesToShow = min($number_of_feed_enteries_to_show, count($entries));
for ($i=0; $i < $numEntriesToShow; $i++) {
    $entry = $entries[$i];
    $date_text = date('F j<\s\u\p>S</\s\u\p>',$entry['date']);
    if ($last_date != $date_text || !$last_date) {
        if ($bkgclr!='white') {
            $bkgclr = 'white';
        } else {
            $bkgclr = '#eee';
        }
        $page->addBodyContent("</ul><div style='background-color:$bkgclr'><h2>$date_text</h2><ul>");
    }
    $last_date = $date_text;
    $page->addBodyContent(
        "<li>
        <strong>".$entry['site'].":</strong>
        <a target='_blank' href='".$entry['link']."'>".$entry['title']."</a>
        </li>"
    );
}
$page->addBodyContent("</ul></div>");
$page->addBodyContent("</div><!-- end .container -->");
$page->display();
