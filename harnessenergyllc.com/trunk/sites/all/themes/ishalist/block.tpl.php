<?php
/** $Id: block.tpl.php,v 1.2 2009/04/16 09:35:29 ishmaelsanchez Exp $
 *
 * Available variables:
 * - $block->subject: Block title.
 * - $block->content: Block content.
 * - $block->module: Module that generated the block.
 * - $block->delta: This is a numeric id connected to each module.
 *
 * Helper variables:
 * - $is_front: Flags true when presented in the front page.
 * - $logged_in: Flags true when the current user is a logged-in member.
 * - $is_admin: Flags true when the current user is an administrator.
 *
 */
?>

<div id="block-<?php print $block->module .'-'. $block->delta; ?>" class="block block-<?php print $block->module ?>">
  <?php if ($block->subject): ?>
    <h3><?php print $block->subject ?></h3>
  <?php endif;?>
  <?php print $block->content ?>
</div>
