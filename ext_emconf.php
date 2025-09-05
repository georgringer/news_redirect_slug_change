<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Automated redirects for EXT:news articles',
    'description' => 'Generate a redirect if the slug of a news article changes',
    'category' => 'be',
    'author' => 'Georg Ringer',
    'author_email' => 'mail@ringer.it',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '2.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'redirects' => '13.4.0-13.4.99',
            'news' => '12.0.0-12.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
