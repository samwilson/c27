<?php
require_once 'config.php';
require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/link.php';
require_once 'HTML/QuickForm/select.php';
require_once 'HTML/QuickForm/submit.php';
require_once 'HTML/QuickForm/textarea.php';

$defaultMailbox = "INBOX.Archives";
$message_id = 1;

/*******************************************************************************
 * basic setup of general objects
 ******************************************************************************/
$page->setTitle("G.P.O. Inbox");

require_once 'Net/IMAP.php';
$imap = new Net_IMAP($mail_server['server'], $mail_server['port']);
if (PEAR::isError($imap->login($mail_server['username'], $mail_server['password'], true, false))) {
	die('Could not login to mail server.');
}
$imap->selectMailbox($mail_server['inbox']);


/*******************************************************************************
 * Archive selected email
 * (Saving to the database is handled in lib/Database.php)
 ******************************************************************************/

if ( (isset($_POST['save']) && $_POST['save']=='Archive + Delete') || isset($_POST['delete']) ) {
	$imap->deleteMsg($message_id);
	$imap->expunge();
	header("Location:".$_SERVER['PHP_SELF']);
	exit();
}

else if (isset($_POST['save']) && $_POST['save']=='Archive Only') {
	header("Location:".$_SERVER['PHP_SELF']);
	exit();
}


/*******************************************************************************
 * Get list of people
 ******************************************************************************/
$ppl = $db->fetchAll("SELECT id, name FROM people ORDER BY name ASC");
$people[0] = 'n. i. d.';
foreach ($ppl as $person) {
	$people[$person['id']] = $person['name'];
}
 
/*******************************************************************************
 * Display currently selected message
 ******************************************************************************/
if ($imap->numMsg()>0) {
	
	// Get message headers.
	$headers = $imap->getSummary(1);
	if (PEAR::isError($headers)) {
		die('Failed to parse headers of message: '.$headers->message);
	}
	$headers = $headers[0];
	$headers['FROM'] = (isset($headers['FROM'][0]['RFC822_EMAIL'])) ? $headers['FROM'][0]['RFC822_EMAIL'] : "";
	$headers['TO'] = (isset($headers['TO'][0]['RFC822_EMAIL'])) ? $headers['TO'][0]['RFC822_EMAIL'] : "";
	
	// Set defaults.
	$from_id = 0;
	$to_id = 9;
	$subject = $headers['SUBJECT'];
	if (empty($subject) || $subject=='NIL') {
		$subject = '[No Subject]';
	} elseif (mb_check_encoding($subject)) {
		$subject = mb_decode_mimeheader($subject);
	}
	$editform_defaults = array(
		'date_and_time' => date('Y-m-d H:i:s',strtotime($headers['DATE'])),
		'subject' => $subject,
		'to_id' => $to_id,
		'from_id' => $from_id
	);
	//if (!isset($headers->to)) $headers->to = "";
	//if (!isset($headers->from)) $headers->from = "";
		
	// Determine correspondents
	$email_address_pattern = "/[a-zA-Z0-9\.\-_]*@[a-zA-Z0-9\.\-_]*/i";
	preg_match($email_address_pattern, $headers['FROM'], $from_email_address);
	preg_match($email_address_pattern, $headers['TO'], $to_email_address);
	if (isset($from_email_address[0])) {
		$from_person = $db->fetchAll("SELECT * FROM people WHERE email_address LIKE '%".$from_email_address[0]."%'");
		if (isset($from_person[0])) {
			$from_id = $from_person[0]['id'];
			$editform_defaults['from_id'] = $from_id;
		}
	}
	if (isset($to_email_address[0])) {
		$to_person = $db->fetchAll("SELECT * FROM people WHERE email_address LIKE '%".$to_email_address[0]."%'");
		if (isset($to_person[0])) {
			$to_id = $to_person[0]['id'];
			$editform_defaults['to_id'] = $to_id;
		}
	}

	
	// Get message body.
	function getMsgBody($structure) {
		global $imap;
		$message_body = "";
		if (isset($structure->subParts)) {
			foreach ($structure->subParts as $pid=>$part) {
				if ($part->type=='TEXT' && $part->subType=='PLAIN') {
					if ($part->encoding=='QUOTED-PRINTABLE') {
						$message_body .= quoted_printable_decode($imap->getBodyPart(1,$part->partID));
					} elseif ($part->encoding=='BASE64') {
						$message_body .= base64_decode($imap->getBodyPart(1,$part->partID));
					} else {
						$message_body .= $imap->getBodyPart(1,$part->partID);
					}
				} elseif (isset($part->subParts)) {
					$message_body .= getMsgBody($part);
				}
			}
		} else { // If no parts, then must be plain.
			if ($structure->type=='TEXT' && $structure->subType=='HTML') {
				$message_body = ($structure->encoding=='BASE64') ? base64_decode($imap->getBody(1)) : $imap->getBody(1);
				$message_body = quoted_printable_decode($message_body);
				$message_body = strip_tags($message_body);
				$message_body = str_replace("&nbsp;"," ",$message_body);
				$message_body = html_entity_decode($message_body, ENT_QUOTES, $structure->parameters['CHARSET']);
			} else {
				if ($structure->encoding=='BASE64') {
					$message_body = base64_decode($imap->getBody(1));
				} else {
					$message_body = utf8_encode(quoted_printable_decode($imap->getBody(1)));
				}
			}
		}
		return $message_body;
	}
	$editform_defaults['message_body'] = getMsgBody($imap->getStructure(1));

	 // Build form
	$bodytextarea = new HTML_QuickForm_textarea('message_body',null);
	$bodytextarea->setAttribute('class','span-20');
	$bodytextarea->setRows(24);
	$bodytextarea->setCols(80);
	$editform = new HTML_QuickForm('editform');
	$editform->setDefaults($editform_defaults);
	$editform->addElement('header',null,$imap->numMsg().' Message(s) Remaining');
	$editform->addElement('hidden','table_name','emails');
	$editform->addGroup(array(
		new HTML_QuickForm_submit('save','Archive + Delete'),
		new HTML_QuickForm_submit('save','Archive Only'),
		new HTML_QuickForm_submit('delete','Delete Only')
	),null,'Actions: ');
	$editform->addElement('text','date_and_time','Date: ');
	$editform->addGroup(array(
		new HTML_QuickForm_select('from_id','From: ',$people),
		new HTML_QuickForm_static(null,null,"<a href='people.php?person_text=".$headers['FROM']."'><code>".htmlentities($headers['FROM'])."</code></a> ($from_id)")
	), null, 'From: ');
	$editform->addGroup(array(
		new HTML_QuickForm_select('to_id','To: ',$people),
		new HTML_QuickForm_static(null,null,"<a href='people.php?person_text=".$headers['TO']."'><code>".htmlentities($headers['TO'])."</code></a> ($to_id)")
	), null, 'To: ');
	$editform->addElement('text','subject','Subject: ',array('size'=>80, 'class'=>'span-20'));
	$editform->addElement($bodytextarea);
	$page->addBodyContent("<div class='span-24 last'>".$editform->tohtml()."</div>");
}

else {
	$page->addBodyContent("<p class='centre'>No messages to accession.</p>");
}



/*******************************************************************************
 * Clean up and whatnot.
 ******************************************************************************/
$imap->disconnect();
$page->addBodyContent('</div>');
$page->display();

?>