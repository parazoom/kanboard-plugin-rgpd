<div class="page-header">
    <h2><?= t('Add a PDF document') ?></h2>
</div>

<form action="<?= $this->url->href('RgpdFooterController', 'saveFile', array('plugin' => 'Rgpd')) ?>" method="post" enctype="multipart/form-data" autocomplete="off">
    <?= $this->form->csrf() ?>

    <?= $this->form->label(t('Name'), 'title') ?>
    <?= $this->form->text('title', $values, $errors, array('required', 'maxlength="255"', 'autofocus')) ?>

    <?= $this->form->label(t('PDF document'), 'document') ?>
    <?= $this->form->file('document', $errors) ?>
    <?php if ($max_size > 0): ?>
        <p class="form-help"><?= t('The maximum allowed file size is %sB.', $this->text->bytes($max_size)) ?></p>
    <?php endif ?>

    <?= $this->form->label(t('Weight'), 'weight') ?>
    <?= $this->form->select('weight', $weights, $values, $errors, array('required')) ?>

    <?= $this->modal->submitButtons() ?>
</form>
