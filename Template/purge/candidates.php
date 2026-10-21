<?php if (empty($candidates)): ?>
    <p class="alert"><?= t('No account matches the current threshold.') ?></p>
<?php else: ?>
    <table class="table-striped table-scrolling">
        <tr>
            <th class="column-20"><?= t('Username') ?></th>
            <th class="column-25"><?= t('Name') ?></th>
            <th class="column-25"><?= t('Email') ?></th>
            <th class="column-10"><?= t('Role') ?></th>
            <th><?= t('Last login') ?></th>
        </tr>
        <?php foreach ($candidates as $candidate): ?>
            <tr>
                <td><?= $this->text->e($candidate['username']) ?></td>
                <td><?= $this->text->e($candidate['name']) ?></td>
                <td><?= $this->text->e($candidate['email']) ?></td>
                <td><?= $this->text->e($this->user->getRoleName($candidate['role'])) ?></td>
                <td><?= $this->rgpd->getLastLogin($candidate['last_login']) ?></td>
            </tr>
        <?php endforeach ?>
    </table>
    <p><strong><?= t('Accounts concerned: %d', count($candidates)) ?></strong></p>
<?php endif ?>
