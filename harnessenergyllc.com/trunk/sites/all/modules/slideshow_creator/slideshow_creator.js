/**
 * $Id: slideshow_creator.js,v 1.3.2.8 2008/11/13 00:27:41 brmassa Exp $
 * @author: Bruno Massa http://drupal.org/user/67164
 * @file slideshow_creator.js
 * The main Javacript for this module
 */
/*global Drupal, $ */

/**
 * Initialize the module's JS functions
 */
Drupal.behaviors.ssc = function(context) {
  for (var ss in Drupal.settings.ssc) {
    if (Drupal.settings.ssc.hasOwnProperty(ss)) {
      Drupal.settings.ssc[ss].before = Drupal.ssc_before;
      $("#ssc-content-" + ss).cycle(Drupal.settings.ssc[ss]);
    }
  }
};

Drupal.ssc_before = function() {
  var sscid = this.id.replace(/ssc-slide-/, "").replace(/-.*/, "");
  var slide = parseInt(this.id.replace(/ssc-slide-.*-/, ""), 10) + 1;
  $("#ssc-current-" + sscid).html(slide);
};
