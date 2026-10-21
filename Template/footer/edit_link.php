<div class="page-header">
    <h2><?= t('Edit a link') ?></h2>
</div>

<form action="<?= $this->url->href('RgpdFooterController', 'updateLink', array('plugin' => 'Rgpd', 'item_id' => $item['id'])) ?>" method="post" autocomplete="off">
    <?= $this->form->csrf() ?>

    <?= $this->form->label(t('Name'), 'title') ?>
    <?= $this->form->text('title', $values, $errors, array('required', 'maxlength="255"', 'autofocus')) ?>

    <?= $this->form->label(t('URL'), 'url') ?>
    <?= $this->form->text('url', $values, $errors, array('required')) ?>

    <?= $this->form->label(t('Weight'), 'weight') ?>
    <?= $this->form->select('weight', $weights, $values, $errors, array('required')) ?>

    <?= $this->modal->submitButtons() ?>
</form>
