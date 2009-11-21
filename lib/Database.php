<?php
require_once 'MDB2.php';

class Database {
	
	var $mdb2;
	
	function Database($dsn) {

		$this->mdb2 = MDB2::connect($dsn);
		$this->mdb2->setFetchMode(MDB2_FETCHMODE_ASSOC);
		
		/*
		// Add, edit, or delete records:
		if ( isset($_REQUEST['table_name']) ) {
			//if (!isset($_REQUEST['action'])) $_REQUEST['action']='add';
			if ( isset($_POST['save'])) {
				$this->save($_POST['table_name'], $_POST);
			} elseif ( (isset($_REQUEST['delete']) || (isset($_REQUEST['action']) && $_REQUEST['action']=='delete')) && isset($_REQUEST['id']) ) {
				$this->delete($_REQUEST['table_name'], $_REQUEST['id']);
			} elseif (
					((isset($_REQUEST['edit']) || (isset($_REQUEST['action']) && $_REQUEST['action']=='edit')) && isset($_REQUEST['id']))
					|| (isset($_REQUEST['add']) || (isset($_REQUEST['action']) && $_REQUEST['action']=='add'))
				) {
				global $page;
				$page->setTitle($_REQUEST['table_name']);
				$page->addBodyContent($this->getHtmlForm());
				$page->display();
				die();
			}
		}
		*/
		
	}
	
	function delete($tbl, $id) {
		global $page;
		if (isset($_REQUEST['delete_confirmation'])) {
			$this->query("DELETE FROM ".$this->esc($tbl)." WHERE id=".$this->esc($id));
			$page->addBodyContent("<div class='success'><p>Record deleted. <a href='".$_SERVER['PHP_SELF']."'>Continue &raquo;</a></p>");
		} else {
			$page->addBodyContent($this->getHtmlForm());
		}
		$page->display();
		die();
	}


	function getHtmlTable($tbl, $sql, $with_actions=true) {
		require_once 'HTML/Table.php';
		$rows = $this->fetchAll($sql);
		$table = new HTML_Table();
		$headings = array();
		if (count($rows)>0) {
			$headings = array_map('titlecase',array_keys($rows[0]));
		}
		if ($with_actions) $headings[] = 'Actions';
		$table->addRow($headings,null,'th');
		if (count($rows)>0) {
			foreach ($rows as $row) {
				//for ($r=0;$r<count($row); $r++) {
				foreach ($row as $cell_name=>$cell_value) {
					//$cell_names = array_keys($row);
					//$cell_name = $cell_names[$r];
					$cell_type = $this->getFieldType($tbl,$cell_name);
					if ($cell_type=='text'||$cell_type=='longtext') {
						$row[$cell_name] = wikiformat($row[$cell_name]);
					}
				}
				if ($with_actions) {
					$actions  = "<a href='?action=edit&table_name=$tbl&id=".$row['id']."'>[e]</a>";
					$actions .= "<a href='?action=delete&table_name=$tbl&id=".$row['id']."' class='delete'>[d]</a>";
					$row[] = $actions;
				}
				$table->addRow(array_values($row));
			}
		}
		if ($with_actions) {
			$cell = "<a href='?action=add&table_name=$tbl&return_to=".urlencode($_SERVER['REQUEST_URI']."#add-$tbl");
			$cell .= (isset($_REQUEST['id'])) ? "&".$_REQUEST['table_name']."_id=".$_REQUEST['id'] : '';
			$cell .= "' name='add-$tbl'>Add New Row</a>";
			$table->setCellContents($table->getRowCount(), $table->getColCount()-1, $cell);
		}
		return $table;
	}
		
	function query($sql) {
		$res = $this->mdb2->query($sql);
		if (PEAR::isError($res)) {
			$page = new HTML_Page2();
			$page->setTitle($res->message);
			$page->addBodyContent("<h1>$res->message</h1><pre>$res->userinfo</pre>");
			$page->display();
			die();
		} else {
			return $res;
		}
	}
	
