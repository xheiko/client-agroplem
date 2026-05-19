<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();
global $APPLICATION;

d('ff');
d($arResult);
?>

<div class="table_container">
    <?php
    $APPLICATION->IncludeComponent(
        'tanais.alter:grid.all',
        'custom_company',
        [
            'GRID_ID' => 'custom',
            'COLUMNS' => $arResult['COLUMNS'],
            'ROWS' => '',
            'ROWS_CUSTOM' => $arResult['LIST'],
            'SHOW_ROW_CHECKBOXES' => true,
            'NAV_OBJECT' => [],
            'AJAX_MODE' => 'Y',
            'AJAX_ID' => \CAjax::getComponentID('custom:main.ui.grid', 'custom_company', ''),
            'PAGE_SIZES' => [
                ['NAME' => "5", 'VALUE' => '5'],
                ['NAME' => '10', 'VALUE' => '10'],
                ['NAME' => '20', 'VALUE' => '20'],
                ['NAME' => '50', 'VALUE' => '50'],
                ['NAME' => '100', 'VALUE' => '100']
            ],
            'AJAX_OPTION_JUMP' => 'N',
            'SHOW_CHECK_ALL_CHECKBOXES' => true,
            'SHOW_ROW_ACTIONS_MENU' => true,
            'SHOW_GRID_SETTINGS_MENU' => true,
            'SHOW_NAVIGATION_PANEL' => true,
            'SHOW_PAGINATION' => true,
            'SHOW_SELECTED_COUNTER' => true,
            'SHOW_TOTAL_COUNTER' => true,
            'SHOW_PAGESIZE' => true,
            'SHOW_ACTION_PANEL' => true,
            'ACTION_PANEL' => [
                'GROUPS' => [
                    'TYPE' => [
                        'ITEMS' => [
                            [
                                'ID' => 'set-type',
                                'TYPE' => 'DROPDOWN',
                                'ITEMS' => [
                                    ['VALUE' => '', 'NAME' => '- Выбрать -'],
                                    ['VALUE' => 'plus', 'NAME' => 'Поступление'],
                                    ['VALUE' => 'minus', 'NAME' => 'Списание']
                                ]
                            ],
                        ],
                    ]
                ],
            ],
            'ALLOW_COLUMNS_SORT' => true,
            'ALLOW_COLUMNS_RESIZE' => true,
            'ALLOW_HORIZONTAL_SCROLL' => true,
            'ALLOW_SORT' => true,
            'ALLOW_PIN_HEADER' => true,
            'AJAX_OPTION_HISTORY' => 'N'
        ]
    ); ?>
</div>