<?php
require_once 'common.php';
require_once 'HTTP/Upload.php';
require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/file.php';
require_once 'HTML/QuickForm/submit.php';
require_once 'HTML/QuickForm/textarea.php';
$page->setTitle('Images');







if ( isset($_GET['show_image'])
     && isset($_GET['id'])
     && is_numeric($_GET['id'])
     && isset($_GET['size'])
     && ( $_GET['size'] == 'full'
          || $_GET['size'] == 'view'
          || $_GET['size'] == 'thumb'
        )
) {
    $filename = DATADIR.'/images/'.$_GET['size'].'/'.$_GET['id'].'.jpg';
    
    // Make a viewable size if there isn't one.
    if (!file_exists($filename)) {
        scaleimage($_GET['id'], $_GET['size']);
    }

    // Display the image.
    if (file_exists($filename)) {
        $length = filesize($filename);
        header('Content-type: image/jpeg');
        header('Content-Length: '.$length);
        header('Content-Disposition: inline; filename="'.basename($filename).'"');
        readfile($filename);
        die();
    } else {
        $page->addBodyContent("<p class='error'>File Not Found: $filename</p>");
    }

}






















// Navigation:
/*
$page->addBodyContent("<div class='span-24 last'><ul class='tabs'>");
$year = (isset($_GET['year'])) ? $_GET['year'] : '';
$years = $db->fetchAll("SELECT YEAR(date_and_time) AS year FROM images GROUP BY YEAR(date_and_time)");
foreach ($years as $y) {
	$y = $y['year'];
	if ($year == $y) {
		$page->addBodyContent("<li class='selected'>$y</li>");
	} else {
		$page->addBodyContent("<li><a href='?year=$y'>$y</a></li>");
	}
}
$page->addBodyContent(" </ol><ol class='tabs lev2'>");
$all_months = array(0=>'(Unspecified)',1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December');
$month = (isset($_GET['month'])) ? $_GET['month'] : 1;
$months = $db->fetchAll("SELECT MONTH(date_and_time) AS month FROM images WHERE YEAR(date_and_time)='$year' GROUP BY MONTH(date_and_time)");
foreach ($months as $m) {
	$m = $m['month'];
	if ($month == $m) {
		$page->addBodyContent("<li class='selected'>{$all_months[$m]}</li>");
	} else {
		$page->addBodyContent("<li><a href='?year=$year&month=$m'>{$all_months[$m]}</a></li>");
	}
}
$page->addBodyContent("</ol></div>");
*/











if (isset($_POST['upload_image'])) {
    require_once "HTTP/Upload.php";
    $uploadTo = DATADIR.'/images/IN/';
    $upload = new HTTP_Upload("en");
    $file = $upload->getFiles("image");
    if ($file->isValid()) {
        $moved = $file->moveTo($uploadTo, false);
        if (PEAR::isError($moved)) {
            $page->addBodyContent("<p class='message error'>Badness happened: ".$moved->getMessage()."</p>");
        }
    } elseif ($file->isError()) {
        $page->addBodyContent("<p class='message error'>Badness happened: ".$file->errorMsg()."</p>");
    }
    $uploadedImageFilename = "$uploadTo/".$file->getProp('name');
    $id = importImage($uploadedImageFilename);
    header("Location:?action=edit_image&id=$id");
    die();
}











