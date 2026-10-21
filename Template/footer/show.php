<div class="page-header">
    <h2><?= t('Footer settings') ?></h2>
    <ul>
        <li>
            <?= $this->modal->medium('plus', t('Add a link'), 'RgpdFooterController', 'createLink', array('plugin' => 'Rgpd')) ?>
        </li>
        <li>
            <?= $this->modal->medium('file-pdf-o', t('Add a PDF document'), 'RgpdFooterController', 'createFile', array('plugin' => 'Rgpd')) ?>
        </li>
    </ul>
</div>

<div class="panel">
    <?= t('The footer is only displayed when at least one item is defined. Items are sorted by weight, from 1 to 10.') ?>
</div>

<?php if (! empty($items)): ?>
    <table class="table-striped table-scrolling">
        <tr>
            <th class="column-25"><?= t('Name') ?></th>
            <th class="column-15"><?= t('Type') ?></th>
            <th><?= t('Target') ?></th>
            <th class="column-10"><?= t('Weight') ?></th>
            <th class="column-15"><?= t('CSS class') ?></th>
            <th class="column-15"><?= t('Actions') ?></th>
        </tr>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><strong><?= $this->text->e($item['title']) ?></strong></td>
                <td><?= $this->rgpd->getTypeLabel($item) ?></td>
                <td class="rgpd-item-target"><?= $this->text->e($this->rgpd->getTarget($item)) ?></td>
                <td><?= (int) $item['weight'] ?></td>
                <td><code><?= $this->rgpd->getCssClass($item) ?></code></td>
                <td>
                    <ul>
                        <?php if ($item['type'] === \Kanboard\Plugin\Rgpd\Model\FooterItemModel::TYPE_FILE): ?>
                            <?= $this->modal->medium('edit', t('Edit'), 'RgpdFooterController', 'editFile', array('plugin' => 'Rgpd', 'item_id' => $item['id'])) ?>
                        <?php else: ?>
                            <?= $this->modal->medium('edit', t('Edit'), 'RgpdFooterController', 'editLink', array('plugin' => 'Rgpd', 'item_id' => $item['id'])) ?>
                        <?php endif ?>
                        <?= t('or') ?>
                        <?= $this->modal->confirm('trash-o', t('Remove'), 'RgpdFooterController', 'confirm', array('plugin' => 'Rgpd', 'item_id' => $item['id'])) ?>
                    </ul>
                </td>
            </tr>
        <?php endforeach ?>
    </table>
<?php else: ?>
    <p class="alert"><?= t('There is no footer item yet.') ?></p>
<?php endif ?>
