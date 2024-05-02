<?php

$GLOBALS['TL_DCA']['tl_multilang_settings'] = [
    'config' => [
        'dataContainer' => 'File',
        'closed' => true
    ],
    'palettes' => [
        'default' => "
        {settings_legend},languages;
        {undefined_languages_settings_legend:hide},tables;
    "
    ],
    'fields' => [
        'languages' => [
            'inputType' => 'langSelectionWizard'
        ],
        'tables' => [
            'inputType' => 'multilangTablesWizard'
        ]
    ]
];