// Process next image:
if (isset($_GET['process_next_image'])) {
    $imagesINdir = DATADIR.'/images/IN';
    if (!is_dir($imagesINdir)) {
        if (!@mkdir($imagesINdir, 0755, true)) {
            $page->addBodyContent(
                '<p class="error">Unable to create new directory:<br />'.
                '<code>'.$imagesINdir.'</code></p>'
            );
            $page->display();
            die();
        }
    }
    foreach (scandir($imagesINdir) as $file) {
        if (substr($file, 0, 1)=='.' || is_dir($imagesINdir.'/'.$file)) continue;
        $fullname = $imagesINdir.'/'.$file;
        $id = importImage($fullname);
        header("Location:?action=edit_image&id=$id");
        die();
    }
}
// Process 10 images:
if ( isset($_GET['process_pending_images']) ) {
    $imagesINdir = DATADIR.'/images/IN';
    $numberToProcess = 10;
    foreach (scandir($imagesINdir) as $file) {
        if (substr($file, 0, 1)=='.' || is_dir($imagesINdir.'/'.$file)) continue;
        $fullname = $imagesINdir.'/'.$file;
        importImage($fullname);
        $numberToProcess--;
        if ($numberToProcess==0) {
            break;
        }
    }
}
function importImage($fullname) {
    global $db, $page;
    $title = basename($fullname);
    $date = '1111-11-11 11:11:11';
    if ($exif_date = @exif_read_data($fullname, 'IFD0', 0)) {
        if (isset($exif_date['DateTime'])) {
            $date = $exif_date['DateTime'];
        }
        if (isset($exif_date['CreateDate'])) {
            $date = $exif_date['CreateDate'];
        }
    } elseif ( preg_match('|([0-9]{4}-[0-9]{2}-[0-9]{2}).(([0-9]{2})([0-9]{2}))?(.*)jpg|i',$title,$date_matches) > 0 ) {
        //print_r($date_matches);
        $hour = (isset($date_matches[3])) ? $date_matches[3] : '00';
        $minute = (isset($date_matches[4])) ? $date_matches[4] : '00';
        $date = $date_matches[1]." $hour:$minute";
        $title = (isset($date_matches[5])) ? trim($date_matches[5]) : $title;
    }
    $title = str_replace('_', ' ', $title);
    mysql_query("INSERT INTO images SET caption='".$db->esc($title)."', date_and_time='".$db->esc($date)."', auth_level='10'");
    if (mysql_error()) {
        $page->addBodyContent("<p class='error'>Something went wrong with <code>$file</code>: ".mysql_error()."</p>");
    } else {
        $id = mysql_insert_id();
        $destFilename = DATADIR."/images/full/$id.jpg";
        // Create destination directory if neccessary.
        $destDir = dirname($destFilename);
        if (!is_dir($destDir)) {
            if (!@mkdir($destDir, 0755, true)) {
                $page->addBodyContent(
                    '<p class="error">Unable to create new directory:<br />'.
                    '<code>'.$destDir.'</code></p>'
                );
                $page->display();
                die();
            } else {
                $page->addBodyContent(
                    '<p class="success">Created new directory:<br />'.
                    '<code>'.$destDir.'</code></p>'
                );
            }
        }
        if (!rename($fullname, $destFilename)) {
            $page->addBodyContent(
                "<p class='error'>".
                "Could not move<br />$fullname<br />to<br />$destFilename".
                "</p>"
            );
        }

        // make other sizes:
        scaleimage($id, 'thumb');
        scaleimage($id, 'view');
        
        $page->addBodyContent("<div class='success span-24 last'>Image $id accessioned.</div>");
        return $id;
    }
}











// Pending file count:
$inDir = DATADIR . '/images/IN';
$pendingCount = 0;
if (is_dir($inDir)) {
    $pendingCount = count(preg_grep("/^[^\.]/",scandir($inDir)));
}







// Upload form:
$form = new HTML_QuickForm('','post',$_SERVER['PHP_SELF']);
$file_element = new HTML_QuickForm_file('image','');
$submit_element = new HTML_QuickForm_submit('upload_image','Go!');
$form->addGroup(array($file_element,$submit_element),'','Upload: ');
$page->addBodyContent("
    <div class='span-10'>
            ".$form->toHtml()."
    </div>
    <div class='span-13 last'>
        <p>
            $pendingCount images remain to be accessioned.
            <a href='?process_pending_images'>Process ten.</a>
            <a href='?process_next_image'>Process one.</a>
        </p>
    </div>
");



















// Rotate
if ( isset($_GET['rotate']) && is_numeric($_GET['rotate']) && isset($_GET['id']) ) {
    $degrees = $_GET['rotate'];
    $id = $_GET['id'];
    shell_exec("convert -rotate $degrees ".DATADIR."/images/full/$id.jpg ".DATADIR."/images/full/$id.jpg");
    shell_exec("convert -rotate $degrees ".DATADIR."/images/view/$id.jpg ".DATADIR."/images/view/$id.jpg");
    shell_exec("convert -rotate $degrees ".DATADIR."/images/thumb/$id.jpg ".DATADIR."/images/thumb/$id.jpg");
    header("Location:?action=edit_image&id=$id");
}















if (!empty($_POST)) {
    $db->save('images', $_POST);
            /*array(
            'id' => $_POST['id'],
            'date_and_time' => $_POST['date_and_time'],
            'caption' => $_POST['caption'],
            'auth_level' => $_POST['auth_level']*/
    //));

    // Save tags
    $db->query("DELETE FROM tags_to_images WHERE image=".$db->esc($_POST['id']));
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
            $db->save('tags_to_images', array('tag'=>$tag_id,'image'=>$_POST['id']));
        }
    }


}



















