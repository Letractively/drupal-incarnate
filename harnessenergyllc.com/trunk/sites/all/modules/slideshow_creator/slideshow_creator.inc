<?php
// $Id: slideshow_creator.inc,v 1.1.2.33 2008/11/13 17:31:39 brmassa Exp $
/**
 * @author Bruno Massa http://drupal.org/user/67164
 * @file
 * Create true slideshows using any image over internet with many other features.
 */

/**
 * Get all images on a given directory
 *
 * @param $path
 *  String. The absolute path to start the scanner
 * @param $path
 *  Boolean. TRUE for recursive scanning
 * @return
 *  Array. All images' paths
 */
function _slideshow_creator_explore_dir($path = '', $recursive = FALSE) {
  // The path should be
  $path = variable_get('file_directory_path', conf_path() .'/files') . $path;

  // A list of image extensions. It should be on lower and
  // upper cases to work on non GNU systems, like Solaris and Windows
  $exts = array('png', 'gif', 'jpeg', 'jpg', 'bmp',
    'PNG', 'GIF', 'JPEG', 'JPG', 'BMP');

  // Get all files from a given path
  $files = array();
  foreach ($exts as $ext) {
    if ($new_files = glob("$path*.$ext")) {
      $files = array_merge($files, $new_files);
    }
  }

  // If its a recursive scan, enter in all directories
  // and scan for more image files
  if ($dirs = glob("$path*", GLOB_ONLYDIR) and !empty($recursive)) {
    foreach ($dirs as $dir) {
      $files = array_merge($files, _slideshow_creator_explore_dir("$dir/"));
    }
  }

  return $files;
}

/**
 * The main user hook to display a slideshow.
 *
 * @param $output
 *   String. All the page text with the slideshow code
 * @return
 *   String. All the page text with the slideshow HTML/Javascript
 */
function _slideshow_creator_process_text($output) {
  // Search for Slideshow Creator tags on the text.
  if (preg_match_all('/\[slideshow:([^]]+)\]/i', $output, $matches, PREG_SET_ORDER)) {
    $max_ss = count($matches);

    // For each of the instances, replace the tag for
    // the real HTML code
    foreach ($matches as $match) {
      $attributes = preg_split('|(?<!\\\),|', $match[1]);
      if (!empty($attributes)) {
        $output = preg_replace('/\[slideshow:([^]]+)\]/i',
          _slideshow_creator_tagconversion($max_ss, $attributes), $output, 1);
      }
    }
  }

  // Return the original text with all slideshows correctly
  // inserted
  return $output;
}

/**
 * Convert the slideshow tag into the data array that will
 * describe the Slideshow.
 *
 * @param &$max_ss
 *   Number. How many slideshows there are on the page
 * @param &$attributes
 *   Array. Each slideshow attributes, like 'img', 'dir', 'rotate'
 * @return
 *   String, The HTML code for the slideshow
 */
