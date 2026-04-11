<?php
/**
 * Template for stories index page - lists available stories.
 *
 * Expects:
 *   ->stories (array with view_url, channel_id, can_edit, can_delete flags)
 *   ->read    (bool)
 *   ->comments (bool)
 *
 * Helpers available: jonahUrl(), jonahLink(), jonahIconLink(), jonahImage()
 */
?>
<?php if (!empty($this->stories)): ?>
<table width="100%" cellspacing="0" class="linedRow nowrap">
 <tr class="item">
  <th width="1%">&nbsp;</th>
  <th class="leftAlign"><?php echo _("Story") ?></th>
  <th class="leftAlign"><?php echo _("Date") ?></th>
  <?php if ($this->read): ?>
    <th class="leftAlign"><?php echo _("Read") ?></th>
  <?php endif; ?>
  <?php if ($this->comments): ?>
    <th class="leftAlign"><?php echo _("Comments") ?></th>
  <?php endif; ?>
 </tr>
 <?php foreach ($this->stories as $story): ?>
   <tr>
    <td>
     <?php echo $this->jonahIconLink($this->jonahUrl('StoryPdf', ['id' => $story['id'], 'channel_id' => $story['channel_id']]), 'mime/pdf.png', _("PDF version")) ?>
     <?php if ($story['can_edit']): ?>
      <?php echo $this->jonahIconLink($this->jonahUrl('StoryEdit', ['id' => $story['id'], 'channel_id' => $story['channel_id']]), 'edit.png', _("Edit story")) ?>
     <?php endif ?>
     <?php if ($story['can_delete']): ?>
      <?php echo $this->jonahIconLink($this->jonahUrl('StoryDelete', ['id' => $story['id'], 'channel_id' => $story['channel_id']]), 'delete.png', _("Delete story")) ?>
     <?php endif ?>
    </td>
    <td>
     <?php echo $this->jonahLink($story['view_url'], htmlspecialchars($story['title']), $story['description'] ?? '') ?>
    </td>
    <td>
     <?php echo $story['published_date'] ?>
    </td>
    <?php if ($this->read): ?>
    <td>
     <?php echo $story['readcount'] ?>
    </td>
    <?php endif; ?>
    <?php if ($this->comments): ?>
    <td>
     <?php echo $story['comments'] ?>
    </td>
    <?php endif; ?>
   </tr>
  <?php endforeach; ?>
</table>
<?php else: ?>&nbsp;<?php endif;?>
