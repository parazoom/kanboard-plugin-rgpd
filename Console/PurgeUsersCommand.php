<?php

namespace Kanboard\Plugin\Rgpd\Console;

use Kanboard\Console\BaseCommand;
use Kanboard\Plugin\Rgpd\Model\PurgeLogModel;
use Kanboard\Plugin\Rgpd\Model\PurgeScheduleModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Run the purge of inactive user accounts
 *
 * Meant to be called regularly by the system scheduler, for instance every
 * fifteen minutes: "cd /path/to/kanboard && ./cli rgpd:purge".
 *
 * The command does nothing until the scheduled run is due, and never runs
 * twice for the same due date.
 *
 * @package  Rgpd\Console
 */
class PurgeUsersCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('rgpd:purge')
            ->setDescription('Purge the user accounts inactive for too long')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Purge now instead of waiting for the scheduled run')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'List the accounts that would be removed without removing them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = (bool) $input->getOption('force');
        $dry_run = (bool) $input->getOption('dry-run');
        $now = time();

        // Every call is used to date the accounts created since the last one
        $this->userFirstSeenModel->recordNewUsers($now);

        if (! $force && ! $dry_run && ! $this->isRunnable($output, $now)) {
            return 0;
        }

        $settings = $this->purgeScheduleModel->getSettings();
        $threshold_date = $this->purgeScheduleModel->getThresholdDate();

        if ($threshold_date <= 0) {
            $output->writeln('<error>The purge threshold is not configured</error>');
            return 1;
        }

        if ($threshold_date > $now) {
            $output->writeln('<error>The purge threshold is in the future</error>');
            return 1;
        }

        $include_never_logged = ! empty($settings[PurgeScheduleModel::OPTION_INCLUDE_NEVER_LOGGED]);
        $output->writeln('Accounts whose last login is older than '.date('Y-m-d H:i', $threshold_date));

        if ($dry_run) {
            return $this->listCandidates($output, $threshold_date, $include_never_logged);
        }

        $nb_users = $this->userPurgeModel->purge($threshold_date, $include_never_logged);
        $this->purgeLogModel->create(PurgeLogModel::MODE_AUTOMATIC, $threshold_date, $nb_users);

        $output->writeln('<info>'.$nb_users.' user account(s) removed</info>');

        return 0;
    }

    /**
     * Decide whether the scheduled run must happen now
     *
     * The schedule is moved forward before the purge itself, so that a purge
     * failing halfway is not retried at every call of the scheduler.
     *
     * @access protected
     * @param  OutputInterface $output
     * @param  integer         $now
     * @return bool
     */
    protected function isRunnable(OutputInterface $output, $now)
    {
        $occurrence = $this->purgeScheduleModel->getNextRunTimestamp();

        if ($occurrence === 0) {
            $output->writeln('<info>Nothing to do, the automatic purge is disabled</info>');
            return false;
        }

        if ($occurrence > $now) {
            $output->writeln('<info>Nothing to do, next run scheduled on '.date('Y-m-d H:i', $occurrence).'</info>');
            return false;
        }

        $already_run = $this->purgeLogModel->hasAutomaticRunSince($occurrence);
        $next_run = $this->purgeScheduleModel->advanceNextRun($now);

        if ($already_run) {
            $output->writeln('<info>Already purged for the run due on '.date('Y-m-d H:i', $occurrence).', next run on '.$next_run.'</info>');
            return false;
        }

        $output->writeln('Running the purge due on '.date('Y-m-d H:i', $occurrence).', next run on '.$next_run);

        return true;
    }

    /**
     * Print the accounts that would be removed
     *
     * @access protected
     * @param  OutputInterface $output
     * @param  integer         $threshold_date
     * @param  bool            $include_never_logged
     * @return integer
     */
    protected function listCandidates(OutputInterface $output, $threshold_date, $include_never_logged)
    {
        $candidates = $this->userPurgeModel->getCandidates($threshold_date, $include_never_logged);

        foreach ($candidates as $user) {
            $output->writeln(sprintf(
                '  #%d %s (%s)',
                $user['id'],
                $user['username'],
                $user['last_login'] > 0 ? date('Y-m-d H:i', $user['last_login']) : 'never connected'
            ));
        }

        $output->writeln('<info>'.count($candidates).' user account(s) would be removed</info>');

        return 0;
    }
}
