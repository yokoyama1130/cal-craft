<!-- templates/Comments/edit.php -->

<h2>コメント編集</h2>

<?= $this->Form->create($comment) ?>
<?= $this->Form->hidden('portfolio_id') ?>
<?= $this->Form->control('content', ['label' => '内容', 'rows' => 4]) ?>
<?= $this->Form->button('更新', ['class' => 'btn btn-primary mt-2']) ?>
<?= $this->Form->end() ?>

<div class="mt-3">
    <?= $this->Html->link('戻る', ['controller' => 'Portfolios', 'action' => 'view', $comment->portfolio_id], ['class' => 'btn btn-secondary']) ?>
</div>
