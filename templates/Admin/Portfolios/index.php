<h2 class="mb-4"><i class="bi bi-collection"></i> 投稿一覧（ユーザー選択）</h2>

<div class="mb-4">
  <form method="get" class="row g-2 align-items-center">
    <div class="col-auto">
      <label for="userSelect" class="col-form-label fw-bold">ユーザーを選択:</label>
    </div>
    <div class="col-auto">
      <select id="userSelect" name="user_id" class="form-select" onchange="this.form.submit()">
          <option value="">-- 全ユーザー --</option>
          <?php foreach ($users as $id => $name): ?>
              <option value="<?= $id ?>" <?= $id == $userId ? 'selected' : '' ?>>
                  <?= h($name) ?>
              </option>
          <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<div class="table-responsive">
  <table class="table table-bordered table-hover align-middle text-center shadow-sm">
      <thead class="table-dark">
          <tr>
              <th>ID</th>
              <th>タイトル</th>
              <th>公開</th>
              <th>操作</th>
          </tr>
      </thead>
      <tbody>
          <?php foreach ($portfolios as $p): ?>
              <tr>
                  <td><?= $p->id ?></td>
                  <td class="text-start"><?= h($p->title) ?></td>
                  <td><?= $p->is_public ? '✅ 公開中' : '❌ 非公開' ?></td>
                  <td>
                      <?= $this->Form->postLink(
                          '公開切替',
                          ['action' => 'toggleVisibility', $p->id],
                          ['class' => 'btn btn-sm btn-outline-warning me-2']
                      ) ?>
                      <?= $this->Form->postLink(
                          '削除',
                          ['action' => 'delete', $p->id],
                          [
                              'confirm' => '本当に削除しますか？',
                              'class' => 'btn btn-sm btn-outline-danger'
                          ]
                      ) ?>
                  </td>
              </tr>
          <?php endforeach; ?>
      </tbody>
  </table>
</div>
