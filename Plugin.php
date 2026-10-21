<?php

namespace Kanboard\Plugin\Rgpd;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Security\Role;
use Kanboard\Core\Translator;
use Kanboard\Plugin\Rgpd\Console\PurgeUsersCommand;

/**
 * Rgpd Plugin
 *
 * @package  Rgpd
 */
class Plugin extends Base
{
    /**
     * Container key holding the templates overridden by this plugin
     *
     * @var string
     */
    const TEMPLATE_CHAIN = 'rgpdTemplateChain';

    /**
     * Initialize the plugin
     *
     * @access public
     */
    public function initialize()
    {
        $this->registerAccessMap();
        $this->registerRoutes();
        $this->registerTemplates();
        $this->registerAssets();
        $this->registerConsoleCommands();

        $this->helper->register('rgpd', '\Kanboard\Plugin\Rgpd\Helper\RgpdHelper');
    }

    /**
     * Load the translations and append the footer to the layout-less views
     *
     * The overrides are declared here rather than in initialize() because
     * this runs once every plugin has been initialized: a plugin replacing
     * the same views keeps its own template, which this one renders.
     *
     * @access public
     */
    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');

        $this->appendFooterTo('auth/index', 'rgpd:auth/index');
        $this->appendFooterTo('board/view_public', 'rgpd:board/view_public');
        $this->appendFooterTo('task/public', 'rgpd:task/public');
    }

    /**
     * Classes registered in the dependency injection container
     *
     * @access public
     * @return array
     */
    public function getClasses()
    {
        return array(
            'Plugin\Rgpd\Model' => array(
                'FooterItemModel',
                'PurgeLogModel',
                'PurgeScheduleModel',
                'UserFirstSeenModel',
                'UserPurgeModel',
            ),
            'Plugin\Rgpd\Validator' => array(
                'FooterItemValidator',
                'PurgeSettingsValidator',
            ),
        );
    }

    /**
     * Restrict the administration pages to application administrators
     *
     * The footer file viewer stays public: those documents are meant to be
     * reachable from the login page and from public boards.
     *
     * @access protected
     */
    protected function registerAccessMap()
    {
        $this->applicationAccessMap->add('RgpdFooterController', '*', Role::APP_ADMIN);
        $this->applicationAccessMap->add('RgpdPurgeController', '*', Role::APP_ADMIN);
        $this->applicationAccessMap->add('RgpdFooterFileController', 'show', Role::APP_PUBLIC);
    }

    /**
     * Declare the plugin routes
     *
     * @access protected
     */
    protected function registerRoutes()
    {
        $this->route->addRoute('/settings/rgpd/footer', 'RgpdFooterController', 'show', 'Rgpd');
        $this->route->addRoute('/settings/rgpd/purge', 'RgpdPurgeController', 'show', 'Rgpd');
        $this->route->addRoute('/settings/rgpd/purge/preview', 'RgpdPurgeController', 'preview', 'Rgpd');
        $this->route->addRoute('/settings/rgpd/purge/logs', 'RgpdPurgeController', 'logs', 'Rgpd');
        $this->route->addRoute('/rgpd/document/:item_id', 'RgpdFooterFileController', 'show', 'Rgpd');
    }

    /**
     * Attach the templates to the core hooks
     *
     * The footer is rendered on every kind of page: the regular application
     * layout, the login form and the public (token based) views, which are
     * rendered without the application layout.
     *
     * @access protected
     */
    protected function registerTemplates()
    {
        $this->template->hook->attach('template:config:sidebar', 'rgpd:config/sidebar');
        $this->template->hook->attach('template:layout:bottom', 'rgpd:layout/footer');
    }

    /**
     * Append the footer to a view rendered without the application layout
     *
     * The template currently in place is remembered so that the override
     * can render it: another plugin overriding the same view keeps working.
     *
     * @access protected
     * @param  string $template
     * @param  string $override
     */
    protected function appendFooterTo($template, $override)
    {
        $chain = $this->container->offsetExists(self::TEMPLATE_CHAIN) ? $this->container[self::TEMPLATE_CHAIN] : array();
        $chain[$template] = $this->template->getTemplateFile($template);

        $this->container[self::TEMPLATE_CHAIN] = $chain;
        $this->template->setTemplateOverride($template, $override);
    }

    /**
     * Register the plugin stylesheet
     *
     * @access protected
     */
    protected function registerAssets()
    {
        $this->hook->on('template:layout:css', array('template' => 'plugins/Rgpd/Asset/css/rgpd.css'));
    }

    /**
     * Register the command line interface
     *
     * @access protected
     */
    protected function registerConsoleCommands()
    {
        $this->container['cli']->add(new PurgeUsersCommand($this->container));
    }

    public function getPluginName()
    {
        return 'Rgpd';
    }

    public function getPluginDescription()
    {
        return t('GDPR toolbox: footer and automatic purge of inactive user accounts');
    }

    public function getPluginAuthor()
    {
        return 'Ludovic FRANCOIS (LINAGORA)';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/parazoom/rgpg_kanboard.git';
    }

    public function getCompatibleVersion()
    {
        return '>=1.2.20';
    }
}