function _slideshow_creator_tagconversion(&$max_ss, &$attributes) {
  $output = '';

  // Using version system, its possible to expand the slideshow functionality
  // and support old versions
  switch ((int) $attributes[0]) {
    case 1:
    case 2:
    default:
      $slides = array(
        '#total'   => 0,
      );

      $attributes_count = count($attributes);
      foreach ($attributes as $attribute) {
        $attribute = explode('=', $attribute);

        // If the attribute has no value, skip the validation
        if (!isset($attribute[1]) or $attribute[1] == '') {
          continue;
        }

        if (trim(drupal_strtolower($attribute[0])) == 'img') { // img tag: URL|link|title|description|target format
          $img_attributes = explode('|' , $attribute[1]);
          if (!empty($img_attributes[1])) {
            $slides['#total']++;
            $slides[$slides['#total']] = array(
              '#src'         => _slideshow_creator_url(check_url(trim($img_attributes[1]))),
              '#link'        => empty($img_attributes[2]) ? '': _slideshow_creator_url(url(trim($img_attributes[2]))),
              '#title'       => empty($img_attributes[3]) ? '': check_plain(preg_replace('|\\\,|', ',', trim($img_attributes[3]))),
              '#description' => empty($img_attributes[4]) ? '': check_plain(preg_replace('|\\\,|', ',', trim($img_attributes[4]))),
              '#target'      => !empty($img_attributes[5]) ? check_plain(trim($img_attributes[5])) : '_blank',
            );
          }
        }
        elseif (trim(drupal_strtolower($attribute[0])) == 'dir') { // dir tag: scan all images from a given directory
          $dir_attributes = explode('|' , $attribute[1]);
          if (!empty($dir_attributes[1]) and $files = _slideshow_creator_explore_dir($dir_attributes[1], $dir_attributes[2])) {
            foreach ($files as $file) {
              $file = preg_replace('/^\.\//', '', $file);
              $slides['#total']++;
              $slides[$slides['#total']] = array(
                '#src'         => $file,
                '#link'        => empty($dir_attributes[3]) ? '': $file,
                '#title'       => empty($dir_attributes[4]) ? '': check_plain(preg_replace('|\\\,|', ',', trim($dir_attributes[4]))),
                '#description' => empty($dir_attributes[5]) ? '': check_plain(preg_replace('|\\\,|', ',', trim($dir_attributes[5]))),
                '#target'      => !empty($dir_attributes[6]) ? check_plain(trim($dir_attributes[6])) : '_blank',
              );
            }
          }
        }
        else { // all other tags
          $list = array(
            'layout'  => TRUE,
            'width'   => TRUE,
            'height'  => TRUE,
            'name'    => TRUE,
          );
          // For retro compatibility, we must filter some results
          // to simulate the previous behaviour
          if ($list[trim($attribute[0])]) {
            $slides['#'. trim($attribute[0])] = check_plain(trim($attribute[1]));
          }
          else {
            $value = check_plain(trim($attribute[1]));
            if (is_numeric($value)) {
              $value = (int) $value;
            }
            $slides['js'][trim($attribute[0])] = $value;
            if (trim($attribute[0]) == 'blend') {
              $slides['js']['speed'] = $slides['js']['blend'] * 1000;
              unset($slides['js']['blend']);
            }
            elseif (trim($attribute[0]) == 'rotate') {
              $slides['js']['timeout'] = $slides['js']['rotate'] * 1000;
              unset($slides['js']['rotate']);
            }
          }
        }
      }

      // If at least one slide is valid, build the Slideshow
      if ($slides['#total'] > 0) {

        // The slideshow should be displayed using
        // a theme function to allow users to change
        // it's general look
        $output = theme('slideshow_creator', $slides);
      }
      break;
  }
  return $output;
}

/**
 * Repair internal urls using the pathfilter module, if available.
 *
 * @param $url
 *   String, The url to complete (if internal)
 * @return
 *   String, The full url, filtered from internal
 */
function _slideshow_creator_url($url) {
  if (module_exists('pathfilter')) {
    // Wrap url in double quotes, filter and then remove double quotes
    return preg_replace("(^\"|\"$)", '', pathfilter_filter('process', 0, -1, '"'. $url .'"'));
  }
  else {
    // Return URL as-is
    return $url;
  }
}

/**
 * Format the slideshow
 * @ingroup themeable
 */
