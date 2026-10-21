<div class="page-header">
    <h2><?= t('Remove a footer item') ?></h2>
</div>

<div class="confirm">
    <p class="alert alert-info">
        <?= t('Do you really want to remove this item: "%s"?', $this->text->e($item['title'])) ?>
    </p>

    <?= $this->modal->confirmButtons(
        'RgpdFooterController',
        'remove',
        array('plugin' => 'Rgpd', 'item_id' => $item['id'])
    ) ?>
</div>
