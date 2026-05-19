<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();
\CJSCore::Init(['ajax']);
global $APPLICATION;

if (empty($arResult['rowsData'])) { ?>
    <div class="ui-alert ui-alert-warning">
        <span class="ui-alert-message">У компании в сделке нет договоров</span>
    </div>

    <?php
}


$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
    'GRID_ID' => 'deal_choosing_contract',
    'COLUMNS' => $arResult['columns'],
    'ROWS' => $arResult['rowsData'],
    'SHOW_ROW_CHECKBOXES' => false,
    'AJAX_MODE' => 'Y',
    'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
    'AJAX_OPTION_JUMP' => 'N',
    'TOTAL_ROWS_COUNT' => $arResult['count'],
    'SHOW_CHECK_ALL_CHECKBOXES' => false,
    'SHOW_ROW_ACTIONS_MENU' => false,
    'SHOW_GRID_SETTINGS_MENU' => true,
    'SHOW_NAVIGATION_PANEL' => true,
    'SHOW_PAGINATION' => false,
    'SHOW_SELECTED_COUNTER' => false,
    'SHOW_TOTAL_COUNTER' => true,
    'SHOW_PAGESIZE' => false,
    'SHOW_ACTION_PANEL' => true,
    'ALLOW_STICKED_COLUMNS' => true,
    'ALLOW_COLUMNS_SORT' => true,
    'ALLOW_COLUMNS_RESIZE' => true,
    'ALLOW_HORIZONTAL_SCROLL' => true,
    'ALLOW_SORT' => true,
    'ALLOW_PIN_HEADER' => true,
    'AJAX_OPTION_HISTORY' => 'N',
]);