function theme_slideshow_creator($ss) {
  // To ensure that all slideshows have a unique
  // ID, the current_ss variable will hold a automatic
  // increasing number
  static $ssid = 0;
  ++$ssid;

  static $settings;
  if (empty($settings)) {
    $settings = variable_get('slideshow_creator_settings', array());
  }

  // Merge the current slideshow data with previously saved default settings
  $ss = array_merge($settings, $ss);

  // Default settings for jQuery Cycle plugin
  // The proper JavaScript commands of next and previuos
  $ss['js']['pause'] = TRUE;
  $ss['js']['next']  = '#ssc-next-'. $ssid;
  $ss['js']['prev']  = '#ssc-previous-'. $ssid;

  // This will put the pieces into a given order,
  // base on which "layout" the user chose.
  if (empty($ss['#layout']) or $ss['#layout'] == 'default') {
    $layout = array('#previous', '#status', '#next', array('#title', '#main', '#description'));
  }
  elseif ($ss['#layout'] == 'reverse') {
    $layout = array(array('#title', '#main', '#description'), '#previous', '#status', '#next');
  }
  elseif ($ss['#layout'] == 'bottom') {
    $layout = array('#previous', '#status', '#next', array('#title', '#description', '#main'));
  }
  elseif ($ss['#layout'] == 'top') {
    $layout = array(array('#main', '#title', '#description'), '#previous', '#status', '#next');
  }
  elseif ($ss['#layout'] == 'none') {
    $layout = array(array('#main', '#title', '#description'));
  }

  _theme_slideshow_creator_nextprevious($ssid, $ssc, $ss);

  // Building the slideshow parts: title, name, slideshow weight and height
  $css_class      = !empty($ss['name']) ? $ss['name'] : '';

  // Setting the image height and width
  $css_width  = $ss['#width'] .'px';
  $css_height = $ss['#height'] .'px';

  // Create each image. The order should be:
  // 1* the current picture to the end
  // 2* the first picture to the current
  for ($i = $ss['#current']; $i <= $ss['#total']; ++$i) {
    _theme_slideshow_creator_content($ssid, $ssc, $ss[$i - 1]);
  }
  for ($i = 1; $i < $ss['#current']; ++$i) {
    _theme_slideshow_creator_content($ssid, $ssc, $ss[$i - 1]);
  }

  // Put all pieces togheter, according to layout
  foreach ($layout as $piece) {
    if (is_array($piece)) {
      $output .= "<span id='ssc-content-$ssid' class='ssc-content' style=\"height:$css_height;width:$css_width;\">\n";
      foreach (array_keys($ssc['#content']) as $slide) {
        $output .= "<span id='ssc-slide-$ssid-$slide' style=\"height:$css_height;width:$css_width;\">\n";
        foreach ($ssc['#content'][$slide] as $piece2) {
          $output .= $piece2;
        }
        $output .= "</span>\n";
      }
      $output .= "</span>\n";
    }
    else {
      $output .= $ssc[$piece];
    }
  }

  // Add the CSS file
  drupal_add_css(drupal_get_path('module', 'slideshow_creator') .'/slideshow_creator.css');

  // Add the main JavaScript, that does all the magic
  drupal_add_js(drupal_get_path('module', 'slideshow_creator') .'/slideshow_creator.js');
  drupal_add_js(drupal_get_path('module', 'jquery_plugin') .'/jquery.cycle.min.js');

  // Filter the JS object to avoid JS injection
  // NOTE: this list should be periodically updated with cycle jQuery plugin
  $cycle_attributes = array_flip(array('', 'fx', 'timeout', 'continuous', 'speed', 'speedIn', 'speedOut',
    'next', 'prev', 'prevNextClick', 'pager', 'pagerClick', 'pagerEvent',
    'pagerAnchorBuilder', 'before', 'after', 'end', 'easing', 'easeIn', 'easeOut',
    'shuffle', 'animIn', 'animOut', 'cssBefore', 'cssAfter', 'fxFn', 'height',
    'startingSlide', 'sync', 'random', 'fit', 'pause', 'autostop', 'autostopCount',
    'delay', 'slideExpr', 'cleartype', 'nowrap'));
  foreach ($ss['js'] as $attribute) {
    if (empty($cycle_attributes[$attribute])) {
      unset($ss['js'][$attribute]);
    }
  }

  drupal_add_js(array('ssc' => array($ssid => $ss['js'])), 'setting');

  // Put everything inside a SPAN tag
  return  "<span class='ssc $css_class' id='ssc-$ssid' style='width:$css_width;'>\n$output</span>\n";
}

