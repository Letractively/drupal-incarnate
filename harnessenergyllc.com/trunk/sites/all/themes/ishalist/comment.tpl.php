<?php
/** $Id: comment.tpl.php,v 1.3 2009/07/22 05:55:33 ishmaelsanchez Exp $
 *
 * Default theme implementation for comments.
 *
 * Available variables:
 * - $author: Comment author. Can be link or plain text.
 * - $content: Body of the post.
 * - $date: Date and time of posting.
 * - $links: Various operational links.
 * - $new: New comment marker.
 * - $signature: Authors signature.
 * - $status: Comment status. Possible values are:
 *   comment-unpublished, comment-published or comment-preview.
 * - $submitted: By line with date and time.
 * - $title: Linked title.
 *
 */
?>
<div class="comment<?php print ($comment->new) ? ' comment-new' : ''; print ' '. $status ?> clear-block">

  <?php if ($comment->new): ?>
    <span class="new"><?php print $new ?></span>
  <?php endif; ?>

  <p><?php print $title ?></p>

  <div class="submitted">
    <?php print $submitted ?>
  </div>

    <?php print $content ?>

  <div class="comment-add"><?php print $links ?></div>
</div>
