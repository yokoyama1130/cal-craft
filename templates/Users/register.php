<h2>新規登録</h2>
<?= $this->Form->create($user) ?>
<?= $this->Form->control('name') ?>
<?= $this->Form->control('email') ?>
<?= $this->Form->control('password') ?>
<?= $this->Form->button('登録') ?>
<?= $this->Form->end() ?>
