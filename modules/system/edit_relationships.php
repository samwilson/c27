<?php
/* $Id$ */

//----------------------------------------------------------------------------//
//                       Related page <select>.                               //
//----------------------------------------------------------------------------//
//Get parent page information, and build <select>
	$sql = "SELECT id FROM pages";
	$result = mysql_query($sql);
	if ($num_rows = mysql_num_rows($result) > 0) {
		$related_page_select = ("<select name='related_page_id'>\n");
		for ($row_num=0; $row_num<$num_rows; $row_num++) {
			$row = mysql_fetch_assoc($result);
			$parents_path[$row['id']] = MakePath($row['id'], 0);
		}
		asort ($parents_path);
		foreach ($parents_path as $parent_id => $parent_path) {
			if ($parent_id == $EditPage['parent_id']) {
				$selected = 'selected';
			} else {
				$selected = '';
			}
	
			$related_page_select .= (
				"<option value='$parent_id' $selected>$parent_path</option>
			");
		}
		$related_page_select .= "</select>\n";
	}


if (!isset($_GET['page_id'])) {
	$Page['error_message'] = "<p>You have not specified for which page you ".
		"wish to define relationships.</p>";
} else {
	$page_id = $_GET['page_id'];
	$sql = "SELECT * FROM pages WHERE id=$page_id";
	$result = mysql_query($sql);
	if (mysql_num_rows($result) < 1) {
		$Page['error_message'] .= "<p>No page was found to relate with.</p>";
	} else {
		$related_page = mysql_fetch_assoc($result);
		$sql = "SELECT id, title FROM pages";
		$result = mysql_query($sql);
		if (mysql_num_rows($result) > 0) {
			$page_select = "<select name='related_page_id'>";
			while ($row = mysql_fetch_assoc($result)) {
				$page_select .= "<option value='".$row['id']."'>".$row['title']."</option>";
			}
			$page_select .= "</select>";
		}
		$Page['body'] .= (
			"<form action='' method='post'>\n".
			"  <h3>New relationship</h3>".
			"    <div class='subsection'>\n".
			"      <div class='input' style='width:100%'>".
			"        <label>Relate <em>".$related_page['title']."</em> with</label>".
			"        $page_select".
			"      </div>\n".
			"      <div class='input' style='width:100%'>".
			"        <label>Tagline</label>".
			"        <input type='text' name='tagline' />".
			"      </div>\n".
			"      <div class='input' style='width:100%'>".
			"        <label>Description</label>".
			"        <textarea name='description' rows='5'></textarea>".
			"      </div>\n".
			"    </div>\n".
			"    <p class='submit'>".
			"      <input type='submit' name='add_relationship' ".
			                                      "value='Add relationship' />".
			"    </p>\n".
			"</form>\n"
		);
	}
}

?>