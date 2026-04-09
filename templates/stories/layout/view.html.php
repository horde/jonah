<?php
/**
 * Main layout for viewing story entries
 * Expects:
 *   ->tagcloud
 *   ->story
 *   ->sharelink
 *   ->comments
 */
?>
<?php if (!empty($this->tagcloud)): ?>
<?php echo $this->contentTag('div', $this->contentTag('div', $this->tagcloud, ['class' => 'tagSelector']), ['style' => 'float:right;']);?>
<div style="margin-right:170px;">
<?php else:?>
<div>
<?php endif;?>
  <?php echo $this->renderPartial('story', ['local' => ['story' => $this->story]]); ?>
  <?php echo $this->contentTag('div', (!empty($this->sharelink) ? $this->sharelink : ''), ['class' => 'storyLinks']);?>
</div>
<?php
    if (!empty($this->comments)) {
        echo $this->contentTag('div', $this->comments['threads'] . $this->tag('br') . $this->comments['comments'], ['class' => 'storyComments']);
    }
