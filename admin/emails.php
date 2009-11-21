<?php
require_once 'config.php';
require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/select.php';
require_once 'HTML/QuickForm/submit.php';
require_once 'HTML/QuickForm/textarea.php';

$page->setTitle("G.P.O. Archive");
$css->parseString("div.email {background-color:#eee; margin:1em; padding:1em}");
if (!empty($_REQUEST['with'])) $with = $_REQUEST['with']; else $with = 0;
if (!empty($_REQUEST['year'])) $year = $_REQUEST['year']; else $year = 0;

/*******************************************************************************
 * Send reply?
 ******************************************************************************/
if (isset($_POST['save']) && $_POST['save']=='Send') {
	$body = $_POST['message_body'];
	if (!empty($_POST['last_body'])) {
		$body .= "\n\n
------------------------------------------------------------------------------
Date: ".$_POST['last_date']." (Freo time)
From: ".$_POST['to']."
  To: Sam Wilson <sam@archives.org.au>
------------------------------------------------------------------------------

".wordwrap($_POST['last_body'],78)."

------------------------------------------------------------------------------
http://sam.archives.org.au/
";
	}
	$headers = "From: Sam Wilson <sam@archives.org.au>\r\nX-Mailer: Sam's Super Smelling Salts.";
	if (!mail($_POST['to'], $_POST['subject'], $body, $headers)) {
		die("Something bad happened with sending the email!!  I don't know what to do.  Sorry.");
	}
	header("Location:".$_SERVER['PHP_SELF']."?with=".$_POST['to_id']."&year=$year#reply-form");
}

/*******************************************************************************
 * Get years.
 ******************************************************************************/
$res = $db->fetchAll("SELECT YEAR(date_and_time) AS year FROM emails GROUP BY year");
foreach ($res as $y) {
	$years[$y['year']] = $y['year'];
}


 /*******************************************************************************
 * Get people
 ******************************************************************************/
$ppl = $db->fetchAll("SELECT id, name FROM people ORDER BY name ASC");
foreach ($ppl as $person) {
	$people[$person['id']] = $person['name'];
}


/*******************************************************************************
 * Navigation form stuff.
 ******************************************************************************/

$page->addBodyContent("<p class='centre'>LaTeX: | ");
foreach ($years as $y) {
	$page->addBodyContent(" <a href='emails_latex.php?year=$y' title='$y.tex'>$y</a> | ");
}
$page->addBodyContent("</p>");

$page->addBodyContent("<p class='centre'>Chronological: | ");
foreach ($years as $y) {
	$page->addBodyContent(" <a href='emails.php?year=$y' title='$y.tex'>$y</a> | ");
}
$page->addBodyContent("</p>");

$form = new HTML_QuickForm('','post',$_SERVER['PHP_SELF'].'#reply-form');
$form->setDefaults(array('year'=>$year, 'with'=>$with));
$form->addElement('header',null,$db->numRows('emails').' emails in archive');
$form->addGroup(array(
	new HTML_QuickForm_select('with','With',$people),
	new HTML_QuickForm_select('year','Year',$years),
	new HTML_QuickForm_submit('change_with','View')
), null, null);
$page->addBodyContent($form);


/*******************************************************************************
 * list emails
 ******************************************************************************/

