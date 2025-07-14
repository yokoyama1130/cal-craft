<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Portfolio $portfolio
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Portfolio'), ['action' => 'edit', $portfolio->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Portfolio'), ['action' => 'delete', $portfolio->id], ['confirm' => __('Are you sure you want to delete # {0}?', $portfolio->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Portfolios'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Portfolio'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="portfolios view content">
            <h3><?= h($portfolio->title) ?></h3>
            <table>
                <tr>
                    <th><?= __('User') ?></th>
                    <td><?= $portfolio->has('user') ? $this->Html->link($portfolio->user->name, ['controller' => 'Users', 'action' => 'view', $portfolio->user->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Title') ?></th>
                    <td><?= h($portfolio->title) ?></td>
                </tr>
                <tr>
                    <th><?= __('Thumbnail') ?></th>
                    <td><?= h($portfolio->thumbnail) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($portfolio->id) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Description') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($portfolio->description)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
