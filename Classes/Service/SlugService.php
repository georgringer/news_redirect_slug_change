<?php
declare(strict_types=1);

namespace GeorgRinger\NewsRedirectSlugChange\Service;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility as BackendUtilityCore;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;

class SlugService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected Context $context;
    protected SiteInterface $site;
    protected SiteFinder $siteFinder;
    protected PageRepository $pageRepository;
    protected LinkService $linkService;
    protected bool $autoCreateRedirects;
    protected int $redirectTTL;
    protected int $httpStatusCode;
    protected int $targetPageId;
    protected RedirectCacheService $redirectCacheService;
    protected $typo3MajorVersion = 0;

    public function __construct(
        Context              $context,
        SiteFinder           $siteFinder,
        PageRepository       $pageRepository,
        LinkService          $linkService,
        RedirectCacheService $redirectCacheService
    )
    {
        $this->context = $context;
        $this->siteFinder = $siteFinder;
        $this->pageRepository = $pageRepository;
        $this->linkService = $linkService;
        $this->redirectCacheService = $redirectCacheService;
        $this->typo3MajorVersion = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
    }


    public function rebuildSlugsForSlugChange(int $recordId, string $currentSlug, string $newSlug, CorrelationId $correlationId): int
    {
        $currentRecord = BackendUtility::getRecord('tx_news_domain_model_news', $recordId);
        if ($currentRecord === null) {
            return 0;
        }
        $pageId = $currentRecord['pid'];
        $this->initializeSettings($pageId);
        if ($this->autoCreateRedirects && $this->targetPageId) {
            $redirectRow = $this->createRedirect($currentSlug, $recordId, (int)$currentRecord['sys_language_uid'], $pageId);

            if ($redirectRow) {
                if ($this->typo3MajorVersion < 11) {
                    $this->redirectCacheService->rebuild();
                } else {
                    $this->redirectCacheService->rebuildForHost($redirectRow['source_host'] ?: '*');
                }
                return $redirectRow['uid'];
            }
        }
        return 0;
    }

    /**
     * @return array redirect record
     */
    protected function createRedirect(string $originalSlug, int $recordId, int $languageId, int $pid): array
    {
        $siteLanguage = $this->site->getLanguageById($languageId);

        /** @var DateTimeAspect $date */
        $date = $this->context->getAspect('date');
        $endtime = $date->getDateTime()->modify('+' . $this->redirectTTL . ' days');
        $targetLink = $this->linkService->asString([
            'type' => 'page',
            'pageuid' => $this->targetPageId,
            'parameters' => '_language=' . $languageId . '&tx_news_pi1[controller]=News&tx_news_pi1[action]=detail&tx_news_pi1[news]=' . $recordId,
        ]);

        $sourcePath = $this->generateUrl($this->site, $recordId, $this->targetPageId);
        $sourcePath = '/' . ltrim(str_replace(['http://', 'https://', $siteLanguage->getBase()->getHost()], '', $sourcePath), '/');

        $record = [
            'pid' => $pid,
            'updatedon' => $date->get('timestamp'),
            'createdon' => $date->get('timestamp'),
            'createdby' => $this->context->getPropertyFromAspect('backend.user', 'id'),
            'deleted' => 0,
            'disabled' => 0,
            'starttime' => 0,
            'endtime' => $this->redirectTTL > 0 ? $endtime->getTimestamp() : 0,
            'source_host' => $siteLanguage->getBase()->getHost() ?: '*',
            'source_path' => $sourcePath,
            'is_regexp' => 0,
            'force_https' => 0,
            'respect_query_parameters' => 0,
            'target' => $targetLink,
            'target_statuscode' => $this->httpStatusCode,
            'hitcount' => 0,
            'lasthiton' => 0,
            'disable_hitcount' => 0,
        ];
        if ($this->typo3MajorVersion >= 12) {
            unset($record['createdby']);
            $record['creation_type'] = 6322;
        }
        //todo use dataHandler to create records
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_redirect');
        $connection->insert('sys_redirect', $record);
        return (array)BackendUtility::getRecord('sys_redirect', (int)$connection->lastInsertId('sys_redirect'));
    }


    protected function generateUrl(SiteInterface $site, int $recordId, int $detailPageId): string
    {
        $additionalQueryParams = [
            'tx_news_pi1' => [
                'action' => 'detail',
                'controller' => 'News',
                'news' => $recordId
            ]
        ];
        return (string)$site->getRouter()->generateUri(
            (string)$detailPageId,
            $additionalQueryParams
        );
    }

    protected function initializeSettings(int $pageId): void
    {
        $this->site = $this->siteFinder->getSiteByPageId($pageId);
        $settings = $this->site->getConfiguration()['settings']['redirectsNews'] ?? [];
        $this->autoCreateRedirects = (bool)($settings['autoCreateRedirects'] ?? true);
        if (!$this->context->getPropertyFromAspect('workspace', 'isLive')) {
            $this->autoCreateRedirects = false;
        }
        $this->redirectTTL = (int)($settings['redirectTTL'] ?? 0);
        $this->httpStatusCode = (int)($settings['httpStatusCode'] ?? 307);
        $this->targetPageId = (int)($settings['pageId'] ?? 0);

        $pagesTsConfig = BackendUtilityCore::getPagesTSconfig($pageId);
        $overruledPageId = (int)($pagesTsConfig['tx_news.']['redirect.']['pageId'] ?? 0);
        if ($overruledPageId) {
            $this->targetPageId = $overruledPageId;
        }
    }

}