if ($year || $with) {
	$css->parseString("
		.email {text-align:left; border:2px solid #CCC}
		.email.from-me {border:2px solid #060}
		.from {color:#CCC;font-variant:small-caps}
		.from.from-me {color:#060}
	");
	
	$sql = "SELECT * FROM emails WHERE YEAR(date_and_time)=".$db->esc($year)." ";
	if ($with) $sql .= "AND (to_id = ".$db->esc($with)." OR from_id = ".$db->esc($with).") ";
	$sql .= "ORDER BY date_and_time ASC";
	
	$emails = $db->fetchAll($sql);
	$last_subject = '';
	$last_body = '';
	$last_date = '';
	$last_from_id = '';
	$page->addBodyContent("<p class='centre'>Showing ".count($emails)." emails.</p>");
	foreach ($emails as $count=>$email) {
		$email_class = ($email['from_id']==9) ? 'from-me' : '';
		$page->addBodyContent("<div class='email $email_class'><p>");
		if ($count==count($emails)-1) {
			$page->addBodyContent("<a name='reply-form'></a>");
		}
		$page->addBodyContent("
			<span class='from $email_class'>".$people[$email['from_id']]."</span>
		");
		if (!$with) {
			$page->addBodyContent(" (to ".$people[$email['to_id']].") ");
		}
		$page->addBodyContent("
			".date('l, F jS, g:iA',strtotime($email['date_and_time']))."
			&nbsp;&nbsp;
			<strong>".$email['subject']."</strong> &nbsp;&nbsp;
			<small>
			  <a href='?table_name=emails&edit&id=".$email['id']."'>[e]</a>
			  <a href='?table_name=emails&delete&id=".$email['id']."'>[d]</a>
			</small>
			</p><pre>".trim(wordwrap(htmlentities($email['message_body']), 78))."</pre></div>");
		$last_subject = $email['subject'];
		$last_body = $email['message_body'];
		$last_date = $email['date_and_time'];
		$last_from_id = $email['from_id'];
	}
	$page->addBodyContent("<p class='centre'>Showing ".count($emails)." emails.</p>");
}


/*******************************************************************************
 * reply form
 ******************************************************************************/
	
if ($with) {
	$to = $db->getVar('people',$with,"CONCAT(name,' <',email_address,'>')");
	$replyform = new HTML_QuickForm('','post',$_SERVER['PHP_SELF']);
	$replyform->addElement('hidden','table_name','emails');
	$replyform->addElement('hidden','from_id','9');
	$replyform->addElement('hidden','to_id',$with);
	$replyform->addElement('hidden','with',$with);
	$replyform->addElement('hidden','year',$year);
	$replyform->addElement('hidden','correspondent',$with);
	if ($last_from_id!=9) {
		$replyform->addElement('hidden','last_body',htmlentities($last_body));
	}
	$replyform->addElement('hidden','last_date',htmlentities($last_date));
	$replyform->addElement('hidden','date_and_time',date('Y-m-d H:i:s'));
	$replyform->addElement('header',null,'Reply');
	$replyform->addElement('submit','save','Send');
	$replyform->addElement('text','to','To: ',array('value'=>$to, 'size'=>80));
	if (stristr($last_subject,'re')===FALSE) {
		$new_subject = 'Re: '.$last_subject;
	} else {
		$new_subject = $last_subject;
	}
	$replyform->addElement('text','subject','Subject: ',array('value'=>$new_subject, 'size'=>80));
	$bodyelement = new HTML_QuickForm_textarea('message_body',null);
	$bodyelement->setRows(24);
	$bodyelement->setCols(80);
	$replyform->addElement($bodyelement);
	$page->addBodyContent($replyform);
}
 

/*******************************************************************************
 * who still to reply to
 ******************************************************************************/

$page->addBodyContent("<ul class='columnar'>");
foreach ($people as $pid=>$name) {
	if ($pid!=9) { // 9 is me.
		$sql = ("SELECT from_id, to_id, YEAR(date_and_time) AS year FROM emails
		WHERE to_id = ".$db->esc($pid)." OR from_id = ".$db->esc($pid)."
		ORDER BY date_and_time DESC LIMIT 1");
		$unanswered = $db->fetchAll($sql);
		if (isset($unanswered[0])) {
			$from_id = $unanswered[0]['from_id'];
			$to_id = $unanswered[0]['to_id'];
			$year = $unanswered[0]['year'];
			//if (!empty($from_id)) {
				if ($from_id!=9) { // 9 is me.
					$page->addBodyContent("<li><strong><a style='color:red' href='?with={$from_id}&year={$year}#reply-form'>$name</a></strong></li>");
				} else {
					$page->addBodyContent("<li><a href='?with={$to_id}&year={$year}#reply-form'>$name</a></li>");
					//$page->addBodyContent("<li>$name</li>");
				}
			//}
		} else {
			$page->addBodyContent("<li><a href='?with=$pid&year=".date('Y')."#reply-form'>$name</a></li>");
		}
		$from_id = null;
		$to_id = null;
		$year = null;
	}
}
$page->addBodyContent("</ul><hr />");
 
$page->display();

?>