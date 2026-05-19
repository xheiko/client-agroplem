<?
return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Tanais\\Alter\\Controller',
            'restIntegration' => [ 'enabled' => true, ]
        ],
        'readonly' => true,
    ],
    'intranet.customSection' => [
        'value' => [
            'provider' => '\\Tanais\\Alter\\SectionProvider',
        ],
    ],
];
