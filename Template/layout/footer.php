<?php $rgpd_footer_items = $this->rgpd->getFooterItems() ?>
<?php if (! empty($rgpd_footer_items)): ?>
    <footer class="rgpd-footer">
        <hr class="rgpd-footer-separator">
        <ul class="rgpd-footer-links">
            <?php foreach ($rgpd_footer_items as $rgpd_footer_item): ?>
                <?php $rgpd_footer_url = $this->rgpd->getEscapedUrl($rgpd_footer_item) ?>
                <?php if ($rgpd_footer_url !== ''): ?>
                    <li>
                        <a href="<?= $rgpd_footer_url ?>"
                           class="<?= $this->rgpd->getCssClass($rgpd_footer_item) ?>"
                           target="_blank"
                           rel="noopener noreferrer"><?= $this->text->e($rgpd_footer_item['title']) ?></a>
                    </li>
                <?php endif ?>
            <?php endforeach ?>
        </ul>
    </footer>
<?php endif ?>
