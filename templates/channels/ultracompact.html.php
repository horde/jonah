<?php if (!empty($this->error)): ?><em><?php echo $this->error ?></em><?php endif ?>
<?php if (!empty($this->stories)): ?>
<?php foreach ($this->stories as $story): ?>
:: <a target="blank" href="<?php echo $story['permalink'] ?>"><?php echo $story['title'] ?></a>
<?php endforeach ?>
<?php endif ?>
