<h2>ユーザー一覧</h2>
<table class="table table-striped">
  <thead>
    <tr>
      <th>ID</th><th>名前</th><th>メール</th><th>管理者</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($users as $user): ?>
    <tr>
      <td><?= $user->id ?></td>
      <td><?= h($user->name) ?></td>
      <td><?= h($user->email) ?></td>
      <td><?= $user->is_admin ? '✅' : '❌' ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
