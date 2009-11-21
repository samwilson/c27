<?php

require_once 'common.php';

$page->setTitle('Upgrade');
$page->setBody('<div class="container centre">
<div class="span-24 last"><h1>Upgrade</h1></div>');


$result = $mdb2->exec("
CREATE TABLE IF NOT EXISTS auth_levels (
  	id int(3) NOT NULL PRIMARY KEY,
  	name varchar(50) NOT NULL
) ENGINE=InnoDB;
");
if (PEAR::isError($result)) {
	$page->addBodyContent("<p class='error'>".$result->getMessage()."</p>");
}

$result = $mdb2->exec("
CREATE TABLE IF NOT EXISTS people (
  id int(11) NOT NULL auto_increment PRIMARY KEY,
  name varchar(150) NOT NULL,
  first_name varchar(100) NOT NULL,
  surname varchar(100) NOT NULL,
  email_address varchar(100) NOT NULL,
  notes text NOT NULL,
  auth_level int(2) NOT NULL default '5',
  CONSTRAINT people_auth_level FOREIGN KEY (auth_level) REFERENCES auth_levels (id)
) ENGINE=InnoDB;
");
if (PEAR::isError($result)) {
	$page->addBodyContent("<p class='error'>".$result->getMessage()."</p>");
}

$result = $mdb2->exec("
CREATE TABLE IF NOT EXISTS emails (
  id int(10) NOT NULL auto_increment PRIMARY KEY,
  to_id int(11) NOT NULL,
  from_id int(11) NOT NULL,
  date_and_time datetime default NULL,
  subject varchar(200) NOT NULL,
  message_body text,
  CONSTRAINT emails_to_id FOREIGN KEY (to_id) REFERENCES people (id),
  CONSTRAINT emails_from_id FOREIGN KEY (from_id) REFERENCES people (id)
) ENGINE=InnoDB;
");
if (PEAR::isError($result)) {
	$page->addBodyContent("<p class='error'>".$result->getMessage()."</p>");
}


$result = $mdb2->exec("
CREATE TABLE IF NOT EXISTS images (
  id int(15) NOT NULL auto_increment PRIMARY KEY,
  date_and_time datetime NOT NULL,
  caption text NOT NULL,
  auth_level int(2) NOT NULL,
  CONSTRAINT images_auth_level FOREIGN KEY (auth_level) REFERENCES auth_levels (id)
) ENGINE=InnoDB;
");
if (PEAR::isError($result)) {
	$page->addBodyContent("<p class='error'>".$result->getMessage()."</p>");
}


$result = $mdb2->exec("
CREATE TABLE IF NOT EXISTS journal_entries (
  id int(11) NOT NULL auto_increment PRIMARY KEY,
  date_and_time datetime NOT NULL,
  entry_text text NOT NULL,
  title varchar(200) default NULL,
  auth_level int(3) NOT NULL default '10',
  CONSTRAINT journal_entries_auth_level FOREIGN KEY (auth_level) REFERENCES auth_levels (id)
) ENGINE=InnoDB;
");
if (PEAR::isError($result)) {
	$page->addBodyContent("<p class='error'>".$result->getMessage()."</p>");
}


$result = $mdb2->exec("
CREATE TABLE IF NOT EXISTS tags (
  id int(15) NOT NULL auto_increment PRIMARY KEY,
  title varchar(200) NOT NULL UNIQUE
) ENGINE=InnoDB;
");
if (PEAR::isError($result)) {
	$page->addBodyContent("<p class='error'>".$result->getMessage()."</p>");
}


$result = $mdb2->exec("
CREATE TABLE IF NOT EXISTS tags_to_images (
  tag int(15) NOT NULL,
  image int(15) NOT NULL,
  PRIMARY KEY  (tag,image),
  CONSTRAINT tags_to_images_tag FOREIGN KEY (tag) REFERENCES tags (id),
  CONSTRAINT tags_to_images_image FOREIGN KEY (image) REFERENCES images (id)
) ENGINE=InnoDB;
");
if (PEAR::isError($result)) {
	$page->addBodyContent("<p class='error'>".$result->getMessage()."</p>");
}


$result = $mdb2->exec("
CREATE TABLE IF NOT EXISTS tags_to_journal_entries (
  tag int(15) NOT NULL,
  journal_entry int(15) NOT NULL,
  PRIMARY KEY  (tag,journal_entry),
  CONSTRAINT tags_to_journal_entries_tag FOREIGN KEY (tag) REFERENCES tags (id),
  CONSTRAINT tags_to_journal_entries_journal_entry 
    FOREIGN KEY (journal_entry) REFERENCES journal_entries (id)
) ENGINE=InnoDB;
");
if (PEAR::isError($result)) {
	$page->addBodyContent("<p class='error'>".$result->getMessage()."</p>");
}


$result = $mdb2->exec("
CREATE TABLE IF NOT EXISTS users (
  id int(11) NOT NULL auto_increment PRIMARY KEY,
  username varchar(60) NOT NULL,
  password varchar(60) NOT NULL,
  person_id int(11) NOT NULL,
  auth_level int(3) NOT NULL default '0',
  reset_hash varchar(100) NOT NULL
) ENGINE=InnoDB;
");
if (PEAR::isError($result)) {
	$page->addBodyContent("<p class='error'>".$result->getMessage()."</p>");
}


$page->addBodyContent('<p>
<a class="positive button" href="index.php">All done!  Click here to continue.</a>
</p>
</div><!-- end div.container -->');
$page->display();
