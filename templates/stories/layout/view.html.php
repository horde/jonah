<?php
/**
 * Main layout for viewing story entries
 * Expects:
 *   ->tagcloud   (string, pre-rendered HTML)
 *   ->story      (array)
 *   ->shareUrl   (?string, URL or null)
 *   ->comments   (?array with 'threads' and 'comments' keys)
 *
 * Helpers available: jonahUrl(), jonahLink(), jonahIconLink(), jonahImage()
 */
?>
<?php if (!empty($this->tagcloud)): ?>
<?php echo $this->contentTag('div', $this->contentTag('div', $this->tagcloud, ['class' => 'tagSelector']), ['style' => 'float:right;']);?>
<div style="margin-right:170px;">
<?php else:?>
<div>
<?php endif;?>
  <?php echo $this->renderPartial('story', ['local' => ['story' => $this->story]]); ?>
  <?php echo $this->contentTag('div', (!empty($this->shareUrl) ? $this->jonahLink($this->shareUrl, _("Share this story")) : ''), ['class' => 'storyLinks']);?>
</div>
<?php
    if (!empty($this->comments)) {
        echo $this->contentTag('div', $this->comments['threads'] . $this->tag('br') . $this->comments['comments'], ['class' => 'storyComments']);
    }
