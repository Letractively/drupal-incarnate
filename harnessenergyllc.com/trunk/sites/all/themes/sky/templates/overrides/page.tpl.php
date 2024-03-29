<?php // $Id$

/**
 * @file
 * Main template file
 *
 * @see template_preprocess_page(), preprocess/preprocess-page.inc
 * http://api.drupal.org/api/function/template_preprocess_page/6
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml" lang="<?php print $language->language; ?>" xml:lang="<?php print $language->language; ?>">
    <head>
      <?php print $head; ?>
      <title><?php print $head_title; ?></title>
      <?php print $styles; ?>
      <?php print $ie_styles; ?>
      <?php print $scripts; ?>
    </head>
  <body<?php print $attributes; ?>>
  <?php if (!empty($admin)) print $admin; // support for: http://drupal.org/project/admin ?>
  <div id="wrapper">
    <div<?php print $header_attributes; ?>>
      <div id="header-inner">
        <?php if ($logo): ?>
        <a href="<?php print $base_path; ?>" title="<?php print $site_name; ?>" id="logo"><img src="<?php print $logo; ?>" alt="<?php if ($site_name): print $site_name;  endif; ?>" /></a>
        <?php endif; ?>
        <?php if ($site_name): ?>
        <span id="site-name"> <a href="<?php print $base_path; ?>" title="<?php print $site_name; ?>"><?php print $site_name; ?></a> </span>
        <?php endif; ?>
        <?php if ($site_slogan): ?>
          <span id="site-slogan"><?php print $site_slogan; ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($primary_links): ?>
      <div id="navigation"><?php print $primary_links; ?></div>
    <?php endif; ?>
    <div id="container" class="layout-region">
      <?php if ($left): ?>
        <div id="sidebar-left" class="sidebar">
          <div class="inner">
            <?php print $left; ?>
          </div>
        </div>
      <!-- END HEADER -->
      <?php endif; ?>
      <div id="main">
        <div class="main-inner">
          <?php if ($breadcrumb): ?>
            <div class="breadcrumb clearfix"><?php print $breadcrumb; ?></div>
          <?php endif; ?>
          <?php if ($show_messages && $messages != ""): ?>
          <?php print $messages; ?>
          <?php endif; ?>
          <?php if ($is_front && $mission): ?>
            <div class="mission"><?php print $mission; ?></div>
          <?php endif; ?>
          <?php if ($contenttop): ?>
            <div id="content-top"><?php print $contenttop; ?></div>
            <!-- END CONTENT TOP -->
          <?php endif; ?>
          <?php if ($title): ?>
            <h1 class="title"><?php print $title; ?></h1>
          <?php endif; ?>
          <?php if ($help): ?>
            <div class="help"><?php print $help; ?></div>
          <?php endif; ?>
          <?php print $tabs; ?>
          <div id="content" class="clearfix">
            <?php print $content; ?>
          </div>
          <!-- END CONTENT -->
          <?php print $feed_icons; ?>
          <?php if ($contentbottom): ?>
            <div id="content-bottom"><?php print $contentbottom; ?></div>
          <?php endif; ?>
        </div>
        <!-- END MAIN INNER -->
      </div>
      <!-- END MAIN -->
      <?php if ($right): ?>
        <div id="sidebar-right" class="sidebar">
          <div class="inner">
          <?php print $right; ?>
          </div>
        </div>
      <!-- END SIDEBAR RIGHT -->
      <?php endif; ?>
    </div>
    <!-- END CONTAINER -->
    <div class="push">&nbsp;</div>
  </div>
  <!-- END WRAPPER -->
  <div id="footer" class="layout-region">
    <div id="footer-inner">
      <?php print $contentfooter; ?>
      <?php print $footer_message; ?>
    </div>
  </div>
  <?php print $closure; ?>
  </body>
</html>