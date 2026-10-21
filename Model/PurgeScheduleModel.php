<?php

namespace Kanboard\Plugin\Rgpd\Model;

use DateTime;
use DateTimeZone;
use Exception;
use Kanboard\Core\Base;

/**
 * Purge Schedule Model
 *
 * Holds every setting of the user purge, computes the inactivity threshold
 * and keeps the date of the next automatic run.
 *
 * The next run is stored as a wall clock string read in the application
 * timezone, so that the web interface and the command line always agree on
 * the time of day, whatever timezone the connected user is in.
 *
 * @package  Rgpd\Model
 */
class PurgeScheduleModel extends Base
{
    /**
     * Threshold modes
     *
     * DURATION: accounts inactive for longer than a rolling period.
     * DATE: accounts whose last login is older than a fixed date.
     *
     * @var string
     */
    const MODE_DURATION = 'duration';
    const MODE_DATE = 'date';

    /**
     * Storage format of the next automatic run
     *
     * @var string
     */
    const NEXT_RUN_FORMAT = 'Y-m-d H:i';

    /**
     * Accepted time of day of the next automatic run, "HH:MM"
     *
     * @var string
     */
    const TIME_PATTERN = '/^([01][0-9]|2[0-3]):[0-5][0-9]$/';

    /**
     * Settings keys
     *
     * @var string
     */
    const OPTION_MODE = 'rgpd_purge_mode';
    const OPTION_DATE = 'rgpd_purge_date';
    const OPTION_DURATION_VALUE = 'rgpd_purge_duration_value';
    const OPTION_DURATION_UNIT = 'rgpd_purge_duration_unit';
    const OPTION_INCLUDE_NEVER_LOGGED = 'rgpd_purge_include_never_logged';
    const OPTION_AUTO_ENABLED = 'rgpd_purge_auto_enabled';
    const OPTION_FREQUENCY = 'rgpd_purge_frequency';
    const OPTION_NEXT_RUN = 'rgpd_purge_next_run';

    /**
     * Get the periods usable as a frequency or as an inactivity duration
     *
     * @access public
     * @return array
     */
    public function getPeriodList()
    {
        return array(
            'year' => t('Year'),
            'month' => t('Month'),
            'week' => t('Week'),
        );
    }

    /**
     * Get the available threshold modes
     *
     * @access public
     * @return array
     */
    public function getModeList()
    {
        return array(
            self::MODE_DURATION => t('Inactive for longer than'),
            self::MODE_DATE => t('Last login before a fixed date'),
        );
    }

    /**
     * Get all settings with their default value
     *
     * @access public
     * @return array
     */
    public function getSettings()
    {
        return array(
            self::OPTION_MODE => $this->configModel->get(self::OPTION_MODE, self::MODE_DURATION),
            self::OPTION_DATE => (int) $this->configModel->get(self::OPTION_DATE, 0),
            self::OPTION_DURATION_VALUE => (int) $this->configModel->get(self::OPTION_DURATION_VALUE, 3),
            self::OPTION_DURATION_UNIT => $this->configModel->get(self::OPTION_DURATION_UNIT, 'year'),
            self::OPTION_INCLUDE_NEVER_LOGGED => (int) $this->configModel->get(self::OPTION_INCLUDE_NEVER_LOGGED, 0),
            self::OPTION_AUTO_ENABLED => (int) $this->configModel->get(self::OPTION_AUTO_ENABLED, 0),
            self::OPTION_FREQUENCY => $this->configModel->get(self::OPTION_FREQUENCY, 'year'),
            self::OPTION_NEXT_RUN => $this->configModel->get(self::OPTION_NEXT_RUN, ''),
        );
    }

    /**
     * Save the settings
     *
     * @access public
     * @param  array $values
     * @return bool
     */
    public function save(array $values)
    {
        return $this->configModel->save(array(
            self::OPTION_MODE => $values[self::OPTION_MODE],
            self::OPTION_DATE => (int) $values[self::OPTION_DATE],
            self::OPTION_DURATION_VALUE => (int) $values[self::OPTION_DURATION_VALUE],
            self::OPTION_DURATION_UNIT => $values[self::OPTION_DURATION_UNIT],
            self::OPTION_INCLUDE_NEVER_LOGGED => empty($values[self::OPTION_INCLUDE_NEVER_LOGGED]) ? 0 : 1,
            self::OPTION_AUTO_ENABLED => empty($values[self::OPTION_AUTO_ENABLED]) ? 0 : 1,
            self::OPTION_FREQUENCY => $values[self::OPTION_FREQUENCY],
            self::OPTION_NEXT_RUN => $values[self::OPTION_NEXT_RUN],
        ));
    }

    /**
     * Get the date before which a last login makes an account purgeable
     *
     * @access public
     * @param  integer $reference  Moment the purge runs at, now by default
     * @param  array   $settings
     * @return integer
     */
    public function getThresholdDate($reference = null, array $settings = array())
    {
        $settings = empty($settings) ? $this->getSettings() : $settings;
        $reference = $reference === null ? time() : $reference;

        if ($settings[self::OPTION_MODE] === self::MODE_DATE) {
            return $settings[self::OPTION_DATE];
        }

        $value = max(1, $settings[self::OPTION_DURATION_VALUE]);
        $threshold = strtotime('-'.$value.' '.$settings[self::OPTION_DURATION_UNIT], $reference);

        return $threshold === false ? 0 : $threshold;
    }

