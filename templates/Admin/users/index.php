<h2 class="mb-4">👥 ユーザー一覧</h2>

<div class="table-responsive">
  <table class="table table-bordered table-hover align-middle text-center">
    <thead class="table-dark">
      <tr>
        <th scope="col">ID</th>
        <th scope="col">名前</th>
        <th scope="col">メールアドレス</th>
        <th scope="col">管理者</th>
        <th scope="col">詳細</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= $user->id ?></td>
          <td><?= h($user->name) ?></td>
          <td><?= h($user->email) ?></td>
          <td><?= $user->is_admin ? '✅ 管理者' : '❌ 一般' ?></td>
          <td>
            <?= $this->Html->link('詳細', ['controller' => 'Portfolios', 'action' => 'index', '?' => ['user_id' => $user->id]], ['class' => 'btn btn-sm btn-primary']) ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
