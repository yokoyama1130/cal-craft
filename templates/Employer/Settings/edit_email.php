<!-- templates/Settings/edit_email.php -->
<div class="settings-container">
  <h2 class="page-title">メールアドレスの変更</h2>
  <div class="card shadow-sm settings-card">
    <div class="card-body">
      <div class="mb-3 text-muted small">
        現在のメール： <strong><?= h($company->auth_email) ?></strong>
      </div>

      <?= $this->Form->create(null, ['url' => ['action' => 'updateEmail'], 'class' => 'needs-validation', 'novalidate' => true]) ?>

        <div class="mb-3">
          <label class="form-label">
            <span class="label-icon">🔒</span> 現在のパスワード
          </label>
          <?= $this->Form->control('current_password', [
              'type' => 'password',
              'label' => false,
              'required' => true,
              'class' => 'form-control form-control-lg',
              'placeholder' => '••••••••',
              'autocomplete' => 'current-password'
          ]) ?>
          <div class="form-text">本人確認のために入力してください。</div>
        </div>

        <div class="mb-4">
          <label class="form-label">
            <span class="label-icon">📧</span> 新しいメールアドレス
          </label>
          <?= $this->Form->control('new_email', [
              'type' => 'email',
              'label' => false,
              'required' => true,
              'class' => 'form-control form-control-lg',
              'placeholder' => 'name@example.com',
              'autocomplete' => 'email'
          ]) ?>
          <div class="form-text">確認リンクを送ります。リンクを開くまで変更は反映されません。</div>
        </div>

        <div class="d-flex gap-2">
          <?= $this->Form->button('確認メールを送る', ['class' => 'btn btn-primary btn-lg']) ?>
          <?= $this->Html->link('戻る', ['action' => 'index'], ['class' => 'btn btn-outline-secondary btn-lg']) ?>
        </div>

      <?= $this->Form->end() ?>
    </div>
  </div>
</div>

<style>
/* ちょいリッチにする軽量CSS（Bootstrapない環境でも最低限映える） */
.settings-container{max-width:720px;margin:0 auto}
.page-title{font-weight:700;letter-spacing:.02em;margin-bottom:16px}
.settings-card{border-radius:16px;border:1px solid #eee}
.label-icon{margin-right:.35rem}
.form-text{color:#6c757d}
.shadow-sm{box-shadow:0 .125rem .25rem rgba(0,0,0,.075)}
/* Bootstrapが無い場合の簡易ボタン見た目 */
.btn{display:inline-block;padding:.6rem 1rem;border-radius:.6rem;border:1px solid #ccc;text-decoration:none}
.btn-lg{padding:.7rem 1.1rem;font-size:1rem}
.btn-primary{background:#0d6efd;border-color:#0d6efd;color:#fff}
.btn-outline-secondary{background:#fff;border-color:#ced4da;color:#495057}
.form-control{width:100%;padding:.7rem .9rem;border:1px solid #ced4da;border-radius:.6rem}
.form-control:focus{outline:none;border-color:#86b7fe;box-shadow:0 0 0 .2rem rgba(13,110,253,.15)}
.form-label{font-weight:600;margin-bottom:.35rem}
.card{background:#fff;border-radius:12px}
.card-body{padding:1.25rem 1.25rem}
.small{font-size:.875rem}
.text-muted{color:#6c757d}
.gap-2{gap:.5rem}
.d-flex{display:flex}
.mb-3{margin-bottom:1rem}
.mb-4{margin-bottom:1.5rem}
</style>

<script>
// フロント側の軽いバリデーション（Bootstrapのスタイル想定）
document.addEventListener('submit', e => {
  const form = e.target.closest('.needs-validation');
  if (!form) return;
  if (!form.checkValidity()) {
    e.preventDefault();
    e.stopPropagation();
  }
  form.classList.add('was-validated');
}, { capture:true });
</script>
