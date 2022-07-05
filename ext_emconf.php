<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Generate redirect if the slug changes',
    'description' => '',
    'category' => 'be',
    'author' => 'Georg Ringer',
    'author_email' => 'mail@ringer.it',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'redirects' => '10.4.13-11.9.99',
            'news' => '9.0.0-11.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
