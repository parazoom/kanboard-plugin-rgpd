<?php

namespace Kanboard\Plugin\Rgpd\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Plugin\Rgpd\Model\PurgeLogModel;
use Kanboard\Plugin\Rgpd\Model\PurgeScheduleModel;

/**
 * User purge administration
 *
 * @package  Rgpd\Controller
 */
class RgpdPurgeController extends BaseController
{
    /**
     * Form fields holding the two halves of the next automatic run
     *
     * @var string
     */
    const FIELD_NEXT_RUN_DATE = 'rgpd_purge_next_run_date';
    const FIELD_NEXT_RUN_TIME = 'rgpd_purge_next_run_time';

    /**
     * Display the purge settings
     *
     * @access public
     * @param  array $values
     * @param  array $errors
     */
    public function show(array $values = array(), array $errors = array())
    {
        $settings = $this->purgeScheduleModel->getSettings();

        $this->response->html($this->helper->layout->config('rgpd:purge/show', array(
            'values' => empty($values) ? $this->getFormValues($settings) : $values,
            'errors' => $errors,
            'settings' => $settings,
            'threshold_date' => $this->purgeScheduleModel->getThresholdDate(),
            'next_run' => $this->purgeScheduleModel->getNextRunTimestamp(),
            'last_log' => $this->purgeLogModel->getLast(),
            'nb_candidates' => count($this->getCandidates($this->purgeScheduleModel->getThresholdDate(), $settings)),
            'modes' => $this->purgeScheduleModel->getModeList(),
            'periods' => $this->purgeScheduleModel->getPeriodList(),
            'hours' => $this->helper->dt->getDayHours(),
            'root_dir' => realpath(ROOT_DIR) ?: ROOT_DIR,
            'title' => t('Settings').' &gt; '.t('User purge'),
        )));
    }

    /**
     * Save the purge settings
     *
     * @access public
     */
    public function save()
    {
        $values = $this->request->getValues() + array(
            PurgeScheduleModel::OPTION_INCLUDE_NEVER_LOGGED => 0,
            PurgeScheduleModel::OPTION_AUTO_ENABLED => 0,
            self::FIELD_NEXT_RUN_DATE => '',
            self::FIELD_NEXT_RUN_TIME => '',
        );

        $values = $this->dateParser->convert($values, array(
            PurgeScheduleModel::OPTION_DATE,
            self::FIELD_NEXT_RUN_DATE,
        ));

        $values[PurgeScheduleModel::OPTION_NEXT_RUN] = $this->purgeScheduleModel->buildNextRun(
            $values[self::FIELD_NEXT_RUN_DATE],
            $values[self::FIELD_NEXT_RUN_TIME]
        );

        list($valid, $errors) = $this->purgeSettingsValidator->validate($values);

        if (! $valid) {
            return $this->show($values, $errors);
        }

        if ($this->purgeScheduleModel->save($values)) {
            $this->flash->success(t('Settings saved successfully.'));
        } else {
            $this->flash->failure(t('Unable to save your settings.'));
        }

        $this->response->redirect($this->helper->url->to('RgpdPurgeController', 'show', array('plugin' => 'Rgpd')));
    }

    /**
     * Confirmation dialog before an immediate purge
     *
     * @access public
     */
    public function confirm()
    {
        $settings = $this->purgeScheduleModel->getSettings();
        $threshold_date = $this->purgeScheduleModel->getThresholdDate();

        $this->response->html($this->template->render('rgpd:purge/confirm', array(
            'threshold_date' => $threshold_date,
            'candidates' => $this->getCandidates($threshold_date, $settings),
        )));
    }

    /**
     * Run an immediate purge
     *
     * @access public
     */
    public function purge()
    {
        $this->checkCSRFParam();

        $settings = $this->purgeScheduleModel->getSettings();
        $threshold_date = $this->purgeScheduleModel->getThresholdDate();

        if ($threshold_date <= 0) {
            $this->flash->failure(t('The purge threshold is not configured.'));
            return $this->response->redirect($this->helper->url->to('RgpdPurgeController', 'show', array('plugin' => 'Rgpd')));
        }

        if ($threshold_date > time()) {
            $this->flash->failure(t('The purge threshold is in the future.'));
            return $this->response->redirect($this->helper->url->to('RgpdPurgeController', 'show', array('plugin' => 'Rgpd')));
        }

        $nb_users = $this->userPurgeModel->purge(
            $threshold_date,
            ! empty($settings[PurgeScheduleModel::OPTION_INCLUDE_NEVER_LOGGED])
        );

        $user = $this->userSession->getAll();

        $this->purgeLogModel->create(
            PurgeLogModel::MODE_MANUAL,
            $threshold_date,
            $nb_users,
            $this->userSession->getId(),
            empty($user['name']) ? $this->userSession->getUsername() : $user['name']
        );

        $this->flash->success(t('Removed user accounts: %d.', $nb_users));

        $this->response->redirect($this->helper->url->to('RgpdPurgeController', 'logs', array('plugin' => 'Rgpd')));
    }

    /**
     * Display the accounts that are about to be removed
     *
     * Two lists are shown: the one the button would remove right now, and
     * the one the next scheduled run would remove. They differ because the
     * threshold of a rolling period moves with time.
     *
     * @access public
     */
    public function preview()
    {
        $settings = $this->purgeScheduleModel->getSettings();
        $next_run = $this->purgeScheduleModel->getNextRunTimestamp();

        $now_threshold = $this->purgeScheduleModel->getThresholdDate(null, $settings);
        $next_threshold = $next_run > 0 ? $this->purgeScheduleModel->getThresholdDate($next_run, $settings) : 0;

        $this->response->html($this->helper->layout->config('rgpd:purge/preview', array(
            'now_threshold' => $now_threshold,
            'now_candidates' => $this->getCandidates($now_threshold, $settings),
            'next_run' => $next_run,
            'next_threshold' => $next_threshold,
            'next_candidates' => $this->getCandidates($next_threshold, $settings, $next_run),
            'title' => t('Settings').' &gt; '.t('Purge preview'),
        )));
    }

    /**
     * Display the purge history
     *
     * @access public
     */
    public function logs()
    {
        $this->response->html($this->helper->layout->config('rgpd:purge/logs', array(
            'logs' => $this->purgeLogModel->getAll(),
            'last_log' => $this->purgeLogModel->getLast(),
            'title' => t('Settings').' &gt; '.t('Purge log'),
        )));
    }

    /**
     * Expand the settings into the form fields
     *
     * The next run is stored as a single wall clock string, the form splits
     * it into a date and a time of day.
     *
     * @access protected
     * @param  array $settings
     * @return array
     */
    protected function getFormValues(array $settings)
    {
        list($day, $time) = $this->purgeScheduleModel->splitNextRun($settings[PurgeScheduleModel::OPTION_NEXT_RUN]);

        return $settings + array(
            self::FIELD_NEXT_RUN_DATE => $day > 0 ? date($this->dateParser->getUserDateFormat(), $day) : '',
            self::FIELD_NEXT_RUN_TIME => $time === '' ? '03:00' : $time,
        );
    }

    /**
     * Get the accounts matching a threshold
     *
     * @access protected
     * @param  integer $threshold_date
     * @param  array   $settings
     * @param  integer $reference  Moment the purge runs at, now by default
     * @return array
     */
    protected function getCandidates($threshold_date, array $settings, $reference = null)
    {
        return $this->userPurgeModel->getCandidates(
            $threshold_date,
            ! empty($settings[PurgeScheduleModel::OPTION_INCLUDE_NEVER_LOGGED]),
            $reference
        );
    }
}