/**
 * Build the main content of the slideshow. It needs a different
 * process because the content is the union of separated parts
 * that are build in a loop process.
 *
 * @param $ssid
 *   Number. The slideshow unique ID
 * @param &$ssc
 *   Array. The slideshow final data array
 * @param &$ss
 *   Array. The partial data array, extracted from the ssc tag
 */
function _theme_slideshow_creator_content($ssid, &$ssc, &$ss) {
  $title = !empty($ss['#title']) ? $ss['#title'] : '';

  if (!empty($ss['#src'])) {
    // Build the image
    $image = theme('image', $ss['#src'], check_plain($title),
      check_plain($title), NULL, FALSE);

    // Add a new image link
    if (empty($ss['#link'])) {
      $main = $image ."\n";
    }
    else {
      $main = l($image, $ss['#link'], array('html' => TRUE)) ."\n";
    }
  }
  else {
    $main = $ss['#main'];
  }

  $ssc['#content'][] = array(
    '#title'       => "<span class='ssc-title'>$title</span>\n",
    '#description' => '<span class="ssc-description">'. $ss['#description'] ."</span>\n",
    '#main'        => "<div class='ssc-main'>$main</div>\n",
  );
}

/**
 * To build the correct link for NEXT and PREVIOUS
 * buttons (for non-javascript browsers), we need to
 * know which slide each slideshow is now.
 *
 * @param $ssid
 *   Number. The slideshow unique ID
 * @param &$ssc
 *   Array. The slideshow final data array
 * @param &$ss
 *   Array. The partial data array, extracted from the ssc tag
 */
function _theme_slideshow_creator_nextprevious($ssid, &$ssc, &$ss) {
  if (!empty($_GET['ssc'])) {
    $status_sscs = explode(';', $_GET['ssc']);
    foreach ($status_sscs as $status_ssc) {
      $temp = explode(',' , $status_ssc);
      if ($temp[0] == $ssid) {
        $sscs['next'][] = $temp[1] + 1 > $ss['#total'] ? $ssid .',1' : $ssid .','. ($temp[1] + 1);
        $sscs['previous'][] = $temp[1] - 1 < 1 ? $ssid .','. $ss['#total'] : $ssid .','. ($temp[1] - 1);
        $ss['#current'] = $temp[1];
        $toggle = TRUE;
      }
      else {
        $sscs['next'][] = $status_ssc;
        $sscs['previous'][] = $status_ssc;
        $ss['#current'] = 1;
      }
    }
  }

  if (empty($toggle)) {
    $sscs['next'][] = $ss['#total'] > 1 ? check_plain($ssid .',2') : check_plain($ssid .',1');
    $sscs['previous'][] = check_plain($ssid .','. $ss['#total']);
    $ss['#current'] = 1;
  }

  $ss['link_next']      = check_plain(implode(';', $sscs['next']));
  $ss['link_previous']  = check_plain(implode(';', $sscs['previous']));

  // Put a PREVIOUS and NEXT buttons if there are more
  // then one image (generally TRUE for a slideshow)
  if ($ss['#total'] > 1) {
    $ssc['#previous'] = l(t('Previous'), $_GET['q'], array('query' =>  'ssc='. $ss['link_previous'],
      'attributes' => array('id' => "ssc-previous-$ssid", 'class' => 'ssc-previous')));
    $ssc['#next']     = l(t('Next'), $_GET['q'],  array('query' => 'ssc='. $ss['link_next'],
      'attributes' => array('id' => "ssc-next-$ssid", 'class' => 'ssc-next'))) ."\n";
    $ssc['#status']   = " <span class='ssc-index' id='ssc-index-$ssid'>".
      t($ss['#current_slide_string']) ." <span class='ssc-current' id='ssc-current-$ssid'>".
      $ss['#current'] .'</span>/'. $ss['#total'] .' </span>';
  }
}
