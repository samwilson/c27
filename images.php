<?php
require_once 'common.php';













// Output an actual image:
if ( isset($_GET['id']) && isset($_GET['size']) && ($_GET['size']=='full'||$_GET['size']=='view'||$_GET['size']=='thumb') ) {
	$filename = DATADIR."/images/".$_GET['size']."/".$_GET['id'].".jpg";
	if (file_exists($filename)) {
		$res = $mdb2->query("SELECT * FROM images WHERE id='".$mdb2->escape($_GET['id'])."' LIMIT 1");
		$image_data = $res->fetchRow();
		if ( $image_data['auth_level']==0 || $image_data['auth_level']<=$auth->getAuthData('auth_level') ) {
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
		die("Image not found.");
	}
}













//Single image:

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	$id = $_GET['id'];
	$res = $mdb2->query("SELECT * FROM images WHERE id='".$mdb2->escape($id)."' LIMIT 1");
	$image_data = $res->fetchAll();
	if (count($image_data)==0) die("IMG-$id not found.");
	$image_data = $image_data[0];
	$page->setTitle('SAM/IMAGE-'.$image_data['id']);
	
	$res = $mdb2->query("SELECT * FROM images WHERE date_and_time<='".$mdb2->escape($image_data['date_and_time'])."' AND id!='".$mdb2->escape($id)."' ORDER BY date_and_time DESC LIMIT 1");
	$prev = $res->fetchAll();
	$res = $mdb2->query("SELECT * FROM images WHERE date_and_time>='".$mdb2->escape($image_data['date_and_time'])."' AND id!='".$mdb2->escape($id)."' AND id!='".$mdb2->escape($prev[0]['id'])."' ORDER BY date_and_time ASC LIMIT 1");
	$next = $res->fetchAll();
	$page->addBodyContent("<p class='centre'>");
	if (isset($prev[0])) $page->addBodyContent("<a href='/images/".$prev[0]['id']."'>&laquo; Previous</a> | ");
	if (isset($next[0])) $page->addBodyContent("<a href='/images/".$next[0]['id']."'>Next &raquo;</a>");
	$page->addBodyContent("</p>");
	
	$date_format = (date('H:i',strtotime($image_data['date_and_time']))=='00:00')
		? "F j<\s\u\p>S</\s\u\p> Y"
		: "g:iA F j<\s\u\p>S</\s\u\p> Y";
	if ( $image_data['auth_level']==0 || $image_data['auth_level']<=$auth->getAuthData('auth_level') ) {
		$css->parseString(".caption {text-align:justify; width:60ex}");
		$date_timestamp = strtotime($image_data['date_and_time']);
		$page->addBodyContent("
		<p><img src='/images/".$image_data['id']."/view' /></p>
		<p><a href='/".date('Y',$date_timestamp)."-".date('m',$date_timestamp)."'>".date($date_format,$date_timestamp)."</a></p>
		<div class='caption'>".wikiformat($image_data['caption'])."</div>
		");
	} else {
		$page->addBodyContent("<p>Please log in.</p>");
	}
}







// Year's images:
else { // If no ID specified:
	$year = (isset($_GET['year'])) ? $_GET['year'] : date('Y');
	$num_columns = 8;
	require_once 'HTML/Table.php';
	$table = new HTML_Table(array('class'=>'thumbs'));
	$res = $mdb2->query("SELECT * FROM images WHERE YEAR(date_and_time)=".$mdb2->escape($year)." ORDER BY date_and_time ASC");
	$images = $res->fetchAll();
	$i = 0;
	for ($row=0; $row<(ceil(count($images)/$num_columns)); $row++) {
		$cells = array();
		for ($col=0; $col<=$num_columns; $col++) {
			if (isset($images[$i])) {
				$cell_contents = "<a href='/images/".$images[$i]['id']."'>";
				if ( $images[$i]['auth_level']==0 || ($images[$i]['auth_level']>0 && $_SESSION['logged_in']) ) {
					$cell_contents .= "<img src='/images/".$images[$i]['id']."/thumb' />";
				} else {
					$cell_contents .= "[IMG-".$images[$i]['id']."]";
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

$page->display();

?>