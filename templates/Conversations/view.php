<h2><?= h($conversation->user1->name === $this->request->getAttribute('identity')->get('name') ? $conversation->user2->name : $conversation->user1->name) ?>さんとの会話</h2>

<div class="chat-box mb-4" style="max-height: 400px; overflow-y: auto;">
    <?php foreach ($messages as $m): ?>
        <?php $isMine = $m->sender_id === $userId; ?>
        <div class="d-flex <?= $isMine ? 'justify-content-end' : 'justify-content-start' ?> mb-2">
            <div style="
                max-width: 70%;
                background-color: <?= $isMine ? '#60a9ff' : '#f1f1f1' ?>;
                color: <?= $isMine ? '#fff' : '#000' ?>;
                border-radius: 12px;
                padding: 10px;
            ">
                <div class="small fw-bold"><?= h($m->sender->name) ?></div>
                <div><?= nl2br(h($m->content)) ?></div>
                <div class="text-muted small text-end"><?= $m->created->nice() ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<h4>メッセージを送る</h4>
<?= $this->Form->create(null, ['url' => ['controller' => 'Messages', 'action' => 'send']]) ?>
<?= $this->Form->hidden('conversation_id', ['value' => $conversation->id]) ?>
<div class="input-group">
    <?= $this->Form->control('content', [
        'label' => false,
        'rows' => 2,
        'class' => 'form-control',
        'placeholder' => 'メッセージを入力...'
    ]) ?>
    <div class="input-group-append">
        <?= $this->Form->button('送信', ['class' => 'btn btn-primary']) ?>
    </div>
</div>
<?= $this->Form->end() ?>