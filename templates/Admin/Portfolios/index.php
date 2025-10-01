<?php $this->assign('title', 'ポートフォリオ管理') ?>

<div class="card p-3 mb-3">
  <?= $this->Form->create(null, ['type' => 'get', 'class' => 'row g-2 align-items-end']) ?>
    <div class="col-md-6">
      <?= $this->Form->control('q', ['label' => 'キーワード', 'value' => $q['q'] ?? '', 'class' => 'form-control']) ?>
    </div>
    <div class="col-md-3">
      <?= $this->Form->control('owner', ['label' => '投稿者', 'type' => 'select', 'options' => ['' => '—', 'user' => 'ユーザー', 'company' => '企業'], 'value' => $q['owner'] ?? '']) ?>
    </div>
    <div class="col-md-3">
      <?= $this->Form->control('visibility', ['label' => '公開状態', 'type' => 'select', 'options' => ['' => '—', '1' => '公開', '0' => '非公開'], 'value' => $q['visibility'] ?? '']) ?>
    </div>
    <div class="col-12 text-end">
      <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i>検索</button>
    </div>
  <?= $this->Form->end() ?>
</div>

<div class="card p-0">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>ID</th><th>サムネ</th><th>タイトル</th><th>投稿者</th><th>公開</th><th>作成</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($portfolios as $pf) : ?>
        <tr>
          <td>#<?= (int)$pf->id ?></td>
          <td style="width:72px">
            <?php if ($pf->thumbnail) : ?>
              <img src="<?= h($pf->thumbnail) ?>" style="width:64px;height:40px;object-fit:cover;border-radius:6px">
            <?php endif; ?>
          </td>
          <td><?= h($pf->title) ?></td>
          <td>
            <?php if ($pf->company_id) : ?>
              <span class="badge text-bg-secondary me-1">企業</span>
                <?= h($pf->company->name ?? '—') ?>
            <?php else : ?>
              <span class="badge text-bg-info me-1">ユーザー</span>
                <?= h($pf->user->name ?? '—') ?>
            <?php endif; ?>
          </td>
          <td><?= $pf->is_public ? '公開' : '非公開' ?></td>
          <td><?= $pf->created ? $pf->created->i18nFormat('yyyy/MM/dd HH:mm') : '—' ?></td>
          <td class="text-end">
            <?= $this->Html->link('表示', ['prefix' => false, 'controller' => 'Portfolios', 'action' => 'view',$pf->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
            <?= $this->Form->postLink(($pf->is_public ? '非公開に' : '公開に'), ['action' => 'toggle',$pf->id], ['class' => 'btn btn-sm btn-warning ms-1']) ?>
            <?= $this->Form->postLink('削除', ['action' => 'delete',$pf->id], ['confirm' => '削除しますか？', 'class' => 'btn btn-sm btn-danger ms-1']) ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="p-3">
    <?= $this->element('pagination') // いつものページャ要素があれば ?>
  </div>
</div>
