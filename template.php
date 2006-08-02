<!-- $Id$ -->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.0//EN"
  "http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  
  <meta name="Author"      content="<?php echo $Page['author_name']; ?>" />
  <meta name="Description" content="<?php echo $Page['summary']; ?>" />
  <meta name="DC.Created"  content="<?php echo $Page['date_modified']; ?>" />
  <meta name="DC.Modified" content="<?php echo $Page['date_published']; ?>" />
  
  <link href="style.css" rel="stylesheet" type="text/css" />
  <style type="text/css">
    <?php echo $Page['style']; ?>
  </style>
  
  <title><?php echo $Page['title']." &laquo; ".$Config['sitename']; ?></title>
	
</head>
<body>
  <div id="header">
    <h1><a href="index.php"><?php echo $Config['sitename']; ?></a></h1>
    <?php if ($Page['id']!='1') echo "<p>".$Page['breadcrumb']."</p>"; ?>
  </div>

  <?php if (isset($User)): ?>
    <p id='logout_link'>
      <a href='?id=4'>Logout <?php echo $User['username']; ?></a>
    </p>
  <?php endif; ?>

  <div id="body">
  
    <?php if ($Page['id']!='1') echo "<h2>".$Page['title']."</h2>"; ?>
    
    <?php if ($Page['error_message'])
    	print("<div class='error_message'><p><strong>Error:</strong></p>".
    		  $Page['error_message']."</div>"); ?>
    <?php if ($Page['message'])
    	echo "<div class='message'>".$Page['message']."</div>"; ?>
    	
    <?php echo $Page['body']; ?>
  </div>
  
  <?php if($Page['TOC']): ?>
  <div id='toc'>
    <?php echo $Page['TOC']; ?>
    <div class='clear'>&nbsp;</div>
  </div>
  <?php endif; ?>
  
  <div id="footer">
    <ol>
    
      <?php if ($User['logged_in']): ?>
      <li><a href="?id=9">[My Account]</a></li>
      <?php endif; ?>
            
      <?php if ($User['level'] == 10): ?>
      <li><a href="?id=5#view">[New page]</a></li>
      <li><a href="?id=5&edit_id=<?php echo $Page['id']; ?>#view">[Edit this page]</a></li>
      <?php endif; ?>
      
      <li>Powered by <a href='http://sourceforge.net/projects/channel27'>Channel 27</a></li>
      <li>Valid HTML &amp; CSS</li>
      
    </ol>
  </div>

</body>
</html>