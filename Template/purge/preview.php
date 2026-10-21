<div class="page-header">
    <h2><?= t('Purge preview') ?></h2>
</div>

<?= $this->render('rgpd:purge/tabs', array('active' => 'preview')) ?>

<div class="panel">
    <h3><?= t('Removed by the "Purge now" button') ?></h3>
    <?php if ($now_threshold > 0): ?>
        <p><?= t('Accounts whose last login is before %s.', $this->dt->datetime($now_threshold)) ?></p>
    <?php endif ?>
    <?= $this->render('rgpd:purge/candidates', array('candidates' => $now_candidates)) ?>
</div>

<div class="panel">
    <h3><?= t('Removed by the next scheduled run') ?></h3>
    <?php if ($next_run > 0): ?>
        <p>
            <?= t('Next run on %s, removing accounts whose last login is before %s.', $this->dt->datetime($next_run), $this->dt->datetime($next_threshold)) ?>
        </p>
        <p class="form-help">
            <?= t('An account listed here is spared if its owner logs in before that date.') ?>
        </p>
        <?= $this->render('rgpd:purge/candidates', array('candidates' => $next_candidates)) ?>
    <?php else: ?>
        <p class="alert"><?= t('Automatic purge disabled') ?></p>
    <?php endif ?>
</div>
