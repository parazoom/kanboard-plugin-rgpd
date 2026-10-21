<div class="page-header">
    <h2><?= t('Purge log') ?></h2>
</div>

<?= $this->render('rgpd:purge/tabs', array('active' => 'logs')) ?>

<div class="panel">
    <strong><?= t('Last purge') ?>:</strong>
    <?= empty($last_log) ? t('Never') : $this->dt->datetime($last_log['date_run']) ?>
</div>

<?php if (empty($logs)): ?>
    <p class="alert"><?= t('No purge has been run yet.') ?></p>
<?php else: ?>
    <table class="table-striped table-scrolling">
        <tr>
            <th class="column-25"><?= t('Date') ?></th>
            <th><?= t('Details') ?></th>
            <th class="column-25"><?= t('Threshold') ?></th>
            <th class="column-15"><?= t('Removed accounts') ?></th>
        </tr>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= $this->dt->datetime($log['date_run']) ?></td>
                <td>
                    <?php if ($log['run_mode'] === \Kanboard\Plugin\Rgpd\Model\PurgeLogModel::MODE_MANUAL): ?>
                        <?= t('Run by %s on %s', $this->text->e($log['username']), $this->dt->datetime($log['date_run'])) ?>
                    <?php else: ?>
                        <?= t('Run automatically on %s', $this->dt->datetime($log['date_run'])) ?>
                    <?php endif ?>
                </td>
                <td><?= $this->dt->datetime($log['threshold_date']) ?></td>
                <td><?= (int) $log['nb_users'] ?></td>
            </tr>
        <?php endforeach ?>
    </table>
<?php endif ?>
