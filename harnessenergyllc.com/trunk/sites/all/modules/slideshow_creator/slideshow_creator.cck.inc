<?php
// $Id: slideshow_creator.cck.inc,v 1.4.2.6 2008/07/06 08:26:38 brmassa Exp $
/**
 * @author Bruno Massa http://drupal.org/user/67164
 * @file
 * Transform images into animated slideshows, using CCK
 *
 * Project Page  : http://drupal.org/project/slideshow_creator
 * Support Queue : http://drupal.org/project/issues/slideshow_creator
 */

/**
 * Implementation of hook_content_is_empty().
 */
function slideshow_creator_content_is_empty($item, $field) {
  return FALSE;
}

/**
 * Implementation of hook_field_info().
 *
 * Its a CCK hook that describes a type of fields. On CCK taxonomy,
 * fields are the types of data (like text, numbers or slideshows),
 * that can be used by several widgets to store and several formatters
 * display this data.
 *
 * @return
 *   Array. Keyed by field type name. Each element of the array is an
 *   array with these keys and values:
 *   - "label":       The human-readable label for the field type.
 *   - "description": The main description of this field. What kind of
 *                    it stores.
 *   - "callbacks":   An array describing the callback functions that this
 *                    field uses for tables and arguments.
 */
function slideshow_creator_field_info() {
  return array(
    'slideshow' => array(
      'callbacks'   => array(
        'tables'    => CONTENT_CALLBACK_DEFAULT,
        'arguments' => CONTENT_CALLBACK_DEFAULT,
        ),
      'description'     => t('Insert Slideshows'),
      'label'           => t('Slideshow'),
      'multiple values' => CONTENT_HANDLE_CORE,
    ),
  );
}

/**
 * Implementation of hook_field_formatter_info().
 *
 * Its a CCK hook, that describes each formatter. No CCK taxonomy,
 * formatters displays a data saved on "fields" on screen.
 *
 * @return
 *   Array. Keyed by formatter name, each element of the array is an associative
 *   array with these keys and values:
 *   - "label": The human-readable label for the formatter.
 *   - "field types": An array of field type names that can be displayed using
 *     this formatter.
 */
function slideshow_creator_field_formatter_info() {
  $formatters = array(
    'default' => array(
      'label'           => 'Default',
      'arguments'       => array('element'),
      'multiple values' => CONTENT_HANDLE_CORE,
      'field types'     => array('slideshow'),
    ),
  );
  return $formatters;
}

/**
 * Implementation of hook_field_settings().
 *
 * @param $op
 *   String. The operation to be performed.
 * @param $field
 *   Array. The field on which the operation is to be performed.
 * @return
 *   Depends. This varies depending on the operation.
 *   - "form": an array of form elements to add to
 *     the settings page.
 *   - "validate": no return value. Use form_set_error().
 *   - "save": an array of names of form elements to
 *     be saved in the database.
 *   - "database columns": an array keyed by column name, with arrays of column
 *     information as values.
 *   - "filters": an array whose values are 'filters'
 *     definitions as expected by views.module (see Views Documentation).
 *   - "callbacks": an array describing the field's behaviour regarding hook_field
 *     operations. The array is keyed by hook_field operations ('view', 'validate'...)
 *     and has the following possible values :
 *       CONTENT_CALLBACK_NONE     : do nothing for this operation
 *       CONTENT_CALLBACK_CUSTOM   : use the behaviour in hook_field(operation)
 *       CONTENT_CALLBACK_DEFAULT  : use content.module's default bahaviour
 *     Note : currently only the 'view' operation implements this feature.
 *     All other field operation implemented by the module _will_ be executed
 *     no matter what.
 */
