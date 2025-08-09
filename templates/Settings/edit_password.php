<!-- templates/Settings/edit_password.php -->
<div class="settings-container">
  <h2 class="page-title">パスワードの変更</h2>

  <div class="card shadow-sm settings-card">
    <div class="card-body">
      <?= $this->Form->create(null, [
        'url' => ['action' => 'updatePassword'],
        'class' => 'needs-validation',
        'novalidate' => true
      ]) ?>

        <div class="mb-3">
          <label class="form-label">
            <span class="label-icon">🔒</span> 現在のパスワード
          </label>
          <div class="password-field">
            <?= $this->Form->control('current_password', [
              'type' => 'password',
              'label' => false,
              'required' => true,
              'class' => 'form-control form-control-lg',
              'placeholder' => '現在のパスワード',
              'autocomplete' => 'current-password'
            ]) ?>
            <button type="button" class="btn-eye" data-toggle="pw">表示</button>
          </div>
          <div class="form-text">本人確認のため入力してください。</div>
        </div>

        <div class="mb-3">
          <label class="form-label">
            <span class="label-icon">🆕</span> 新しいパスワード
          </label>
          <div class="password-field">
            <?= $this->Form->control('new_password', [
              'type' => 'password',
              'label' => false,
              'required' => true,
              'class' => 'form-control form-control-lg',
              'placeholder' => '新しいパスワード',
              'autocomplete' => 'new-password',
              'minlength' => 8
            ]) ?>
            <button type="button" class="btn-eye" data-toggle="pw">表示</button>
          </div>
          <div class="form-text">8文字以上で、英数や記号を組み合わせると安全です。</div>
        </div>

        <div class="mb-4">
          <label class="form-label">
            <span class="label-icon">✅</span> 新しいパスワード（確認）
          </label>
          <div class="password-field">
            <?= $this->Form->control('new_password_confirm', [
              'type' => 'password',
              'label' => false,
              'required' => true,
              'class' => 'form-control form-control-lg',
              'placeholder' => 'もう一度入力',
              'autocomplete' => 'new-password'
            ]) ?>
            <button type="button" class="btn-eye" data-toggle="pw">表示</button>
          </div>
          <div class="form-text">上と同じパスワードを入力してください。</div>
        </div>

        <div class="d-flex gap-2">
          <?= $this->Form->button('変更する', ['class' => 'btn btn-primary btn-lg']) ?>
          <?= $this->Html->link('戻る', ['action' => 'index'], ['class' => 'btn btn-outline-secondary btn-lg']) ?>
        </div>

      <?= $this->Form->end() ?>
    </div>
  </div>
</div>

<style>
/* 共通レイアウト（Bootstrap無くても映える軽量CSS） */
.settings-container{max-width:720px;margin:0 auto}
.page-title{font-weight:700;letter-spacing:.02em;margin-bottom:16px}
.settings-card{border-radius:16px;border:1px solid #eee}
.shadow-sm{box-shadow:0 .125rem .25rem rgba(0,0,0,.075)}
.form-label{font-weight:600;margin-bottom:.35rem}
.form-text{color:#6c757d}
.small{font-size:.875rem}

.card{background:#fff;border-radius:12px}
.card-body{padding:1.25rem}

.d-flex{display:flex} .gap-2{gap:.5rem}
.mb-3{margin-bottom:1rem} .mb-4{margin-bottom:1.5rem}

/* フォームとボタンの最低限スタイル（Bootstrap相当） */
.form-control{width:100%;padding:.7rem .9rem;border:1px solid #ced4da;border-radius:.6rem}
.form-control:focus{outline:none;border-color:#86b7fe;box-shadow:0 0 0 .2rem rgba(13,110,253,.15)}
.btn{display:inline-block;padding:.6rem 1rem;border-radius:.6rem;border:1px solid #ccc;text-decoration:none;cursor:pointer}
.btn-lg{padding:.7rem 1.1rem;font-size:1rem}
.btn-primary{background:#0d6efd;border-color:#0d6efd;color:#fff}
.btn-outline-secondary{background:#fff;border-color:#ced4da;color:#495057}

/* パスワード表示切替ボタン */
.password-field{position:relative}
.btn-eye{
  position:absolute; right:.5rem; top:50%; transform:translateY(-50%);
  background:#f8f9fa; border:1px solid #ced4da; color:#495057;
  padding:.35rem .6rem; border-radius:.5rem; font-size:.9rem;
}
.btn-eye:focus{outline:none}
</style>

<script>
// 簡易バリデーション（未入力なら送信止める）
document.addEventListener('submit', e => {
  const form = e.target.closest('.needs-validation');
  if (!form) return;
  if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
  form.classList.add('was-validated');
}, { capture:true });

// 目アイコン：パスワード表示/非表示切替
document.addEventListener('click', e => {
  const btn = e.target.closest('.btn-eye');
  if (!btn) return;
  const input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
  if (!input) return;
  const showing = input.type === 'text';
  input.type = showing ? 'password' : 'text';
  btn.textContent = showing ? '表示' : '非表示';
});
</script>
