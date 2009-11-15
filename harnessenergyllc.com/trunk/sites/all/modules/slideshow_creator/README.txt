// $Id: README.txt,v 1.3.2.3 2008/11/13 17:32:24 brmassa Exp $
********************************************************************************
                     D R U P A L    M O D U L E
********************************************************************************
Module Name        : Slideshow Creator
Original Author    : Bruno Massa http://drupal.org/user/67164
General Links:
Project Page       : http://drupal.org/project/slideshow_creator
Support Queue      : http://drupal.org/project/issues/slideshow_creator

********************************************************************************


DESCRIPTION
-----------

Slideshow Creator creates a true slideshows using any image over internet with many other features.
If the user does not have JavaScript enabled, it degrades to a "regular" slideshow where the "next" button points to the next image and a whole new page is loaded.


FEATURES
--------

* CCK: Slideshow Creator has its own widget to CCK
* Automaticaaly extract images from a given directory
* Convenient: can be inserted in any node type
* Customize: add as many images you want, rotate speed and even more than one slideshow per page
* Lightweight: the JavaScript file is very small
* Themable: use a CSS to customize the look
* Usable: JavaScript enhance the slideshow, but it is not required
* Valid: the code is XHTML 1.0 Strict


USE
---

In any node, add the string:
[slideshow:VERSION, rotate=ROTATE_SPEED, blend=BLEND, layout=LAYOUT, name=SLIDESHOW_NAME, height=HEIGHT, width=WIDTH, img=|IMAGE_URL|LINK|TITLE|DESCRIPTION|TARGET|, img=|IMAGE_URL|LINK|TITLE|DESCRIPTION|TARGET|]

where:
VERSION [required]: the slideshow filter version: currently 2
ROTATE_SPEED [optional]: the speed, in seconds, to rotate images (0 to not rotate at all)
SLIDESHOW_NAME [optional]: you can theme your slideshow thru CSS giving it a class name
HEIGHT [optional]: all images will be at the same height
WIDTH [optional]: all images will be at the same width
BLEND [optional]: how long will take the fading transaction between images
LAYOUT [optional]: the buttons, title and description can be displayed under several combinations. You can choose between top, bottom, reverse or default.

than, for each image you want to insert, use:
IMAGE_URL [required]: the image itself
LINK [optional]: if you want to provide a link to some page, put the URL here
TITLE [optional]: often the bold text over the image
DESCRIPTION [optional]: often the text under the image
TARGET [optional]: where the linked page will show: _blank (default) will show on another window, _parent and _top is only used when pages have frames, _self shows on the very window or you can use a window name.

New feature: automatically extract images from a given directory
You can create a normal slideshow, but you can scan for more images using dir=|DIR_IMAGE|DIR_RECURSIVE|DIR_LINK|TITLE|DESCRIPTION|TARGET|

where:
DIR_IMAGE [required]: the diretory that you want to scan for images. The folder base is the default site files foder, generally "sites/default/files".
DIR_RECURSIVE [optional]: "yes" if you want to scan recursively or leave it blank
DIR_LINK [optional]: "yes" if you want link all images to their own path or leave it blank
TITLE,DESCRIPTION and TARGET are applied to all images

example: (you can copy and paste)
[slideshow: 2, rotate=2, blend=1, img=|http://drupal.org/themes/bluebeach/logos/drupal.org.png|drupal.org|Drupal|The ultimate CMS. Download it now!|Drupal|, img=|http://www.mysql.com/common/logos/mysql_100x52-64.gif|http://www.mysql.com|MySQL|Free and reliable SQL server and client.|_self|, dir=|/|yes||Generic Photos|Arent they great?||]


INSTALLATION
------------
1* Just copy the folder to your /modules/ folder
2* Activate the module in your Drupal installation. Go to administer > site building > modules
3* Go to administer > site configuration > input formats and add slideshow filter in any filter type your site have.


KNOWN ISSUES AND SPECIAL SITUATIONS
-----------------------------------
1* the "target" feature is only available thru JavaScript, in order to maintain the page XHTML 1.0 Strict
2* blend feature don't work on Konqueror browser 3.x, unfortunately. Normal on Konqueror 4.x.
3* you want to hide the buttons? put the name=BLABLABLA on [slideshow..] tag and, on CSS, put
.BLABLABLA .ssc-previous,.BLABLABLA .ssc-next,.BLABLABLA .ssc-index,.BLABLABLA {display:none;}


DEVELOPMENT
-----------

If you have suggestions or complains, don't hesitate to post your suggestions as an issue on drupal.org.
If you know PHP and Drupal and want to help on this module, post your code now!
Translations are also welcome! I will include all translated code and documentation.

This module is sponsored by the brazilian company Titan Atlas (titanatlas.com).