function slideshow_creator_field_settings($op, $field) {
  if ($op == 'database columns') {
    return array(
      'ssc' => array(
        'type'        => 'text',
      ),
      'url' => array(
        'type'        => 'text',
        'length'      => 75,
      ),
      'dir' => array(
        'type'        => 'varchar',
        'length'      => 255,
      ),
      'rotate' => array(
        'type'        => 'text',
        'length'      => 255,
      ),
      'fx' => array(
        'type'        => 'varchar',
        'length'      => 255,
      ),
      'blend' => array(
        'type'        => 'varchar',
        'length'      => 16,
      ),
      'height' => array(
        'type'        => 'varchar',
        'length'      => 2,
      ),
      'width' => array(
        'type'        => 'varchar',
        'length'      => 16,
      ),
      'layout' => array(
        'type'        => 'varchar',
        'length'      => 16,
      ),
      'name' => array(
        'type'        => 'varchar',
        'length'      => 16,
      ),
    );
  }
  elseif ($op ==  'callbacks') {
    // The simple callback map for such field
    return array(
      'view' => CONTENT_CALLBACK_CUSTOM,
    );
  }
}

/**
 * slideshow_creator_form is a ficticious form field. We need to build
 * the real form fields, which is a series of values inside a fieldset
 *
 * @ingroup form
 */
function _slideshow_creator_form_process($element, $edit, $form_state, $form) {
  // The values are stored into a single text DB field.
  // However, the slideshow form needs several separated values, so
  // we need to unserialized string data.
  $values = unserialize($element['#value']['ssc']);

  // Get the default settings in case its the first time.
  // It will make the creation of a Slideshow faster and easier
  if (empty($values['#url'])) {
    $values = variable_get('slideshow_creator_settings', array());
  }

  $element['ssc_form'] = array(
    '#collapsed'     => TRUE,
    '#collapsible'   => TRUE,
    '#title'         => empty($element['#title']) ? t('Slideshow') : $element['#title'],
    '#tree'          => TRUE,
    '#type'          => 'fieldset',
  );
  $element['ssc_form']['url'] = array(
    '#default_value' => $values['#url'],
    '#description'   => t('On each line, fill your slides using the <b>IMAGE_URL|LINK|TITLE|DESCRIPTION|TARGET</b> pattern.'),
    '#title'         => t('Image'),
    '#type'          => 'textarea',
  );
  $element['ssc_form']['dir'] = array(
    '#default_value' => $values['#dir'],
    '#description'   => t('On each line, fill your slides using the <b>DIR_IMAGE|DIR_RECURSIVE|DIR_LINK|TITLE|DESCRIPTION|TARGET</b> pattern.'),
    '#title'         => t('Image directory'),
    '#type'          => 'textarea',
  );
  $element['ssc_form']['rotate'] = array(
    '#default_value' => $values['#rotate'],
    '#description'   => t('The rotation speed, in seconds. Leave it blank for no rotation.'),
    '#size'          => 5,
    '#title'         => t('Rotation Speed'),
    '#type'          => 'textfield',
  );
  $element['ssc_form']['fx'] = array(
    '#default_value' => $values['#fx'],
    '#description'   => t('The transition effect'),
    '#options'       => array(
      ''            => t('None'),
      'fade'        => t('Fade'),
      'shuffle'     => t('Shuffle'),
      'zoom'        => t('Zoom'),
      'scrollLeft'  => t('Scroll to Left'),
    ),
    '#title'         => t('Effect'),
    '#type'          => 'select',
  );
  $element['ssc_form']['blend'] = array(
    '#default_value' => $values['#blend'],
    '#description'   => t('The effect speed, in seconds. Leave it blank for no blending.'),
    '#size'          => 5,
    '#title'         => t('Effect Speed'),
    '#type'          => 'textfield',
    '#weight'        => 1,
  );
  $element['ssc_form']['width'] = array(
    '#default_value' => $values['#width'],
    '#description'   => t('The slide width, in pixels.'),
    '#size'          => 5,
    '#title'         => t('Width'),
    '#type'          => 'textfield',
    '#weight'        => 2,
  );
  $element['ssc_form']['height'] = array(
    '#default_value' => $values['#height'],
    '#description'   => t('The slide height, in pixels.'),
    '#size'          => 5,
    '#title'         => t('Height'),
    '#type'          => 'textfield',
    '#weight'        => 2,
  );
  $element['ssc_form']['layout'] = array(
    '#default_value' => $values['#layout'],
    '#description'   => t('The customized layout name. Leave it blank for default layout.'),
    '#options'       => array(
      'default' => t('Default'),
      'reverse' => t('Reverse'),
      'bottom'  => t('Bottom'),
      'top'     => t('Top'),
      'none'    => t('None'),
    ),
    '#title'         => t('Layout'),
    '#type'          => 'select',
    '#weight'        => 3,
  );
  $element['ssc'] = array(
    '#type'          => 'value',
    '#value'         => $values,
  );

  return $element;
}