	function getHtmlForm() {
		require_once 'HTML/QuickForm.php';
		require_once 'HTML/QuickForm/textarea.php';
		require_once 'HTML/QuickForm/select.php';
		$tbl = $_REQUEST['table_name'];
		
		//$data = array(); //$_REQUEST;
		// Editing or deleting?  Get data from DB.
		if (	(isset($_REQUEST['edit']) || isset($_REQUEST['delete']) || $_REQUEST['action']=='edit' || $_REQUEST['action']=='delete')
				&& isset($_REQUEST['id']) 
				&& isset($_REQUEST['table_name'])
			) {
			$data = $this->fetchAll("SELECT * FROM ".$this->esc($tbl)." WHERE id='".$this->esc($_REQUEST['id'])."' LIMIT 1");
			$data = $data[0];
			if (count($data)<1) {
				die("Not Found");
			}
		}
		
		// New record?  Get DB defaults.
		else {
			$data = $this->getDefaultFieldValues($tbl);
		}
		
		// Build form.
		$form = new HTML_QuickForm('','post',$_SERVER['PHP_SELF']);
		$form->addElement('hidden', 'table_name', $tbl);
		if (isset($_REQUEST['return_to'])) {
			$form->addElement('hidden','return_to',$_REQUEST['return_to']);
		}
		$form->addElement('header', null, titlecase($_REQUEST['action'].': '.$tbl));
		if (isset($data['id']) && is_numeric($data['id']) && $data['id']>0) {
			$form->addElement('hidden', 'id', $data['id']);
		}
		$form->setDefaults($data);
		$fields = $this->getFieldNames($tbl);
		foreach ($fields as $field) {
			if ($field=='id') continue;
				
			$field_type = $this->getFieldType($tbl,$field);
			$field_label = titlecase($field)." <span style='font-size:smaller'>(".$this->getFieldType($tbl,$field).")</span>: ";
			if ($field_type=='text' || $field_type=='longtext') {
				$textarea = new HTML_QuickForm_textarea($field, $field_label);
				$textarea->setRows(12);
				$textarea->setCols(80);
				$form->addElement($textarea);
				$form->addElement('static','','Formatting: ',wikiformat_doco());
				
			} elseif ($foreign_table = $this->getReferencedTableName($tbl,$field)) {
				$form->addElement($this->getHtmlSelect($field, $field_label, $foreign_table));
				
			} else {
				$form->addElement('text', $field, $field_label, array('size'=>'80'));
				
			}
		}
		if ( isset($_REQUEST['edit']) || isset($_REQUEST['add']) || $_REQUEST['action']=='edit' || $_REQUEST['action']=='add' ) {
			$form->addElement('submit', 'save', 'Save');
		} elseif (isset($_REQUEST['delete']) || $_REQUEST['action']=='delete') {
			$form->addElement('hidden', 'delete_confirmation', true);
			$form->addElement('submit', 'delete', 'Delete!');
		}
		$out = $form->toHtml();
		if (isset($data['id'])) { 
			$out .= $this->getHtmlFormForJoinTables($tbl, $data['id']);
		}
		return $out;
	}
	
	/**
	 * Join tables are of the form <first_table_name>_to_<second_table_name>, and must comprise 3
	 * fields: 'id', '<first_table_name>_id', and '<second_table_name>_id'.
	 */
	function getHtmlFormForJoinTables($current_table, $current_id) {
		$out = "";
		foreach ($this->getJoinTables($current_table) as $join_table=>$joined_table) {
			
			$form = new HTML_QuickForm('','post',$_SERVER['PHP_SELF']);
			$form->addElement('hidden','table_name',$join_table);
			$return_to_here = urlencode("?action=edit&table_name=$current_table&id=$current_id&#rel-$joined_table");
			$form->addElement('hidden','return_to',$return_to_here);
			$form->addElement('hidden',$current_table.'_id',$current_id);
			$form->addElement($this->getHtmlSelect($joined_table.'_id', 'Add relationship: ', $joined_table));
			$form->addElement('submit', 'save', 'Add relationship');
			$form->addElement('static','','Or:',"<a href='?action=add&table_name=$joined_table&return_to=$return_to_here'>Add ".titlecase($joined_table)."</a>");
			
			$sql = "SELECT ".$joined_table.".*, $join_table.id FROM $current_table, $joined_table, $join_table
				WHERE $current_table.id=$current_id
				AND ".$current_table.".id=".$join_table.".".$current_table."_id
				AND ".$join_table.".".$joined_table."_id=".$joined_table.".id";
			$table = $this->getHtmlTable($join_table, $sql);
			
			$out .= "<h2><a name='rel-$joined_table'>Related ".titlecase($joined_table)."</h2>".$table->toHtml().$form->toHtml();
			
		}
		return $out;
	}
	
	function getHtmlSelect($name, $label, $tbl) {
		require_once 'HTML/QuickForm/select.php';
		$select = new HTML_QuickForm_select($name, $label);
		$fields = $this->getFieldNames($tbl);
		$this->mdb2->setFetchMode(MDB2_FETCHMODE_ORDERED);
		$rows = $this->fetchAll("SELECT * FROM $tbl ORDER BY ".$fields[1]."");
		$this->mdb2->setFetchMode(MDB2_FETCHMODE_ASSOC);
		foreach ($rows as $row) {
			$options[$row[0]] = $row[0].' | '.$row[1];
			if (isset($row[2])) $options[$row[0]] .= ' | '.$row[2];
		}
		$select->load($options);
		return $select;
	}
		
