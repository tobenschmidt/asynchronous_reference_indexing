<?php
namespace NamelessCoder\AsyncReferenceIndexing\EventListener;

use TYPO3\CMS\Core\DataHandling\Event\IsTableExcludedFromReferenceIndexEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Class IsTableExcludedFromReferenceIndexEventListener
 *
 */
class IsTableExcludedFromReferenceIndexEventListener
{
    public function __invoke(IsTableExcludedFromReferenceIndexEvent $event): void
    {
        if ($event->isTableExcluded()) {
            return;
        }
        $excludeTablesFromReferenceIndexing = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('asynchronous_reference_indexing', 'excludeTablesFromReferenceIndexing');
        if (empty($excludeTablesFromReferenceIndexing)) {
            return;
        }
        $excludeTableArray = GeneralUtility::trimExplode(',', $excludeTablesFromReferenceIndexing);
        if (in_array($event->getTable(), $excludeTableArray)) {
            $event->markAsExcluded();
        }
    }
}