/**
 * Validate the 'slideshow_creator_form' form field. Also serialize all values.
 *
 * Since the only value that CCK will save is 'ssc', which is a string,
 * we need to serialize all values first.
 *
 * @ingroup form
 */
function _slideshow_creator_form_validate(&$element, &$form_state) {
  // The values are stored into a single text DB field.
  // However, the slideshow form needs several separated values, so
  // we need to store it as a serialized array.
  foreach (element_children($element['ssc_form']) as $field) {
    $ssc['#'. $field] = $element['ssc_form'][$field]['#value'];
  }
  form_set_value($element['ssc'], serialize($ssc), $form_state);
}

/**
 * Implementation of hook_widget().
 *
 * Defines the widget fields during node's creation
 */
function slideshow_creator_widget(&$form, &$form_state, $field, $items, $delta = 0) {
  $element = array(
    '#default_value' => isset($items[$delta]) ? $items[$delta] : '',
    '#type'          => 'slideshow_creator_form',
  );
  return $element;
}

/**
 * Implementation of hook_widget_info().
 *
 * Its a CCK hook, that describes each widget. No CCK taxonomy,
 * widgets are responsible for getting the information from the
 * user and store it on a standard form, a CCK "field".
 *
 * @return
 *   Array. Keyed by widget name. Each element of the array is an
 *   array with these keys and values:
 *   - "label": The human-readable label for the widget.
 *   - "field types": An array of field type names that can be edited using
 *     this widget.
 */
function slideshow_creator_widget_info() {
  return array(
    'slideshow' => array(
      'callbacks'       => array('default value' => CONTENT_CALLBACK_DEFAULT),
      'field types'     => array('slideshow'),
      'label'           => t('Slideshow'),
      'multiple values' => CONTENT_HANDLE_CORE,
    ),
  );
}

/**
 * Format the slideshow widget
 *
 * @ingroup themeable
 */
function theme_slideshow_creator_form($element) {
  return $element['#children'];
}

/**
 * Format the slideshow widget
 *
 * @ingroup themeable
 */
function theme_slideshow_creator_formatter_default($element) {
  $ssc = unserialize($element['#item']['ssc']);
  $attributes = array();

  // Add special attributes to this slideshow
  array_push($attributes, empty($ssc['#name'])   ? '' : 'name='.   $ssc['#name']);
  array_push($attributes, empty($ssc['#rotate']) ? '' : 'rotate='. $ssc['#rotate']);
  array_push($attributes, empty($ssc['#blend'])  ? '' : 'blend='.  $ssc['#blend']);
  array_push($attributes, empty($ssc['#height']) ? '' : 'height='. $ssc['#height']);
  array_push($attributes, empty($ssc['#width'])  ? '' : 'width='.  $ssc['#width']);
  array_push($attributes, empty($ssc['#layout']) ? '' : 'layout='. $ssc['#layout']);

  // Check it there is images on the slideshow: it can be
  //   - direct list of images
  //   - local directory where several images are
  if (!empty($ssc['#url']) or !empty($ssc['#dir'])) {
    if ($urls = split("\n", $ssc['#url'])) {
      while (list($index, $url) = each($urls)) {
        $urls[$index] = 'img=|'. $url;
      }
    }
    if ($dirs = split("\n", $ssc['#dir'])) {
      while (list($index, $url) = each($dirs)) {
        $dirs[$index] = 'dir=|'. $url;
      }
    }
    $images = array_merge($urls, $dirs);

    // Include the proper file to deal with this
    include_once drupal_get_path('module', 'slideshow_creator') .'/slideshow_creator.inc';

    // Build the Slideshow Creator tag and send it to
    // the parser, that will transform it into a slideshow
    return _slideshow_creator_process_text('[slideshow: 2,'. implode(',', $attributes) .','. implode(',', $images) .']');
  }
  return '';
}
