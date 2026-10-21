<?php

namespace Kanboard\Plugin\Rgpd\Model;

use Kanboard\Core\Base;
use Kanboard\Core\Security\Role;
use Kanboard\Model\LastLoginModel;
use Kanboard\Model\UserModel;

/**
 * User Purge Model
 *
 * Finds and removes the user accounts whose last login is older than a
 * given date. Administrators are never removed.
 *
 * An account that never logged in is judged on the date the plugin first saw
 * it, so that a freshly created account is not removed before its owner had
 * a chance to log in.
 *
 * @package  Rgpd\Model
 */
class UserPurgeModel extends Base
{
    /**
     * Get the accounts that would be removed for a given threshold
     *
     * A threshold later than the moment of the purge is refused: it would
     * select every account, including the ones in use.
     *
     * @access public
     * @param  integer $threshold_date        Accounts with an older last login are selected
     * @param  bool    $include_never_logged  Also select accounts that never logged in
     * @param  integer $reference             Moment the purge runs at, now by default
     * @return array
     */
    public function getCandidates($threshold_date, $include_never_logged = false, $reference = null)
    {
        $reference = $reference === null ? time() : $reference;

        if ($threshold_date <= 0 || $threshold_date > $reference) {
            return array();
        }

        $this->userFirstSeenModel->recordNewUsers();

        $last_logins = $this->getLastLogins();
        $first_seen = $this->userFirstSeenModel->getAll();
        $current_user_id = $this->userSession->getId();
        $candidates = array();

        foreach ($this->getPurgeableUsers() as $user) {
            if ((int) $user['id'] === (int) $current_user_id) {
                continue;
            }

            $last_login = isset($last_logins[$user['id']]) ? (int) $last_logins[$user['id']] : 0;

            if ($last_login === 0) {
                if (! $include_never_logged) {
                    continue;
                }

                if (! isset($first_seen[$user['id']]) || (int) $first_seen[$user['id']] >= $threshold_date) {
                    continue;
                }
            } elseif ($last_login >= $threshold_date) {
                continue;
            }

            $user['last_login'] = $last_login;
            $candidates[] = $user;
        }

        usort($candidates, function (array $a, array $b) {
            if ($a['last_login'] === $b['last_login']) {
                return strcasecmp($a['username'], $b['username']);
            }

            return $a['last_login'] < $b['last_login'] ? -1 : 1;
        });

        return $candidates;
    }

    /**
     * Remove the accounts matching the given threshold
     *
     * @access public
     * @param  integer $threshold_date
     * @param  bool    $include_never_logged
     * @return integer  Number of removed accounts
     */
    public function purge($threshold_date, $include_never_logged = false)
    {
        $counter = 0;

        foreach ($this->getCandidates($threshold_date, $include_never_logged) as $user) {
            if ($this->userModel->remove($user['id'])) {
                $counter++;
            } else {
                $this->logger->error(__METHOD__.': unable to remove the user #'.$user['id']);
            }
        }

        return $counter;
    }

    /**
     * Get every account eligible to a purge
     *
     * Administrators are excluded: they must be downgraded first.
     *
     * @access protected
     * @return array
     */
    protected function getPurgeableUsers()
    {
        return $this->db
            ->table(UserModel::TABLE)
            ->columns('id', 'username', 'name', 'email', 'role', 'is_active')
            ->neq('role', Role::APP_ADMIN)
            ->asc('username')
            ->findAll();
    }

    /**
     * Get the most recent login of every user
     *
     * @access protected
     * @return array  User id => timestamp
     */
    protected function getLastLogins()
    {
        $logins = $this->db
            ->table(LastLoginModel::TABLE)
            ->columns('user_id', 'MAX(date_creation) AS last_login')
            ->groupBy('user_id')
            ->findAll();

        return array_column($logins, 'last_login', 'user_id');
    }
}
