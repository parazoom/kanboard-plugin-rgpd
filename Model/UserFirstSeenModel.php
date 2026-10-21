<?php

namespace Kanboard\Plugin\Rgpd\Model;

use Kanboard\Core\Base;
use Kanboard\Model\UserModel;

/**
 * User First Seen Model
 *
 * Kanboard does not keep the creation date of user accounts. The first time
 * the plugin sees an account is recorded instead, so that an account which
 * never logged in is only purged once it has existed for longer than the
 * inactivity threshold. The recorded date is never earlier than the real
 * creation date: an account can be kept longer than expected, never removed
 * too early.
 *
 * @package  Rgpd\Model
 */
class UserFirstSeenModel extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const TABLE = 'rgpd_user_first_seen';

    /**
     * Record the accounts that are not known yet
     *
     * @access public
     * @param  integer $now
     * @return integer  Number of accounts recorded
     */
    public function recordNewUsers($now = null)
    {
        $now = $now === null ? time() : $now;
        $known = array_flip($this->db->table(self::TABLE)->findAllByColumn('user_id'));
        $counter = 0;

        foreach ($this->db->table(UserModel::TABLE)->findAllByColumn('id') as $user_id) {
            if (isset($known[$user_id])) {
                continue;
            }

            $result = $this->db->table(self::TABLE)->insert(array(
                'user_id' => (int) $user_id,
                'date_first_seen' => $now,
            ));

            if ($result) {
                $counter++;
            }
        }

        return $counter;
    }

    /**
     * Get the date every account has been seen for the first time
     *
     * @access public
     * @return array  User id => timestamp
     */
    public function getAll()
    {
        return $this->db->hashtable(self::TABLE)->getAll('user_id', 'date_first_seen');
    }
}
