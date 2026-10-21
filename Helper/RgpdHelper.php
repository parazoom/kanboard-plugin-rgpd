<?php

namespace Kanboard\Plugin\Rgpd\Helper;

use Kanboard\Core\Base;
use Kanboard\Plugin\Rgpd\Model\FooterItemModel;
use Kanboard\Plugin\Rgpd\Plugin;

/**
 * Rgpd Helper
 *
 * @package  Rgpd\Helper
 */
class RgpdHelper extends Base
{
    /**
     * Characters replaced when building a CSS class from a title
     *
     * @var array
     */
    protected $transliterations = array(
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
        'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i',
        'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ö' => 'o', 'ø' => 'o', 'œ' => 'oe', 'ß' => 'ss', 'ù' => 'u', 'ú' => 'u', 'û' => 'u',
        'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y',
    );

    /**
     * Get the footer items to display
     *
     * @access public
     * @return array
     */
    public function getFooterItems()
    {
        return $this->footerItemModel->getAll();
    }

    /**
     * Build the CSS class of a footer link
     *
     * @access public
     * @param  array $item
     * @return string
     */
    public function getCssClass(array $item)
    {
        $slug = $this->getSlug($item['title']);

        return 'link-'.($slug === '' ? $item['id'] : $slug);
    }

    /**
     * Turn a title into a slug usable in a CSS class
     *
     * @access public
     * @param  string $title
     * @return string
     */
    public function getSlug($title)
    {
        $slug = strtr(mb_strtolower($title, 'UTF-8'), $this->transliterations);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim((string) $slug, '-');
    }

    /**
     * Get the file of the template replaced by one of the plugin overrides
     *
     * @access public
     * @param  string $template
     * @return string
     */
    public function getOverriddenTemplateFile($template)
    {
        $chain = $this->container->offsetExists(Plugin::TEMPLATE_CHAIN) ? $this->container[Plugin::TEMPLATE_CHAIN] : array();

        return isset($chain[$template]) ? $chain[$template] : $this->template->getTemplateFile('kanboard:'.$template);
    }

    /**
     * Get the escaped href of a footer item
     *
     * Documents are served by the plugin, external links are used as is
     * once their scheme has been checked again.
     *
     * @access public
     * @param  array $item
     * @return string
     */
    public function getEscapedUrl(array $item)
    {
        if ($item['type'] === FooterItemModel::TYPE_FILE) {
            return $this->helper->url->href('RgpdFooterFileController', 'show', array(
                'plugin' => 'Rgpd',
                'item_id' => $item['id'],
            ));
        }

        if (! $this->footerItemModel->hasWebScheme($item['url'])) {
            return '';
        }

        return $this->helper->text->e($item['url']);
    }

    /**
     * Get the human readable target of an item, for the settings page
     *
     * @access public
     * @param  array $item
     * @return string
     */
    public function getTarget(array $item)
    {
        return $item['type'] === FooterItemModel::TYPE_FILE ? $item['file_name'] : $item['url'];
    }

    /**
     * Get the label of an item type
     *
     * @access public
     * @param  array $item
     * @return string
     */
    public function getTypeLabel(array $item)
    {
        return $item['type'] === FooterItemModel::TYPE_FILE ? t('PDF document') : t('External link');
    }

    /**
     * Format a last login date
     *
     * @access public
     * @param  integer $timestamp
     * @return string
     */
    public function getLastLogin($timestamp)
    {
        return empty($timestamp) ? t('Never connected') : $this->helper->dt->datetime($timestamp);
    }
}
