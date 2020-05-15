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
        $this->addOption('force', null, InputOption::VALUE_NONE, 'force');
        $this->addOption('check', null, InputOption::VALUE_NONE, 'check');
        $this->addOption('silent', null, InputOption::VALUE_NONE, 'silent');
    }

    /**
     * Update Reference Index
     *
     * Updates the reference index - if providing the -f parameter the
     * indexing will index directly to sys_refindex - else the
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
            $io->writeln('Write something');
            //$this->updateReferenceIndex();
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
     * @return void
     */
    protected function updateReferenceIndex()
    {
        $lockFile = GeneralUtility::getFileAbsFileName(static::LOCKFILE);
        if (file_exists($lockFile)) {
            $this->response->setContent('Another process is updating the reference index - skipping' . PHP_EOL);
            return;
        }

        $count = $this->performCount('tx_asyncreferenceindexing_queue');

        if (!$count) {
            $this->response->setContent('No reference indexing tasks queued - nothing to do.' . PHP_EOL);
            return;
        }

        $this->lock();

        $this->response->setContent(
            'Processing reference index for ' . $count . ' record(s)' . PHP_EOL
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
            $this->response->appendContent('Reference indexing complete!' . PHP_EOL);
            $this->unlock();

        } catch (\Exception $error) {

            $this->response->appendContent('ERROR! ' . $error->getMessage() . ' (' . $error->getCode() . ')' . PHP_EOL);
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
