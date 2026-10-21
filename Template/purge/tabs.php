<?php
/**
 * Tab bar shared by the three purge pages.
 *
 * Reuses the core view switcher component. The active tab is given by the
 * page rather than deduced from the route, so that it stays correct when a
 * page is rendered again after a validation error.
 */
?>
<ul class="views rgpd-tabs">
    <li<?= $active === 'show' ? ' class="active"' : '' ?>>
        <?= $this->url->icon('cogs', t('User purge'), 'RgpdPurgeController', 'show', array('plugin' => 'Rgpd')) ?>
    </li>
    <li<?= $active === 'preview' ? ' class="active"' : '' ?>>
        <?= $this->url->icon('users', t('Purge preview'), 'RgpdPurgeController', 'preview', array('plugin' => 'Rgpd')) ?>
    </li>
    <li<?= $active === 'logs' ? ' class="active"' : '' ?>>
        <?= $this->url->icon('history', t('Purge log'), 'RgpdPurgeController', 'logs', array('plugin' => 'Rgpd')) ?>
    </li>
</ul>
