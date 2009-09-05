; // $Id: README.txt,v 1.4 2009/07/22 05:55:33 ishmaelsanchez Exp $

# Information about the theme

# Template files: block.tpl.php, box.tpl.php, comment.tpl.php, comment-wrapper.tpl.php maintenance-page.tpl.php, node.tpl.php, page.tpl.php, template.php
# CSS files: drupal.css, html.css, ie.css, ishalist.css, print.css
# JS file: scripts.js - Just one blank JS file for you to customize
# Misc: favicon.ico, ishalist.info, logo.png, preview.png, screenshot.png
# The Images folder houses custom graphics specific to this theme. You can put additional theme related images here.

# The ishalist CSS file contains the majority of the CSS that influences the layout and color scheme
# The screenshot.png thumbnail will appear on the /admin/build/theme page
# The preview.png file serves no purpose within Drupal, but just gives you a better idea of how the theme will look

# Tips:
# Make use of the body classes, you can get very granular with your CSS using it
# If you stack the boxes, it probably a good idea to make the content all the same height
# You should probably have content in all boxes or none.
# Read the directions about using the maintenance-page.tpl - See below
# You can overwrite the default favion using a Photoshop ico plugin
# Two ways to add a footer: You can add a block to the footer section on /admin/build/block or add one on /admin/settings/site-information
# To prevent the "river of news" effect do not promote more than one post at a time to front

# Using the maintenance-page.tpl
# 1. Go to sites/default/settings.php (Make writable if necessary)
# 2. Around line 170, add the line code - Read notes in the settings.php page
# 3. You are all set, make any customizations to the template

############### Copy Code Below ###########
                                           
$conf['maintenance_theme'] = 'ishalist';  //
                                         
############## End Code Copy ##############

# Tested with most browsers: Firefox 3, IE8-IE5.5, Safari 3.2.1, Google Chrome 1.0.154.53, Opera 9.64
