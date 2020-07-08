<?php
namespace NamelessCoder\AsyncReferenceIndexing\Command;

use NamelessCoder\AsyncReferenceIndexing\Database\ReferenceIndex as AsyncReferenceIndex;
use NamelessCoder\AsyncReferenceIndexing\Traits\ReferenceIndexQueueAware;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Async Reference Index Commands
 *
 * Commandline execution for reference index updating
 * based on the queue maintained by the DataHandler
 * override shipped with this extension.
 */
class AsyncReferenceIndexCommand extends Command
{
    use ReferenceIndexQueueAware;

    const LOCKFILE = 'typo3temp/var/reference-indexing-running.lock';

    /**
     * Configure the asynchronous reference indexing command
     */
    protected function configure()
    {
        $this->setDescription('Update the reference index');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Index directly to sys_refindex without asynchronous indexing');
        $this->addOption('check', 'c', InputOption::VALUE_NONE, 'Check reference index without modification if indexing directly to sys_refindex');
        $this->addOption('silent', 's', InputOption::VALUE_NONE, 'Suppress output if indexing directly to sys_refindex');
    }

    /**
     * Update Reference Index
     *
     * Updates the reference index - if providing the --force option the
     * indexing will index directly to sys_refindex, additional --check
     * option will only check sys_refindex without modification, --silent
     * option will suppress output
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        if ($input->getOption('force')) {
            AsyncReferenceIndex::captureReferenceIndex(false);
            $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
            $refIndexObj->updateIndex($input->getOption('check'), !$input->getOption('silent'));
        }
        else {
            $io = new SymfonyStyle($input, $output);
            $io->title($this->getDescription());
            $this->updateReferenceIndex($io);
        }
        return 0;
    }

    /**
     * Update Reference Index
     *
     * Updates the reference index by
     * processing the queue maintained by
     * the overridden DataHandler class.
     *
     * @param SymfonyStyle $io
     * @return void
     */
    protected function updateReferenceIndex(SymfonyStyle $io)
    {
        $lockFile = GeneralUtility::getFileAbsFileName(static::LOCKFILE);
        if (file_exists($lockFile)) {
            $io->writeln('Another process is updating the reference index - skipping');
            return;
        }

        $count = $this->performCount('tx_asyncreferenceindexing_queue');

        if (!$count) {
            $io->writeln('No reference indexing tasks queued - nothing to do.');
            return;
        }

        $this->lock();

        $io->writeln(
            'Processing reference index for ' . $count . ' record(s)'
        );

        // Note about loop: a fresh instance of ReferenceIndex is *intentional*. The class mutates
        // internal state during processing. Furthermore, we catch any errors and exit only after
        // removing the lock file. Any error causes processing to stop completely.
        try {

            // Force the reference index override to disable capturing. Will apply to *all* instances
            // of ReferenceIndex (but of course only when the override gets loaded).
            AsyncReferenceIndex::captureReferenceIndex(false);

            foreach ($this->getRowsWithGenerator('tx_asyncreferenceindexing_queue') as $queueItem) {

                /** @var $referenceIndex ReferenceIndex */
                $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
                if (!empty($queueItem['reference_workspace']) && BackendUtility::isTableWorkspaceEnabled($queueItem['reference_table'])) {
                    $referenceIndex->setWorkspaceId($queueItem['reference_workspace']);
                }
                $referenceIndex->updateRefIndexTable($queueItem['reference_table'], $queueItem['reference_uid']);
                $this->performDeletion(
                    'tx_asyncreferenceindexing_queue',
                    sprintf(
                        'reference_table = \'%s\' AND reference_uid = %d AND reference_workspace = %d',
                        (string) $queueItem['reference_table'],
                        (integer) $queueItem['reference_uid'],
                        (integer) $queueItem['reference_workspace']
                    )
                );

            }
            $io->writeln('Reference indexing complete!');
            $this->unlock();

        } catch (\Exception $error) {

            $io->writeln('ERROR! ' . $error->getMessage() . ' (' . $error->getCode() . ')');
            $this->unlock();

        }
    }

    /**
     * @return string
     */
    protected function getLockFile()
    {
        return GeneralUtility::getFileAbsFileName(static::LOCKFILE);
    }

    /**
     * Lock so that other command instances do not start running.
     *
     * @return void
     */
    protected function lock()
    {
        touch(
            $this->getLockFile()
        );
    }

    /**
     * Removes run protection lock
     *
     * @return void
     */
    protected function unlock()
    {
        unlink(
            $this->getLockFile()
        );
    }
}
