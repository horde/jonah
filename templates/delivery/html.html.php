<?php echo $this->notify ?>

<table width="100%" cellspacing="0">
  <tr>
    <td class="rowEven">
      <form action="<?php echo $this->url ?>" method="get">
        <input type="hidden" name="channel_id" value="<?php echo $this->channel_id ?>" />
        <?php echo $this->session ?>
        <?php echo _("Select a format:") ?>
        <select onchange="form.submit()" name="format">
<?php foreach ($this->options as $option): ?>
          <?php echo $option ?>
<?php endforeach ?>
        </select>
      </form>
    </td>
  </tr>
  <tr>
    <td class="rowOdd">
      <?php echo $this->stories ?>
    </td>
  </tr>
</table>
