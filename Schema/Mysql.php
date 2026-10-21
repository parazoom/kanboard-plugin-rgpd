<?php

namespace Kanboard\Plugin\Rgpd\Schema;

use Kanboard\Plugin\Rgpd\Model\PurgeScheduleModel;
use PDO;

const VERSION = 3;

function version_1(PDO $pdo)
{
    $pdo->exec("
        CREATE TABLE rgpd_footer_items (
            id INT NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            type VARCHAR(10) NOT NULL DEFAULT 'url',
            url TEXT,
            file_name VARCHAR(255) DEFAULT '',
            file_path VARCHAR(255) DEFAULT '',
            file_size INT DEFAULT 0,
            weight INT NOT NULL DEFAULT 5,
            date_creation INT NOT NULL DEFAULT 0,
            user_id INT NOT NULL DEFAULT 0,
            PRIMARY KEY(id),
            INDEX rgpd_footer_items_weight_idx (weight)
        ) ENGINE=InnoDB CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE rgpd_purge_logs (
            id INT NOT NULL AUTO_INCREMENT,
            date_run INT NOT NULL DEFAULT 0,
            run_mode VARCHAR(10) NOT NULL DEFAULT 'manual',
            user_id INT NOT NULL DEFAULT 0,
            username VARCHAR(255) DEFAULT '',
            threshold_date INT NOT NULL DEFAULT 0,
            nb_users INT NOT NULL DEFAULT 0,
            PRIMARY KEY(id),
            INDEX rgpd_purge_logs_date_idx (date_run)
        ) ENGINE=InnoDB CHARSET=utf8mb4
    ");
}

function version_2(PDO $pdo)
{
    // The schedule used to be a start date plus a time of day, recomputed at
    // every run. It is now a single wall clock date kept in the application
    // timezone, moved forward after each run.
    $options = array();
    $rs = $pdo->query("SELECT `option`, `value` FROM settings WHERE `option` IN ('rgpd_purge_start_date', 'rgpd_purge_time', 'rgpd_purge_next_run')");

    foreach ($rs->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $options[$row['option']] = $row['value'];
    }

    $start = isset($options['rgpd_purge_start_date']) ? (int) $options['rgpd_purge_start_date'] : 0;
    $time = isset($options['rgpd_purge_time']) ? $options['rgpd_purge_time'] : '';
    $next_run = '';

    if ($start > 0) {
        $next_run = date('Y-m-d', $start).' '.(preg_match(PurgeScheduleModel::TIME_PATTERN, $time) ? $time : '00:00');
    }

    if (isset($options['rgpd_purge_next_run'])) {
        $rq = $pdo->prepare("UPDATE settings SET `value` = ? WHERE `option` = ?");
        $rq->execute(array($next_run, 'rgpd_purge_next_run'));
    } elseif ($next_run !== '') {
        $rq = $pdo->prepare("INSERT INTO settings (`option`, `value`) VALUES (?, ?)");
        $rq->execute(array('rgpd_purge_next_run', $next_run));
    }

    $pdo->exec("DELETE FROM settings WHERE `option` IN ('rgpd_purge_start_date', 'rgpd_purge_time')");
}

function version_3(PDO $pdo)
{
    // Kanboard does not keep the creation date of the accounts: the date the
    // plugin first sees an account stands for it. The existing accounts are
    // dated from this upgrade, so none of them is purged before a full
    // inactivity period has elapsed.
    $pdo->exec("
        CREATE TABLE rgpd_user_first_seen (
            user_id INT NOT NULL,
            date_first_seen INT NOT NULL DEFAULT 0,
            PRIMARY KEY(user_id),
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB CHARSET=utf8mb4
    ");

    $pdo->exec('INSERT INTO rgpd_user_first_seen (user_id, date_first_seen) SELECT id, '.time().' FROM users');
}
