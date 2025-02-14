<?php
declare(strict_types=1);

namespace GeorgRinger\NewsRedirectSlugChange\Hooks;

use GeorgRinger\NewsRedirectSlugChange\Service\SlugService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class DataHandlerSlugUpdateHook
{

    protected SlugService $slugService;

    /**
     * @var string[]
     */
    protected array $persistedSlugValues;

    /**
     * @param SlugService $slugService
     */
    public function __construct(SlugService $slugService)
    {
        $this->slugService = $slugService;
    }

    /**
     * Collects slugs of persisted records before having been updated.
     *
     * @param array $incomingFieldArray
     * @param string $table
     * @param string|int $id (id could be string, for this reason no type hint)
     * @param DataHandler $dataHandler
     */
    public function processDatamap_preProcessFieldArray(array $incomingFieldArray, string $table, $id, DataHandler $dataHandler): void
    {
        if (
            $table !== 'tx_news_domain_model_news'
            || empty($incomingFieldArray['path_segment'])
            || !MathUtility::canBeInterpretedAsInteger($id)
            || !$dataHandler->checkRecordUpdateAccess($table, $id, $incomingFieldArray)
        ) {
            return;
        }

        $record = BackendUtility::getRecordWSOL($table, (int)$id, 'path_segment');
        $this->persistedSlugValues[(int)$id] = $record['path_segment'];
    }

    /**
     * Acts on potential slug changes.
     *
     * Hook `processDatamap_postProcessFieldArray` is executed after `DataHandler::fillInFields` which
     * ensure access to pages.slug field and applies possible evaluations (`eval => 'trim,...`).
     */
    public function processDatamap_postProcessFieldArray(string $status, string $table, $id, array $fieldArray, DataHandler $dataHandler): void
    {
        $persistedSlugValue = $this->persistedSlugValues[(int)$id] ?? null;
        if (
            $table !== 'tx_news_domain_model_news'
            || $status !== 'update'
            || empty($fieldArray['path_segment'])
            || $persistedSlugValue === null
            || $persistedSlugValue === $fieldArray['path_segment']
        ) {
            return;
        }

        $redirectId = $this->slugService->rebuildSlugsForSlugChange($id, $persistedSlugValue, $fieldArray['path_segment'], $dataHandler->getCorrelationId());
        if ($redirectId) {
            $this->addMessage($redirectId);
        }
    }

    protected function addMessage(int $recordId): void
    {
        $message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:news_redirect_slug_change/Resources/Private/Language/de.locallang.xlf:notification.success'), $recordId);
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', ContextualFeedbackSeverity::INFO, true);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }
}
