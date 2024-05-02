<?php

$GLOBALS['TL_DCA']['tl_module']['palettes']['language_switcher'] =
    "
    {title_legend},name,type;
    {content_legend},switcherLayout;
    "
;


$GLOBALS['TL_DCA']['tl_module']['fields']['switcherLayout'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => $GLOBALS["TL_LANG"]['multilang']['switcherLayouts'],
    'eval' => [
        'isAssociative' => false,
        'includeBlankOption' => false,
        'tl_class' => 'w50'
    ],
    'sql' => ['type' => 'string', 'length' => '20', 'default' => '']
];
