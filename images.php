<?php
require_once 'common.php';













// Output an actual image:
if ( isset($_GET['id'])
        && is_numeric($_GET['id'])
        && isset($_GET['size'])
        && ( $_GET['size'] == 'full'
                || $_GET['size'] == 'view'
                || $_GET['size'] == 'thumb'
)
) {
    $filename = DATADIR."/images/".$_GET['size']."/".$_GET['id'].".jpg";
    if (file_exists($filename)) {
        $image_data = $db->fetchRow("SELECT * FROM images WHERE id='".$db->esc($_GET['id'])."' LIMIT 1");
        if ( $image_data['auth_level'] == 0
                || $image_data['auth_level'] <= $auth->getAuthData('auth_level') ) {
            $length = filesize($filename);
            header('Content-type: image/jpeg');
            header('Content-Length: '.$length);
            header('Content-Disposition: inline; filename="image-'.basename($filename).'"');
            readfile($filename);
            die();
        } else {
            die("Access denied.");
        }
    } else {
        die("Image not found: $filename");
    }
}













//Single image:

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    $image_data = $db->fetchRow("SELECT * FROM images WHERE id='".$db->esc($id)."' LIMIT 1");
    if (!$image_data) die("Image #$id not found.");
    $page->setTitle(SITETITLE . ': Image #'.$image_data['id']);
    $css->parseString('
        body {background-color:black; text-align:center; margin:1.4em }
        .container > ul { display:none }
    ');

    /*
	$prev = $db->fetchRow("SELECT * FROM images WHERE date_and_time<='".$db->esc($image_data['date_and_time'])."' AND id!='".$db->escape($id)."' ORDER BY date_and_time DESC LIMIT 1");
	$next = $db->fetchRow("SELECT * FROM images WHERE date_and_time>='".$db->esc($image_data['date_and_time'])."' AND id!='".$db->escape($id)."' AND id!='".$db->escape($prev[0]['id'])."' ORDER BY date_and_time ASC LIMIT 1");
	$page->addBodyContent("<p class='centre'>");
	if ($prev) $page->addBodyContent("<a href='/images/".$prev['id']."'>&laquo; Previous</a> | ");
	if ($next) $page->addBodyContent("<a href='/images/".$next['id']."'>Next &raquo;</a>");
	$page->addBodyContent("</p>");
    */

    $date_format = (date('H:i',strtotime($image_data['date_and_time']))=='00:00')
            ? "F j<\s\u\p>S</\s\u\p> Y"
            : "g:iA, F j<\s\u\p>S</\s\u\p> Y";
    if ( $image_data['auth_level']==0 || $image_data['auth_level']<=$auth->getAuthData('auth_level') ) {
        $css->parseString(
            ".caption {
                text-align: justify;
                width: 60ex;
                background-color: white;
                color: black;
                padding: 1em;
                margin: auto;
                border:1px solid #999;
            }"
        );
        $date_timestamp = strtotime($image_data['date_and_time']);
        $page->addBodyContent("
            <div style=''>
                <p>
                    <img src='".WEBROOT."/images/".$image_data['id']."/view' />
                </p>
                <div class='caption'>
                    <p>
                        <a href='".WEBROOT."/".date('Y',$date_timestamp)."-".date('m',$date_timestamp)."'>
                            ".date($date_format,$date_timestamp)."
                        </a>
                    </p>
                    ".wikiformat($image_data['caption'])."
                </div>
            </div>
            ");
    } else {
        $page->addBodyContent("<p>Please log in.</p>");
    }
}







// Year's images:
else { // If no ID specified:
    $year = (isset($_GET['year'])) ? $_GET['year'] : date('Y');
    $page->addBodyContent("<h1>Images from $year</h1>");
    $num_columns = 8;
    require_once 'HTML/Table.php';
    $table = new HTML_Table(array('class'=>'thumbs'));
    $res = $db->query("SELECT * FROM images WHERE YEAR(date_and_time)=".$db->esc($year)." ORDER BY date_and_time ASC");
    $images = $res->fetchAll();
    $i = 0;
    for ($row=0; $row<(ceil(count($images)/$num_columns)); $row++) {
        $cells = array();
        for ($col=0; $col<=$num_columns; $col++) {
            if (isset($images[$i])) {
                $cell_contents = "<a href='".WEBROOT."/images/".$images[$i]['id']."'>";
                //if ( $images[$i]['auth_level']==0 || ($images[$i]['auth_level']>0 && $_SESSION['logged_in']) ) {
			    if ( $images[$i]['auth_level']==0 || $images[$i]['auth_level'] <= $auth->getAuthData('auth_level') ) {
                    $cell_contents .= "<img src='".WEBROOT."/images/".$images[$i]['id']."/thumb' />";
                } else {
                    $cell_contents .= "[Image #".$images[$i]['id']."]";
                }
                $cells[] = $cell_contents."</a>";
            } else {
                $cells[] = '';
            }
            $i++;
        }
        $table->addRow($cells);
    }
    $page->addBodyContent($table);

}


$page->addBodyContent('</div><!-- end div.container -->');
$page->display();
