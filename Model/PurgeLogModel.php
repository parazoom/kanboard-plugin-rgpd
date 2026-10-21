<?php

namespace Kanboard\Plugin\Rgpd\Model;

use Kanboard\Core\Base;

/**
 * Purge Log Model
 *
 * Keeps track of every purge. Only the number of removed accounts is
 * recorded: keeping the identity of purged users would defeat the purpose
 * of the purge itself.
 *
 * @package  Rgpd\Model
 */
class PurgeLogModel extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const TABLE = 'rgpd_purge_logs';

    /**
     * How the purge was triggered
     *
     * @var string
     */
    const MODE_MANUAL = 'manual';
    const MODE_AUTOMATIC = 'automatic';

    /**
     * Record a purge
     *
     * @access public
     * @param  string  $run_mode
     * @param  integer $threshold_date
     * @param  integer $nb_users
     * @param  integer $user_id    Author of a manual purge
     * @param  string  $username   Author name, kept as a snapshot
     * @return bool
     */
    public function create($run_mode, $threshold_date, $nb_users, $user_id = 0, $username = '')
    {
        return $this->db->table(self::TABLE)->insert(array(
            'date_run' => time(),
            'run_mode' => $run_mode,
            'user_id' => (int) $user_id,
            'username' => substr($username, 0, 255),
            'threshold_date' => (int) $threshold_date,
            'nb_users' => (int) $nb_users,
        ));
    }

    /**
     * Get the purge history, most recent first
     *
     * @access public
     * @param  integer $limit
     * @return array
     */
    public function getAll($limit = 100)
    {
        return $this->db
            ->table(self::TABLE)
            ->desc('date_run')
            ->desc('id')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Check whether an automatic purge already ran for a given occurrence
     *
     * Guards against a second run when the scheduler fires twice for the
     * same due date, for instance when two cron calls overlap.
     *
     * @access public
     * @param  integer $occurrence  Timestamp of the scheduled run
     * @return bool
     */
    public function hasAutomaticRunSince($occurrence)
    {
        return $this->db
            ->table(self::TABLE)
            ->eq('run_mode', self::MODE_AUTOMATIC)
            ->gte('date_run', $occurrence)
            ->exists();
    }

    /**
     * Get the last purge
     *
     * @access public
     * @return array|null
     */
    public function getLast()
    {
        return $this->db
            ->table(self::TABLE)
            ->desc('date_run')
            ->desc('id')
            ->findOne();
    }
}
