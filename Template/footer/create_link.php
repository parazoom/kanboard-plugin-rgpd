<div class="page-header">
    <h2><?= t('Add a link') ?></h2>
</div>

<form action="<?= $this->url->href('RgpdFooterController', 'saveLink', array('plugin' => 'Rgpd')) ?>" method="post" autocomplete="off">
    <?= $this->form->csrf() ?>

    <?= $this->form->label(t('Name'), 'title') ?>
    <?= $this->form->text('title', $values, $errors, array('required', 'maxlength="255"', 'autofocus')) ?>

    <?= $this->form->label(t('URL'), 'url') ?>
    <?= $this->form->text('url', $values, $errors, array('required', 'placeholder="https://example.org/privacy"')) ?>

    <?= $this->form->label(t('Weight'), 'weight') ?>
    <?= $this->form->select('weight', $weights, $values, $errors, array('required')) ?>

    <?= $this->modal->submitButtons() ?>
</form>
