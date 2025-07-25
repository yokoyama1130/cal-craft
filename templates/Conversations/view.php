<h2 class="mb-4 text-center fs-5 fw-semibold border-bottom pb-2">
  <?= h($conversation->user1->name === $this->request->getAttribute('identity')->get('name') ? $conversation->user2->name : $conversation->user1->name) ?> さんとのチャット
</h2>

<div class="chat-box p-3 bg-light rounded-4 shadow-sm mb-4" style="height: 65vh; overflow-y: auto;">
  <?php foreach ($messages as $m): ?>
    <?php $isMine = $m->sender_id === $userId; ?>
    <div class="d-flex <?= $isMine ? 'justify-content-end' : 'justify-content-start' ?> mb-3">
      <?php if (!$isMine): ?>
        <!-- 相手のアイコン -->
        <?php if (!empty($m->sender->icon_path)): ?>
          <img src="/img/<?= h($m->sender->icon_path) ?>" class="rounded-circle me-2" style="width: 36px; height: 36px; object-fit: cover;">
        <?php else: ?>
          <i class="fas fa-user-circle fa-2x text-muted me-2"></i>
        <?php endif; ?>
      <?php endif; ?>

      <div class="message-bubble <?= $isMine ? 'bg-primary text-white' : 'bg-white border' ?> px-3 py-2 rounded-4 shadow-sm" style="max-width: 70%;">
        <div class="small fw-bold mb-1 <?= $isMine ? 'text-white-50' : 'text-muted' ?>">
          <?= h($m->sender->name) ?>
        </div>
        <div class="lh-lg"><?= nl2br(h($m->content)) ?></div>
        <div class="text-end small mt-1 <?= $isMine ? 'text-white-50' : 'text-muted' ?>"><?= $m->created->nice() ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- メッセージ送信フォーム -->
<?= $this->Form->create(null, ['url' => ['controller' => 'Messages', 'action' => 'send'], 'class' => 'mt-2']) ?>
<?= $this->Form->hidden('conversation_id', ['value' => $conversation->id]) ?>
<div class="input-group">
  <?= $this->Form->control('content', [
    'label' => false,
    'rows' => 1,
    'class' => 'form-control rounded-start-pill',
    'placeholder' => 'メッセージを入力...',
    'style' => 'resize: none;'
  ]) ?>
  <button class="btn btn-primary rounded-end-pill px-4" type="submit">
    <i class="fas fa-paper-plane"></i>
  </button>
</div>
<?= $this->Form->end() ?>