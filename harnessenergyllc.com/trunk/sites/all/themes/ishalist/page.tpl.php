<?php // $Id: page.tpl.php,v 1.5 2009/07/22 05:55:33 ishmaelsanchez Exp $ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language; ?>" lang="<?php print $language->language; ?>" dir="<?php print $language->dir; ?>">

<head>
	<?php print $head; ?>
  <title><?php print $head_title; ?></title>
  <?php print $styles; ?>
  <?php print $scripts; ?>
  <!--[if IE]>
    <link rel="stylesheet" href="<?php print $base_path . $directory; ?>/ie.css" type="text/css">
  <![endif]-->
</head>

<body class="<?php print $body_classes; ?>">
<div id="outer-wrapper">
<div id="skip"><a href="#content">Skip to Main Content</a></div>  
  <div id="header">  
    <?php if ($site_name): ?>
      <h1><a href="<?php print $front_page; ?>" title="<?php print $site_name; ?>">
		  <?php print $site_name; ?>
       </a></h1>
    <?php endif; ?>
    
    <?php if (isset($secondary_links)): ?> 
      <?php print theme('links', $secondary_links, array('class' => 'secondary-links')) ?>
    <?php endif; ?>
    
	<?php if ($logo): ?>
      <a href="<?php print $front_page; ?>" title="<?php print $site_name; ?>">
      <img src="<?php print $logo; ?>" alt="<?php print $site_name; ?>" title="<?php print $site_name; ?>" />
      </a>
    <?php endif; ?> 
  </div> <!--header-->
  <div id="main">
    <?php if ($help || $messages): ?>
	    <?php print $help; ?>
		  <?php print $messages; ?>
    <?php endif; ?> 
    
	 <?php if (isset($primary_links)) : ?>
      <div id="main-nav">
       <?php print theme('links', $primary_links, array('class' => 'tabs primary-links')) ?>
      </div>
    <?php endif; ?> 
    
    <div id="main-inner">
			<?php if ($tabs): ?>
				<div id="edit-tabs">
					<?php print $tabs; ?>
				</div>
			<?php endif; ?>
    
      <?php if (!empty($content_top)): ?>
        <div id="content-top">
          <?php print $content_top; ?>
        </div> <!-- /content-top-->
      <?php endif; ?>
    
      <a name="content"></a>
      <?php if ($title): ?>
        <h2><?php print $title; ?></h2>
      <?php endif; ?>
      
      <?php print $content; ?>
    
      <?php if (!empty($content_bottom)): ?>
        <div id="content-bottom">
          <?php print $content_bottom; ?>
        </div> <!-- /content-bottom-->
      <?php endif; ?>
    
	    <?php if ($breadcrumb): ?>
        <?php print $breadcrumb; ?>
      <?php endif; ?>
    </div><!--Main Inner-->
  </div><!--Main-->
  
  <?php if ($primary_box): ?>
    <div id="primary-box">
	  <?php print $primary_box; ?>	
	</div>
  <?php endif; ?>
  
  <?php if ($secondary_box): ?>
    <div id="secondary-box">
	  <?php print $secondary_box; ?>	
	</div>
  <?php endif; ?>
  
  <?php if ($tertiary_box): ?>
    <div id="tertiary-box" >
	  <?php print $tertiary_box; ?>	
	</div>
  <?php endif; ?>		

  <?php if ($footer_message): ?>
    <div id="footer-message"> <?php print $footer_message; ?></div>
  <?php endif; ?>	
   
  <?php if ($footer):?>
    <div id="footer"><?php print $footer; ?></div>
  <?php endif; ?>
  
</div> <!-- Outer wrapper -->
<?php print $closure; ?>
</body>
</html>
