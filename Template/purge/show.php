<div class="page-header">
    <h2><?= t('User purge') ?></h2>
</div>

<?= $this->render('rgpd:purge/tabs', array('active' => 'show')) ?>

<div class="panel">
    <p class="alert alert-info">
        <?= t('Accounts with the administrator role and your own account are never removed.') ?>
    </p>

    <ul class="no-bullet">
        <li>
            <strong><?= t('Accounts currently matching the threshold') ?>:</strong>
            <?= (int) $nb_candidates ?>
            <?php if ($threshold_date > 0): ?>
                (<?= t('last login before %s', $this->dt->datetime($threshold_date)) ?>)
            <?php endif ?>
        </li>
        <li>
            <strong><?= t('Next scheduled purge') ?>:</strong>
            <?= $next_run > 0 ? $this->dt->datetime($next_run) : t('Automatic purge disabled') ?>
        </li>
        <li>
            <strong><?= t('Last purge') ?>:</strong>
            <?php if (empty($last_log)): ?>
                <?= t('Never') ?>
            <?php else: ?>
                <?= $this->dt->datetime($last_log['date_run']) ?>
                (<?= t('removed accounts: %d', $last_log['nb_users']) ?>)
            <?php endif ?>
        </li>
    </ul>

    <div class="form-actions rgpd-purge-actions">
        <?= $this->modal->mediumButton('trash-o', t('Purge now'), 'RgpdPurgeController', 'confirm', array('plugin' => 'Rgpd')) ?>
    </div>
</div>

<form action="<?= $this->url->href('RgpdPurgeController', 'save', array('plugin' => 'Rgpd')) ?>" method="post" autocomplete="off">
    <?= $this->form->csrf() ?>

    <fieldset>
        <legend><?= t('Inactivity threshold') ?></legend>

        <?= $this->form->radios('rgpd_purge_mode', $modes, $values) ?>

        <?= $this->form->label(t('Inactivity duration'), 'rgpd_purge_duration_value') ?>
        <?= $this->form->number('rgpd_purge_duration_value', $values, $errors, array('min="1"', 'max="99"')) ?>
        <?= $this->form->select('rgpd_purge_duration_unit', $periods, $values, $errors) ?>
        <p class="form-help"><?= t('Used when the threshold is a rolling duration. The date is recomputed at every purge.') ?></p>

        <?= $this->form->date(t('Last login before'), 'rgpd_purge_date', $values, $errors) ?>
        <p class="form-help"><?= t('Used when the threshold is a fixed date.') ?></p>

        <?= $this->form->checkbox('rgpd_purge_include_never_logged', t('Also remove accounts that never logged in'), 1, ! empty($values['rgpd_purge_include_never_logged'])) ?>
        <p class="form-help"><?= t('Kanboard does not record when an account is created: such an account is only removed once the plugin has known it for longer than the threshold.') ?></p>
    </fieldset>

    <fieldset>
        <legend><?= t('Automatic purge') ?></legend>

        <?= $this->form->checkbox('rgpd_purge_auto_enabled', t('Enable the automatic purge'), 1, ! empty($values['rgpd_purge_auto_enabled'])) ?>

        <?= $this->form->date(t('Next automatic purge'), 'rgpd_purge_next_run_date', $values, $errors) ?>
        <?= $this->form->select('rgpd_purge_next_run_time', $hours, $values, $errors) ?>

        <?= $this->form->label(t('Frequency'), 'rgpd_purge_frequency') ?>
        <?= $this->form->select('rgpd_purge_frequency', $periods, $values, $errors) ?>
        <p class="form-help"><?= t('After each run, the date above moves forward by one period.') ?></p>

        <p class="form-help">
            <?= t('The date above is checked by a command that your system scheduler must call. Nothing happens until the date is passed, and the purge never runs twice for the same date, so the command is safe to call often: every 15 minutes keeps the chosen time accurate.') ?>
        </p>
        <p class="form-help">
            <code>*/15 * * * * cd <?= $this->text->e($root_dir) ?> &amp;&amp; ./cli rgpd:purge</code>
        </p>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn btn-blue"><?= t('Save') ?></button>
    </div>
</form>
