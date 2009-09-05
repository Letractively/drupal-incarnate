<?php
/** $Id: template.php,v 1.3 2009/06/28 08:04:25 ishmaelsanchez Exp $
 *
 * This file contains theme override functions and preprocess functions
 * 
 * If you prefer the Drupal default rendering simply comment out the functions.
 * Additionally you can add your own custom functions and variable here
 *
 */

// Overriding function, adding text, and modifying default seperator
function ishalist_breadcrumb($breadcrumb)
{
  if (!empty($breadcrumb)) 
    {
      return '<div class="breadcrumb"> Path: '. implode(' / ', $breadcrumb) .'</div>';
    }
}

//Get rid of the default "Submitted by.." text on nodes
function ishalist_node_submitted($node) {
  return t('Posted by !username on @datetime',
    array(
      '!username' => theme('username', $node),
      '@datetime' => format_date($node->created, 'custom', 'M j, Y'),
    ));
}

//Get rid of the default "Submitted by.." text for comments
function ishalist_comment_submitted($comment) {
  return t('Comment by !username posted on @datetime',
    array(
      '!username' => theme('username', $comment),
      '@datetime' => format_date($comment->timestamp, 'custom', 'M j, Y')
    ));
}



	