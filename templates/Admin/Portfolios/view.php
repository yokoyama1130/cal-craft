<h2 class="mb-4">📄 投稿詳細</h2>

<table class="table table-bordered">
    <tr>
        <th>ID</th>
        <td><?= h($portfolio->id) ?></td>
    </tr>
    <tr>
        <th>タイトル</th>
        <td><?= h($portfolio->title) ?></td>
    </tr>
    <tr>
        <th>本文</th>
        <td><?= $this->Text->autoParagraph(h($portfolio->description)) ?></td>
    </tr>
    <tr>
        <th>公開状態</th>
        <td><?= $portfolio->is_public ? '✅ 公開中' : '❌ 非公開' ?></td>
    </tr>
    <tr>
        <th>投稿者</th>
        <td><?= h($portfolio->user->name ?? '不明') ?></td>
    </tr>
</table>

<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-secondary">← 一覧に戻る</a>
