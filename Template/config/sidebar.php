<li <?= $this->app->checkMenuSelection('RgpdFooterController', '', 'Rgpd') ?>>
    <?= $this->url->link(t('Footer settings'), 'RgpdFooterController', 'show', array('plugin' => 'Rgpd')) ?>
</li>
<li <?= $this->app->checkMenuSelection('RgpdPurgeController', '', 'Rgpd') ?>>
    <?= $this->url->link(t('User purge'), 'RgpdPurgeController', 'show', array('plugin' => 'Rgpd')) ?>
</li>