// Show 1 image edit form.
function image_edit_url($id) {
    return "?action=edit_image&id=$id&return_to=".urlencode("?action=edit_image&id=$id");
}
if (isset($_GET['action']) && $_GET['action']=='edit_image' && isset($_GET['id'])) {

    $this_image = $db->fetchAll("SELECT id, date_and_time, caption, auth_level, year(date_and_time) as year, month(date_and_time) as month ".
            "FROM images WHERE id='".$db->esc($_GET['id'])."' LIMIT 1");
    $this_image = $this_image[0];
    $prev = $db->fetchAll("SELECT * FROM images WHERE date_and_time<='".$db->esc($this_image['date_and_time'])."' AND id!='".$db->esc($_GET['id'])."' ORDER BY date_and_time DESC LIMIT 1");
    $prev_id = (isset($prev[0])) ? $db->esc($prev[0]['id']) : 0;
    $next = $db->fetchAll("SELECT * FROM images WHERE date_and_time>='".$db->esc($this_image['date_and_time'])."' AND id!='".$db->esc($_GET['id'])."' AND id!='$prev_id' ORDER BY date_and_time ASC LIMIT 1");
    /*$page->addBodyContent("<p class='centre'>");
	if (isset($prev[0])) $page->addBodyContent("<a href='".image_edit_url($prev[0]['id'])."'>&laquo; Previous</a> | ");
	if (isset($next[0])) $page->addBodyContent("<a href='".image_edit_url($next[0]['id'])."'>Next &raquo;</a><br />");
	$page->addBodyContent("</p>");
    */

    // Tags:
    $tag_data = "";
    $tags = $db->fetchAll("SELECT title FROM tags JOIN tags_to_images on (tags.id=tags_to_images.tag) WHERE tags_to_images.image='".$db->esc($_GET['id'])."'");
    foreach ($tags as $tag) {
        $tag_data .= $tag['title'].", ";
    }
    $tag_data = substr($tag_data, 0, -2); // Strip trailing comma-space.

    $page->addBodyContent("<hr />
	<div class='span-11'>
            <img src='?show_image&size=view&id=".$_GET['id']."' style='max-width:100%' /><br />
            Rotate
            <a href='?rotate=90&id={$_GET['id']}'>90&deg;</a>,
            <a href='?rotate=180&id={$_GET['id']}'>180&deg;</a>, or
            <a href='?rotate=270&id={$_GET['id']}'>270&deg;</a> clockwise.
	</div>
	<div class='span-13 last'>
            <form action='images.php?action=edit_image&id=".$this_image['id']."' method='post'>
            <div class='hide'>
                    <input type='hidden' name='save_image' value='true' />
                    <input type='hidden' name='id' value='".$this_image['id']."' />
            </div>

            <div class='span-3'>
                    <label for='date_and_time'>Date:</label>
            </div>
            <input class='span-10 last' type='text' name='date_and_time' value='".$this_image['date_and_time']."' id='date_and_time' />

            <div class='span-13 last'>
                    <label for='caption'>Caption:</label>
            </div>
            <textarea class='span-13 last' style='height:4em' id='caption' name='caption'>".$this_image['caption']."</textarea>

            <div class='span-3'>
                    <label for='tags'>Tags:</label>
            </div>
            <input type='text' name='tags' value='$tag_data' class='span-10 last'/>

            <div class='span-13 last'>
        ".getAuthLevelRadios($this_image['auth_level'])."
            </div>

            <div class='span-13 last'>
                <input type='submit' name='save_image' value='Save' /> or save and return to
                <input type='submit' name='return_to' value='?process_next_image' />
                <input type='submit' name='return_to' value='".WEBROOT."/".$this_image['year']."-".$this_image['month']."' />
            </div>
            </form>
	");
    foreach (exif_read_data(DATADIR.'/images/full/'.$this_image['id'].'.jpg') as $name=>$value) {
        $page->addBodyContent("<tr><th>$name</th><td>$value</td></tr>");
    }
    $page->addBodyContent("</div>");

}









// //Thumb table:
// elseif (!empty($year)) {
// 	$num_columns = 8;
// 	$css->parseString("
// 		td {font-size:0.8em}
// 		td img {float:left; margin:0 4px 4px 0}
// 		.caption {color:#060}
// 	");
// 	require_once 'HTML/Table.php';
// 	$table = new HTML_Table();
// 	$images = $db->fetchAll("
// 		SELECT images.id, images.date_and_time, images.caption, auth_levels.name AS auth_level
// 		FROM images, auth_levels
// 		WHERE auth_levels.id=images.auth_level AND YEAR(date_and_time)=".$db->esc($year)." AND MONTH(date_and_time)=".$db->esc($month)."
// 		ORDER BY date_and_time ASC
// 	");
// 	$i = 0;
// 	for ($row=0; $row<=ceil(count($images)/$num_columns); $row++) {
// 		$cells = array();
// 		for ($col=0; $col<$num_columns; $col++) {
// 			if (isset($images[$i])) {
// 				$cells[] = "<p><a href='?show_image&size=view&id={$images[$i]['id']}'>
// 					<img src='?show_image&size=thumb&id={$images[$i]['id']}' /></a></p>
// 					<p>".date("g:iA, F j<\s\u\p>S</\s\u\p>",strtotime($images[$i]['date_and_time'])).":</p>
// 					<div class='caption'>".wikiformat($images[$i]['caption'])."</div>
// 					[".$images[$i]['auth_level']."]".
// 					"<a href='?action=edit_image&id=".$images[$i]['id']."'>[e]</a>".
// 					"<a href='?action=delete&table_name=images&id={$images[$i]['id']}&return_to=".urlencode($_SERVER['REQUEST_URI'])."' class='delete'>[d]</a>
// 					</p>";
// 			} else {
// 				$cells[] = '';
// 			}
// 			$i++;
// 		}
// 		$table->addRow($cells);
// 	}
// 	$page->addBodyContent($table);
// }




$page->addBodyContent('</div><!-- end div.container -->');
$page->display();






/**
 *
 * @return void
 */
function scaleimage($id, $sizeLabel) {
    global $page;
    require_once 'Image/Transform.php';
    if ($sizeLabel=='thumb') {
        $size = 80;
    }
    if ($sizeLabel=='view') {
        $size = 500;
    }
    $original = DATADIR."/images/full/$id.jpg";
    $transformer = Image_Transform::factory(DRIVER_FOR_IMAGE_TRANSFORM);
    if (PEAR::isError($transformer)) {
        $page->addBodyContent('Error with instantiating Image_Transform: '.$transformer->getMessage(), 'error');
    }
    $ret = $transformer->load($original);
    if (PEAR::isError($ret)) {
        $page->addBodyContent("Unable to load $original.  ".$ret->getMessage(), 'error');
    }
    $ret = $transformer->scaleMaxLength($size);
    if (PEAR::isError($ret)) {
        $page->addBodyContent("Unable to scale image $original to $size.  ".$ret->getMessage(), 'error');
    }
    $ret = $transformer->save(DATADIR."/images/$sizeLabel/$id.jpg");
    if (PEAR::isError($ret)) {
        $page->addBodyContent("Error with saving to ".DATADIR."/images/$sizeLabel/$id.jpg<br />".$ret->getMessage(), 'error');
    }
}
