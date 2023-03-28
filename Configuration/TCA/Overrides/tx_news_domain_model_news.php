<?php

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() >= 12) {
    $GLOBALS['TCA']['sys_redirect']['columns']['creation_type']['config']['items'][] = [
        'label' => 'news',
        'value' => 6322,
    ];
}
