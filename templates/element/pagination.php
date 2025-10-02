<?php
/**
 * 共通ページネーション要素（Bootstrap風）
 * PaginatorHelper 前提
 */
?>
<?php if ($this->Paginator->total() > 1) : ?>
<nav aria-label="Pagination">
    <ul class="pagination">
        <li class="page-item"><?= $this->Paginator->first('«', ['escape' => false, 'class' => 'page-link']) ?></li>
        <li class="page-item"><?= $this->Paginator->prev('‹', ['escape' => false, 'class' => 'page-link']) ?></li>
        <?= $this->Paginator->numbers([
            'before' => '<li class="page-item">',
            'after' => '</li>',
            'modulus' => 3,
            'templates' => [
                'number' => '<a class="page-link" href="{{url}}">{{text}}</a>',
                'current' => '<span class="page-link active">{{text}}</span>',
            ],
        ]) ?>
        <li class="page-item"><?= $this->Paginator->next('›', ['escape' => false, 'class' => 'page-link']) ?></li>
        <li class="page-item"><?= $this->Paginator->last('»', ['escape' => false, 'class' => 'page-link']) ?></li>
    </ul>
    <p class="text-muted small mt-2">
        <?= $this->Paginator->counter('{{start}}-{{end}} / {{count}} 件中') ?>
    </p>
</nav>
<?php endif; ?>
