<div class="page-header">
    <h2><?= t('Edit a PDF document') ?></h2>
</div>

<form action="<?= $this->url->href('RgpdFooterController', 'updateFile', array('plugin' => 'Rgpd', 'item_id' => $item['id'])) ?>" method="post" enctype="multipart/form-data" autocomplete="off">
    <?= $this->form->csrf() ?>

    <?= $this->form->label(t('Name'), 'title') ?>
    <?= $this->form->text('title', $values, $errors, array('required', 'maxlength="255"', 'autofocus')) ?>

    <?= $this->form->label(t('Replace the document'), 'document') ?>
    <?= $this->form->file('document', $errors) ?>
    <p class="form-help">
        <?= t('Current document: %s', $this->text->e($item['file_name'])) ?>
        <?php if ($max_size > 0): ?>
            &ndash; <?= t('The maximum allowed file size is %sB.', $this->text->bytes($max_size)) ?>
        <?php endif ?>
    </p>

    <?= $this->form->label(t('Weight'), 'weight') ?>
    <?= $this->form->select('weight', $weights, $values, $errors, array('required')) ?>

    <?= $this->modal->submitButtons() ?>
</form>