	function tableExists($tbl) {
		return in_array($tbl, $this->getTables());
	}
	
	function getTables() {
		$tables = array();
		foreach ($this->fetchAll("SHOW TABLES") as $tbl) {
			$tbl = array_values($tbl);
			$tables[] = $tbl[0];
		}
		return $tables;
	}
	
	function getJoinTables($joined_to) {
		$join_syntax = '_to_';
		$out = array();
		foreach ($this->getTables() as $tbl) {
			if (stristr($tbl, $joined_to.$join_syntax)) {
				$join_table = $tbl;
				$joined_table = substr($tbl, strlen($joined_to.$join_syntax));
				$out[$join_table] = $joined_table;
			} elseif (stristr($tbl, $join_syntax.$joined_to)) {
				$join_table = $tbl;
				$joined_table = substr($tbl, 0, -(strlen($join_syntax.$joined_to)));
				$out[$join_table] = $joined_table;
			}
		}
		return $out;
	}
	
	function getVar($table, $id, $field) {
		$r = $this->query("SELECT $field AS x FROM $table WHERE id=".$this->esc($id));
		$row = $r->fetchRow();
		return $row['x'];
	}
	
	function numRows($table) { 
		$r = $this->fetchAll("SELECT COUNT(*) AS num FROM $table");
		return $r[0]['num'];
	}
	
	function fetchAll($sql) {
		$r = $this->query($sql);
		$results = $r->fetchAll();
		return $results;
	}
	
	function esc($str) {
		return $this->mdb2->escape($str);
	}

	function save($tbl, $data) {
		global $page;
		$fields = $this->getFieldNames($tbl);
		if ( isset($data['id']) && is_numeric($data['id']) && $data['id']>0 ) {
			$sql = "UPDATE `$tbl` SET ";
		} else {
			$sql = "INSERT INTO `$tbl` SET ";
		}
		foreach ($fields as $field) {
			if (isset($data[$field]) && $field!='id') {
				if ($this->getFieldType($tbl,$field)=='int(1)') {
					$field_data = $this->stringToOneOrZero($data[$field]);
				} else {
					$field_data = $data[$field];
				}
				$sql .= "`$field` = '".$this->esc($field_data)."', ";
			}
		}
		$sql = substr($sql, 0, -2); // Remove last comma-space.
		if ( isset($data['id']) && is_numeric($data['id']) && $data['id']>0 ) {
			$sql .= " WHERE id='".$this->esc($data['id'])."'";
		}
		if ($result = $this->query($sql)) {
			if (isset($_REQUEST['return_to'])) {
				header("Location:".urldecode($_REQUEST['return_to']));
			} else {
				$page->addBodyContent("<div class='span-24 last success'>Record saved.</div>");
				return $result;
			}
		}
	}
	
	function stringToOneOrZero($str) {
		if ( $str!='' && $str!=null && !empty($str) && isset($str)
		     && strcasecmp($str,'false')!=0 && strcasecmp($str,'off')!=0
		   ) {
			return 1;
		} else {
			return 0;
		}
	}
	
	function getFieldNames($tbl) {
		$sql = "DESCRIBE ".$this->esc($tbl);
		$res = $this->mdb2->query($sql);
		if (PEAR::isError($res)) {
			die($res->message."<br>Table: $tbl");
		} else {
			$desc = $res->fetchAll();
			$fields = array();
			foreach ($desc as $col) {
				$fields[] = $col['field'];
			}
			return $fields;
		}
	}
	
	function getFieldType($tbl, $field) {
		$sql = "DESCRIBE ".$this->esc($tbl);
		$res = $this->query($sql);
		$desc = $res->fetchAll();
		foreach ($desc as $col) {
			if ($col['field']==$field) {
				return $col['type'];
			}
		}
		return false;
	}
	
	function getReferencedTableName($tbl, $field) {
		$info = $this->fetchAll("SHOW CREATE TABLE ".$this->esc($tbl));
		preg_match("|FOREIGN KEY \(`$field`\) REFERENCES `(.*?)`|", $info[0]['create table'], $matches);
		if (isset($matches[1])) {
			return $matches[1];
		} else {
			return false;
		}
	}
	
	function getDefaultFieldValues($tbl) {
		$out = array();
		$sql = "DESCRIBE ".$this->esc($tbl);
		$res = $this->query($sql);
		foreach ($res->fetchAll() as $field_desc) {
			if ( $field_desc['type']=='datetime' && empty($field_desc['default']) ) {
				$field_desc['default'] = date('Y-m-d H:i:s');
			}
			$out[$field_desc['field']] = $field_desc['default'];
		}
		return $out;
	}

}
?>
