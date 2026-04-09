<table width="100%" cellspacing="1" class="linedRow">
<?php if (!empty($this->error)): ?><tr class="text"><td><em><?php echo $this->error ?></em></td></tr><?php endif ?>
<?php if (!empty($this->stories)): ?>
<?php foreach ($this->stories as $story): ?>
<tr class="text"><td><a target="_blank" href="<?php echo $story['permalink'] ?>"><?php echo $this->story_marker ?> <?php echo $story['title'] ?></a></td></tr>
<?php endforeach ?>
<?php endif ?>

<?php if (!empty($this->form)): ?><tr><td><?php echo $this->form ?></td></tr><?php endif ?></table>