    /**
     * Get the timezone the schedule is expressed in
     *
     * The application timezone is used rather than the one of the connected
     * user: the command line has no user, and both must agree.
     *
     * @access public
     * @return string
     */
    public function getScheduleTimezone()
    {
        return $this->configModel->get('application_timezone', 'UTC');
    }

    /**
     * Get the next automatic run as a "Y-m-d H:i" string
     *
     * @access public
     * @return string  Empty when the automatic purge is not scheduled
     */
    public function getNextRun()
    {
        $settings = $this->getSettings();

        if (empty($settings[self::OPTION_AUTO_ENABLED])) {
            return '';
        }

        return $settings[self::OPTION_NEXT_RUN];
    }

    /**
     * Get the next automatic run as a timestamp
     *
     * @access public
     * @return integer  Zero when the automatic purge is not scheduled
     */
    public function getNextRunTimestamp()
    {
        return $this->toTimestamp($this->getNextRun());
    }

    /**
     * Check whether the next automatic run is due
     *
     * @access public
     * @param  integer $now
     * @return bool
     */
    public function isDue($now = null)
    {
        $now = $now === null ? time() : $now;
        $next_run = $this->getNextRunTimestamp();

        return $next_run > 0 && $next_run <= $now;
    }

    /**
     * Move the next automatic run to the first occurrence after the given time
     *
     * Several periods are added at once when the scheduler has been down for
     * a while, so that a single missed window never triggers a burst of runs.
     *
     * @access public
     * @param  integer $now
     * @return string  The new next run
     */
    public function advanceNextRun($now = null)
    {
        $now = $now === null ? time() : $now;
        $settings = $this->getSettings();
        $next_run = $this->addPeriods($settings[self::OPTION_NEXT_RUN], $settings[self::OPTION_FREQUENCY], $now);

        $this->configModel->save(array(self::OPTION_NEXT_RUN => $next_run));

        return $next_run;
    }

    /**
     * Add periods to a run date until it is strictly after the given time
     *
     * @access public
     * @param  string  $next_run   "Y-m-d H:i"
     * @param  string  $period     week, month or year
     * @param  integer $now
     * @return string  The new run date, unchanged when it cannot be computed
     */
    public function addPeriods($next_run, $period, $now)
    {
        $date = $this->toDateTime($next_run);

        if ($date === null || ! array_key_exists($period, $this->getPeriodList())) {
            return $next_run;
        }

        // A year of weekly periods is enough to catch up on any sane outage
        for ($i = 0; $i < 60 && $date->getTimestamp() <= $now; $i++) {
            $date->modify('+1 '.$period);
        }

        return $date->format(self::NEXT_RUN_FORMAT);
    }

    /**
     * Build a run date from a date and a time of day
     *
     * @access public
     * @param  integer $date  Timestamp of the day
     * @param  string  $time  "HH:MM"
     * @return string  "Y-m-d H:i", empty when the date is missing
     */
    public function buildNextRun($date, $time)
    {
        if (empty($date)) {
            return '';
        }

        return date('Y-m-d', $date).' '.(preg_match(self::TIME_PATTERN, $time) ? $time : '00:00');
    }

    /**
     * Split a run date into its day part and its time part
     *
     * @access public
     * @param  string $next_run
     * @return array  [timestamp of the day, "HH:MM"]
     */
    public function splitNextRun($next_run)
    {
        if (! preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})$/', (string) $next_run, $matches)) {
            return array(0, '');
        }

        // Noon rather than midnight: the day stays the same whatever the
        // timezone the date is later formatted in.
        $day = strtotime($matches[1].' 12:00');

        return array($day === false ? 0 : $day, $matches[2]);
    }

    /**
     * Convert a run date into a timestamp
     *
     * @access public
     * @param  string $next_run
     * @return integer  Zero when the date is missing or malformed
     */
    public function toTimestamp($next_run)
    {
        $date = $this->toDateTime($next_run);

        return $date === null ? 0 : $date->getTimestamp();
    }

    /**
     * Read a run date in the schedule timezone
     *
     * @access protected
     * @param  string $next_run
     * @return DateTime|null
     */
    protected function toDateTime($next_run)
    {
        if (empty($next_run)) {
            return null;
        }

        try {
            $date = DateTime::createFromFormat(
                self::NEXT_RUN_FORMAT,
                $next_run,
                new DateTimeZone($this->getScheduleTimezone())
            );
        } catch (Exception $e) {
            $this->logger->error(__METHOD__.': '.$e->getMessage());
            return null;
        }

        if ($date === false) {
            return null;
        }

        $date->setTime((int) $date->format('H'), (int) $date->format('i'), 0);

        return $date;
    }
}
