<?php
// $Id;
/**
 * IMPORTANT: See README.txt file on configuration changes to the settings.php file to use this template
 * 
 * This is a custom maintenance page 
 * It's basically the same template as the page.tpl.php except without the bottom boxes and extra regions
 * The primary and secondary navigation were also removed
 * Anything is better than Drupal defaulting to the Garland maintenance page
 * The original maintenance file can be found at modules/system/maintenance-page.tpl
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language; ?>" lang="<?php print $language->language; ?>" dir="<?php print $language->dir; ?>">

<head>
	<?php print $head; ?>
  <title><?php print $head_title; ?></title>
  <?php print $styles; ?>
  <?php print $scripts; ?>
</head>

<body class="<?php print $body_classes; ?>">
<div id="outer-wrapper">
  <div id="header">  
    <?php if ($site_name): ?>
      <h1><a href="<?php print $front_page; ?>" title="<?php print $site_name; ?>">
	    <?php print $site_name; ?>
       </a></h1>
    <?php endif; ?>
   
	<?php if ($logo): ?>
      <a href="<?php print $front_page; ?>" title="<?php print $site_name; ?>">
        <img src="<?php print $logo; ?>" alt="<?php print $site_name; ?>" title="<?php print $site_name; ?>" />
      </a>
    <?php endif; ?> 
  </div> <!--header-->
  <div>&nbsp;</div>
  <div id="main">
    <?php if ($help || $messages): ?>
      <div id="help-messages"> 
	    <?php print $messages; ?>
        <?php print $help; ?>
      </div>
    <?php endif; ?> 
    
	<?php if ($tabs): ?>
      <div id="edit-tabs">
        <?php print $tabs; ?>
      </div>
    <?php endif; ?> 
    
    <div id="main-inner">
      <?php if ($title): ?>
        <h2><?php print $title; ?></h2>
      <?php endif; ?>
    <?php print $content; ?>
    </div><!--Main Inner-->
  </div><!--Main-->	
  
  <?php if ($footer_message): ?>
    <div id="footer-message"> <?php print $footer_message; ?></div>
  <?php endif; ?>
  
  <?php if ($footer):?>
    <div id="footer"><?php print $footer; ?></div>
  <?php endif; ?>
  
  <?php print $closure; ?>
</div> <!-- Outer wrapper -->
</body>
</html>