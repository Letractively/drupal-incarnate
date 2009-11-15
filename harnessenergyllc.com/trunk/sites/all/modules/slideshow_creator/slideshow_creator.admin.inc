<?php
// $Id: slideshow_creator.admin.inc,v 1.1.2.5 2008/11/13 00:47:43 brmassa Exp $
/**
 * @author Bruno Massa http://drupal.org/user/67164
 * @file
 * Create true slideshows using any image over internet with many other features.
 */

/**
 * Module settings page. Users can set the default layout
 * of their charts.
 *
 * @ingroup form
 */
function _slideshow_creator_admin() {
  // Get the default settings for all slideshows
  $settings = variable_get('slideshow_creator_settings', array());

  // Build the form
  $form['height'] = array(
    '#default_value'  => empty($settings['#height']) ? 400 : $settings['#height'],
    '#description'    => t('The slideshow height, in pixels.'),
    '#title'          => t('Height'),
    '#type'           => 'textfield',
  );
  $form['width'] = array(
    '#default_value'  => empty($settings['#width']) ? 400 : $settings['#width'],
    '#description'    => t('The slideshow width, in pixels.'),
    '#title'          => t('Width'),
    '#type'           => 'textfield',
  );
  $form['layout'] = array(
    '#default_value'  => empty($settings['#layout']) ? 'default' : $settings['#layout'],
    '#description'    => t('The slideshow layout'),
    '#options'        => array(
      'default' => t('Default'),
      'reverse' => t('Reverse'),
      'top'     => t('Top'),
      'bottom'  => t('Bottom'),
    ),
    '#title'          => t('Layout'),
    '#type'           => 'select',
  );
  $form['current_slide_string'] = array(
    '#default_value'  => !isset($settings['#current_slide_string']) ? 'Slide' : $settings['#current_slide_string'],
    '#description'    => t('The text to be displayed on the Current Slide part.'),
    '#title'          => t('Current Slide String'),
    '#type'           => 'textfield',
  );

  // Submit button
  $form['submit'] = array(
    '#value'          => t('Save these settings'),
    '#type'           => 'submit',
  );

  return $form;
}

/**
 * Module settings page. Users can set the default layout
 * of their charts.
 *
 * @ingroup form
 */
function _slideshow_creator_admin_submit(&$from, &$form_state) {
  $settings = $form_state['values'];
  unset($settings['submit']);
  unset($settings['form_id']);
  unset($settings['form_build_id']);
  unset($settings['form_token']);
  unset($settings['op']);

  // Add a '#' in all field names
  foreach ($settings as $index => $value) {
    $settings["#$index"] = $value;
    unset($settings[$index]);
  }

  // Save the data into database
  variable_set('slideshow_creator_settings', $settings);

  // Print a 'OK' message
  drupal_set_message('Settings saved');
}
