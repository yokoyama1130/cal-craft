<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\ORM\ResultSet $users
 * @var array $q
 */
$this->assign('title', 'ユーザー管理');

$val = function ($arr, $key, $default = '') {
    return $arr[$key] ?? $default;
};
?>
<div class="card p-3 mb-3">
  <?= $this->Form->create(null, ['type' => 'get', 'class' => 'row g-2 align-items-end']) ?>
    <div class="col-lg-6">
      <?= $this->Form->control('q', [
        'label' => 'キーワード（名前/メール）',
        'value' => $val($q, 'q'),
        'class' => 'form-control',
        'placeholder' => '例）山田 太郎 / taro@example.com',
      ]) ?>
    </div>
    <div class="col-lg-3">
      <?= $this->Form->control('active', [
        'label' => '状態',
        'type' => 'select',
        'options' => ['' => '—', '1' => '有効', '0' => '凍結'],
        'value' => $val($q, 'active'),
        'class' => 'form-select',
      ]) ?>
    </div>
    <div class="col-lg-3 text-end">
      <button class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass me-1"></i>検索</button>
    </div>
  <?= $this->Form->end() ?>
</div>

<div class="card p-0">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:80px;">ID</th>
          <th style="width:72px;">アイコン</th>
          <th>名前 / メール</th>
          <th style="width:200px;">権限 / 認証</th>
          <th style="width:170px;">作成</th>
          <th style="width:180px;">状態</th>
          <th style="width:280px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u) : ?>
            <?php
              $isAdmin = (int)$u->is_admin === 1;
              $isActive = empty($u->deleted_at);
              $mailOk = (int)$u->email_verified === 1;
            ?>
          <tr>
            <td>#<?= (int)$u->id ?></td>
            <td>
              <?php if (!empty($u->icon_path)) : ?>
                <div class="avatar-circle">
                  <img src="/img/<?= h($u->icon_path) ?>" alt="<?= h($u->name ?: 'user') ?>">
                </div>
              <?php else : ?>
                <div class="avatar-circle placeholder"><i class="fa-regular fa-user"></i></div>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-semibold"><?= h($u->name ?: '—') ?></div>
              <div class="text-muted small"><?= h($u->email ?: '—') ?></div>
            </td>
            <td>
              <?php if ($isAdmin) : ?>
                <span class="badge text-bg-dark me-1"><i class="fa-solid fa-crown me-1"></i>Admin</span>
              <?php else : ?>
                <span class="badge text-bg-secondary me-1">User</span>
              <?php endif; ?>

              <?php if ($mailOk) : ?>
                <span class="badge text-bg-success"><i class="fa-solid fa-envelope-circle-check me-1"></i>メール認証</span>
              <?php else : ?>
                <span 
                    class="badge text-bg-warning text-dark"
                >
                    <i class="fa-regular fa-circle-question me-1"></i>未認証
                </span>
              <?php endif; ?>
            </td>
            <td><?= $u->created ? $u->created->i18nFormat('yyyy/MM/dd HH:mm') : '—' ?></td>
            <td>
              <?php if ($isActive) : ?>
                <span class="badge text-bg-success">有効</span>
              <?php else : ?>
                <span class="badge text-bg-danger">凍結</span>
              <?php endif; ?>
            </td>
            <td class="text-end text-nowrap">
              <?= $this->Html->link(
                  '表示',
                  ['prefix' => false, 'controller' => 'Users', 'action' => 'profile', $u->id],
                  ['class' => 'btn btn-sm btn-outline-secondary']
              ) ?>
              <?php if ($isActive) : ?>
                    <?= $this->Form->postLink('凍結', ['action' => 'toggle',$u->id], [
                          'confirm' => 'このユーザーを凍結しますか？',
                          'class' => 'btn btn-sm btn-warning ms-1',
                        ]) ?>
              <?php else : ?>
                    <?= $this->Form->postLink('復活', ['action' => 'toggle',$u->id], [
                          'confirm' => 'このユーザーを復活させますか？',
                          'class' => 'btn btn-sm btn-success ms-1',
                        ]) ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="p-3">
    <?= $this->element('pagination') ?>
  </div>
</div>

<?php $this->start('css'); ?>
<style>
.avatar-circle{
  width: 48px;
  height: 48px;          /* ← 追加（fallback） */
  aspect-ratio: 1 / 1;   /* 使える環境ではこれでOK */
  border-radius: 50%;
  overflow: hidden;
  border: 1px solid #eee;
  background: #fff;
  display: grid;
  place-items: center;
  min-width: 48px;       /* テーブル内で潰れない保険 */
}
.avatar-circle img{
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.avatar-circle.placeholder{ color: #9aa3af; }
</style>
<?php $this->end(); ?>