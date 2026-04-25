<?php

$EM_CONF[$_EXTKEY] = [
    'title'        => 'Site Master Data',
    'description'  => 'Stores master data (address, phone, e-mail, officers) centrally in TYPO3 Site Settings. Editors insert placeholders in any RTE text; TYPO3 replaces them with the current values automatically. Update once — reflect everywhere.',
    'category'     => 'be',
    'author'       => 'porthd',
    'author_email' => '',
    'state'        => 'beta',
    'version'      => '0.0.1',
    'constraints'  => [
        'depends' => [
            'typo3' => '14.3.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests'  => [],
    ],
];
