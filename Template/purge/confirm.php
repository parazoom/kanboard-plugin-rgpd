<div class="page-header">
    <h2><?= t('Purge now') ?></h2>
</div>

<div class="confirm">
    <?php if (empty($candidates)): ?>
        <p class="alert"><?= t('No account matches the current threshold.') ?></p>
    <?php else: ?>
        <p class="alert alert-error">
            <?= t('Do you really want to remove these user accounts? This action cannot be undone.') ?>
            <br>
            <?= t('Accounts to remove: %d', count($candidates)) ?>
        </p>
        <p>
            <?= t('Accounts whose last login is before %s are removed.', $this->dt->datetime($threshold_date)) ?>
        </p>

        <?= $this->modal->confirmButtons(
            'RgpdPurgeController',
            'purge',
            array('plugin' => 'Rgpd'),
            t('Purge now')
        ) ?>
    <?php endif ?>
</div>
