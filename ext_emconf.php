<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Automated redirects for EXT:news articles',
    'description' => 'Generate a redirect if the slug of a news article changes',
    'category' => 'be',
    'author' => 'Georg Ringer',
    'author_email' => 'mail@ringer.it',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '1.2.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.13-12.9.99',
            'redirects' => '10.4.13-12.9.99',
            'news' => '9.0.0-11.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
