<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\ORM\ResultSet $comments
 * @var string|null $q
 */
use Cake\Utility\Text;

$this->assign('title', 'コメント管理');
?>
<div class="card p-3 mb-3">
  <?= $this->Form->create(null, ['type' => 'get', 'class' => 'row g-2 align-items-end']) ?>
    <div class="col-md-6">
      <?= $this->Form->control('q', [
        'label' => 'キーワード（本文）',
        'value' => $q ?? '',
        'class' => 'form-control',
        'placeholder' => '例）修正お願いします',
      ]) ?>
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
          <th style="width:80px;">ID</th>
          <th>本文（抜粋）</th>
          <th style="width:220px;">投稿者</th>
          <th style="width:260px;">対象ポートフォリオ</th>
          <th style="width:160px;">作成</th>
          <th style="width:240px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($comments as $c) : ?>
            <?php
              $isCompany = !empty($c->company_id);
              $authorBadge = $isCompany
                ? '<span class="badge text-bg-secondary me-2">企業</span>'
                : '<span class="badge text-bg-info me-2">ユーザー</span>';
              $authorName = $isCompany
                ? h(isset($c->company) && $c->company ? $c->company->name : '—')
                : h(isset($c->user) && $c->user ? $c->user->name : '—');
              $excerpt = h(Text::truncate((string)$c->content, 120, ['ellipsis' => '…', 'exact' => false]));
            ?>
          <tr>
            <td>#<?= (int)$c->id ?></td>
            <td><?= $excerpt ?></td>
            <td><?= $this->Html->tag('span', '', ['escape' => false]) ?><?= $authorBadge ?><?= $authorName ?></td>
            <td>
              <?php if (isset($c->portfolio) && $c->portfolio) : ?>
                <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($c->portfolio->thumbnail)) : ?>
                      <img 
                          src="<?= h($c->portfolio->thumbnail) ?>" 
                          style="width:56px;height:36px;object-fit:cover;border-radius:6px"
                      />
                    <?php endif; ?>
                  <div>
                    <div class="small text-muted">#<?= (int)$c->portfolio->id ?></div>
                    <div class="fw-semibold"><?= h($c->portfolio->title ?? '—') ?></div>
                  </div>
                </div>
              <?php else : ?>
                —
              <?php endif; ?>
            </td>
            <td><?= $c->created ? $c->created->i18nFormat('yyyy/MM/dd HH:mm') : '—' ?></td>
            <td class="text-end">
              <?php if (isset($c->portfolio_id) && $c->portfolio_id) : ?>
                    <?= $this->Html->link(
                        '表示',
                        ['prefix' => false, 'controller' => 'Portfolios', 'action' => 'view', $c->portfolio_id],
                        ['class' => 'btn btn-sm btn-outline-secondary']
                    ) ?>
              <?php endif; ?>
              <?= $this->Html->link(
                  '編集',
                  ['prefix' => false, 'controller' => 'Comments', 'action' => 'edit', $c->id],
                  ['class' => 'btn btn-sm btn-primary ms-1']
              ) ?>
              <?= $this->Form->postLink('削除', ['action' => 'delete', $c->id], [
                    'confirm' => 'このコメントを削除しますか？',
                    'class' => 'btn btn-sm btn-danger ms-1',
                  ]) ?>
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
