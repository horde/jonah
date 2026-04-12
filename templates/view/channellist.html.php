<?php
/**
 * Channel list view. Expects:
 *  ->channels (array with can_edit, can_delete flags, stories_url,
 *              feed_html_url, feed_html_fallback, feed_rss_url, feed_rss_fallback)
 *
 * Helpers available: jonahUrl(), jonahIconLink(), jonahImage()
 */
?>
<div class="header">
 <?php echo _("Manage Feeds") ?>
 <a id="quicksearchL" href="#" title="<?php echo _("Search")?>" onclick="$('quicksearchL').hide(); $('quicksearch').show(); $('quicksearchT').focus(); return false;"><?php echo $this->jonahImage('search.png', _("Search"))?></a>
 <div id="quicksearch" style="display:none;">
  <input type="text" name="quicksearchT" id="quicksearchT" for="feeds-body" empty="feeds-empty" />
  <small>
   <a title="<?php echo _("Close Search")?>" href="#" onclick="$('quicksearch').hide(); $('quicksearchT').value = ''; QuickFinder.filter($('quicksearchT')); $('quicksearchL').show(); return false;">X</a>
  </small>
 </div>
</div>

<?php if (count($this->channels)):?>
    <table id="feeds" width="100%" class="sortable" cellspacing="0">
    <thead>
     <tr>
      <th width="1%">&nbsp;</th>
      <th class="sortdown"><?php echo _("Name")?></th>
      <th><?php echo _("Delivery URLs")?></th>
      <th><?php echo _("Last Update")?></th>
     </tr>
    </thead>

    <tbody id="feeds-body">
     <?php foreach ($this->channels as $channel):?>
     <tr>
      <td class="nowrap">
       <?php if ($channel['can_edit']): ?>
        <?php echo $this->jonahIconLink($this->jonahUrl('ChannelEdit', ['channel_id' => $channel['channel_id']]), 'edit.png', _("Edit channel")) ?>
       <?php endif ?>
       <?php echo $this->jonahIconLink($this->jonahUrl('StoryCreate', ['channel_id' => $channel['channel_id']]), 'new.png', _("Add story")) ?>
       <?php if ($channel['can_delete']): ?>
        <?php echo $this->jonahIconLink($this->jonahUrl('ChannelDelete', ['channel_id' => $channel['channel_id']]), 'delete.png', _("Delete channel")) ?>
       <?php endif ?>
      </td>
      <td>
       <a href="<?php echo $channel['stories_url']?>"><?php echo $channel['channel_name']?></a>
      </td>
      <td>
       <a href="<?php echo htmlspecialchars($channel['feed_html_url'])?>"><?php echo _("HTML")?></a>
       (<a href="<?php echo htmlspecialchars($channel['feed_html_fallback'])?>"><?php echo _("fallback")?></a>)
       &middot;
       <a href="<?php echo htmlspecialchars($channel['feed_rss_url'])?>"><?php echo _("RSS")?></a>
       (<a href="<?php echo htmlspecialchars($channel['feed_rss_fallback'])?>"><?php echo _("fallback")?></a>)
      </td>
      <td class="linedRow"><?php echo $channel['channel_updated']?></td>
     </tr>
     <?php endforeach?>
    </tbody>
    </table>
    <div id="feeds-empty">
     <?php echo _("No feeds match")?>
    </div>
<?php else:?>
    <div class="text">
     <em><?php echo _("No channels are available.")?></em>
    </div>
<?php endif;?>
