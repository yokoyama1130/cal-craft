<?php
$me = $this->request->getAttribute('identity')->get('name');
$partner = $conversation->user1->name === $me ? $conversation->user2 : $conversation->user1;
?>
<!-- Header -->
<div class="chat-header d-flex align-items-center gap-2 mb-3">
  <a href="<?= $this->Url->build('/') ?>" class="btn btn-light btn-sm rounded-pill">
    <i class="fa-solid fa-chevron-left"></i>
  </a>
  <div class="d-flex align-items-center gap-2">
    <?php if (!empty($partner->icon_path)): ?>
      <img src="/img/<?= h($partner->icon_path) ?>" class="rounded-circle" style="width:38px;height:38px;object-fit:cover;">
    <?php else: ?>
      <i class="fa-regular fa-user-circle fa-2x text-muted"></i>
    <?php endif; ?>
    <div>
      <div class="fw-semibold"><?= h($partner->name) ?></div>
      <div class="small text-muted">チャット</div>
    </div>
  </div>
</div>

<!-- Messages -->
<div id="chatBox" class="chat-box p-3 rounded-4 shadow-sm mb-3">
  <?php
    $lastDate = null;
    foreach ($messages as $m):
      $isMine = $m->sender_id === $userId;
      $d = $m->created->format('Y-m-d');
      if ($d !== $lastDate):
  ?>
    <div class="day-sep"><span><?= h($m->created->i18nFormat('yyyy/MM/dd (eee)')) ?></span></div>
  <?php $lastDate = $d; endif; ?>
  <div class="msg-row d-flex <?= $isMine ? 'justify-content-end' : 'justify-content-start' ?> mb-2">
    <?php if (!$isMine): ?>
      <?php if (!empty($m->sender->icon_path)): ?>
        <img src="/img/<?= h($m->sender->icon_path) ?>" class="rounded-circle me-2 d-none d-sm-block" style="width:32px;height:32px;object-fit:cover;">
      <?php else: ?>
        <i class="fas fa-user-circle fa-lg text-muted me-2 d-none d-sm-block"></i>
      <?php endif; ?>
    <?php endif; ?>
    <div class="bubble <?= $isMine ? 'mine' : 'theirs' ?>">
      <div class="bubble-meta small <?= $isMine ? 'text-white-50' : 'text-muted' ?>">
        <?= h($m->sender->name) ?>
      </div>
      <div class="bubble-body"><?= nl2br(h($m->content)) ?></div>
      <div class="bubble-time small <?= $isMine ? 'text-white-50' : 'text-muted' ?>">
        <?= $m->created->nice() ?>
      </div>
      <span class="tail"></span>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Composer -->
<?= $this->Form->create(null, ['url' => ['controller' => 'Messages', 'action' => 'send'], 'class' => 'chat-composer']) ?>
  <?= $this->Form->hidden('conversation_id', ['value' => $conversation->id]) ?>
  <div class="composer d-flex align-items-end gap-2">
    <div class="flex-grow-1 position-relative">
      <?= $this->Form->control('content', [
        'label' => false,
        'type' => 'textarea',
        'rows' => 1,
        'class' => 'form-control form-control-lg rounded-4 pr-5',
        'placeholder' => 'メッセージを入力…（Ctrl+Enterで送信）',
        'style' => 'resize:none;overflow:hidden;'
      ]) ?>
      <button class="btn btn-light attach-btn" type="button" title="添付（将来用）">
        <i class="fa-regular fa-face-smile"></i>
      </button>
    </div>
    <button class="btn btn-primary btn-lg rounded-4 px-4" type="submit">
      <i class="fas fa-paper-plane"></i>
    </button>
  </div>
<?= $this->Form->end() ?>

<style>
    .chat-box{
  background:#f8fafc;
  border:1px solid #eef1f5;
  height:65vh; overflow-y:auto;
}
.day-sep{display:flex;justify-content:center;margin:12px 0}
.day-sep>span{
  background:#e9eef6;color:#556; font-size:.78rem;
  padding:.2rem .6rem;border-radius:999px
}
.bubble{
  max-width:72%; padding:.55rem .75rem .4rem;
  border-radius:16px; position:relative;
  box-shadow:0 2px 8px rgba(0,0,0,.06)
}
.bubble .bubble-body{line-height:1.7}
.bubble .bubble-meta{margin-bottom:.15rem;font-weight:600}
.bubble .bubble-time{text-align:right;margin-top:.15rem}
.bubble.mine{
  background:#0d6efd;color:#fff
}
.bubble.mine .bubble-body a{color:#fff;text-decoration:underline}
.bubble.theirs{
  background:#fff;border:1px solid #e7ebf0;color:#26323f
}
.bubble .tail{
  content:""; position:absolute; bottom:-4px; width:12px; height:12px; transform:rotate(45deg);
}
.bubble.mine .tail{ right:10px; background:#0d6efd }
.bubble.theirs .tail{ left:10px; background:#fff; border-right:1px solid #e7ebf0; border-bottom:1px solid #e7ebf0 }
.chat-header{position:sticky;top:0;background:transparent;z-index:1}
.composer .attach-btn{
  position:absolute; right:.5rem; bottom:.5rem; border-radius:10px;
  border:1px solid #e7ebf0
}

/* モバイル最適化 */
@media (max-width:576px){
  .bubble{max-width:86%}
  .chat-box{height:60vh}
}
</style>

<script>
(() => {
  // 自動スクロール
  const box = document.getElementById('chatBox');
  if (box) box.scrollTop = box.scrollHeight;

  // テキストエリア自動伸縮 & Ctrl+Enter送信
  const ta = document.querySelector('.chat-composer textarea');
  if (ta) {
    const fit = () => { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 220) + 'px'; };
    ta.addEventListener('input', fit); fit();
    ta.addEventListener('keydown', e => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        e.target.closest('form').submit();
      }
    });
  }
})();
</script>
