<?php
defined('B_PROLOG_INCLUDED') || die;
global $APPLICATION;

if ($_REQUEST['clear_cache'] == 'Y')
    LocalRedirect($_SERVER['SCRIPT_URL']);

if (!$arParams['IFRAME']) {
     $APPLICATION->IncludeComponent(
        'bitrix:crm.control_panel',
        '',
        [
            'ID' => 'CUSTOM_REPORTS',
            'ACTIVE_ITEM_ID' => 'CUSTOM_REPORTS',
        ]
    );
}

if ($arParams['HIDE_FILTER'] !== true) {
    \Bitrix\UI\Toolbar\Facade\Toolbar::addFilter([
        'FILTER_ID' => $arParams['GRID_ID'],
        'GRID_ID' => $arParams['GRID_ID'],
        'FILTER' => $arResult['FILTER_UI'],
        'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
        'VALUE_REQUIRED' => true,
        'VALUE_REQUIRED_MODE' => true,
        'RESET_TO_DEFAULT_MODE' => true,
        'ENABLE_LIVE_SEARCH' => true,
        'ENABLE_LABEL' => true
    ]);
}


if ($arParams['SHOW_BUTTON_UPDATE_CATALOG_FIELDS'] === true) {
    \Bitrix\Main\UI\Extension::load('tanais.alta.crm.catalog.updateCatalogFields');
    $buttonMode = new \Bitrix\UI\Buttons\Button([
        "color" => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
        "click" => new \Bitrix\UI\Buttons\JsCode(
            'updateCatalogFields();'
        ),
        "text" => "Обновить данные",
    ]);
    \Bitrix\UI\Toolbar\Facade\Toolbar::addButton($buttonMode);
}

if ($arParams['HIDE_BUTTON_EXPORT'] !== true) {
    $buttonMode = new \Bitrix\UI\Buttons\Button([
        "color" => \Bitrix\UI\Buttons\Color::PRIMARY_DARK,
        "link" => "?EXPORT_AS=excel",
        "text" => "Экспорт EXCEL",
    ]);
    \Bitrix\UI\Toolbar\Facade\Toolbar::addButton($buttonMode);
}

$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
    'GRID_ID' => $arParams['GRID_ID'],
    'COLUMNS' => $arResult["COLUMNS"],
    'ROWS' => $arResult["ROWS"],
    'NAV_OBJECT' => $arResult["NAV_OBJECT"],
    'SHOW_ROW_CHECKBOXES' => false,
    //'SHOW_ROW_CHECKBOXES' => true,
    'AJAX_MODE' => 'Y',
    'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
    'PAGE_SIZES' => [
        ['NAME' => '5', 'VALUE' => '5'],
        ['NAME' => '10', 'VALUE' => '10'],
        ['NAME' => '20', 'VALUE' => '20'],
        ['NAME' => '50', 'VALUE' => '50'],
        ['NAME' => '100', 'VALUE' => '100'],
        ['NAME' => '200', 'VALUE' => '200'],
        ['NAME' => '500', 'VALUE' => '500'],
    ],
    'AJAX_OPTION_JUMP' => 'N',
    'TOTAL_ROWS_COUNT' => $arResult["COUNT"],
    'SHOW_CHECK_ALL_CHECKBOXES' => false,
    'SHOW_ROW_ACTIONS_MENU' => true,
    'SHOW_GRID_SETTINGS_MENU' => true,
    'SHOW_NAVIGATION_PANEL' => true,
    'SHOW_PAGINATION' => true,
    'SHOW_SELECTED_COUNTER' => true,
    'SHOW_TOTAL_COUNTER' => true,
    'SHOW_PAGESIZE' => true,
    'SHOW_ACTION_PANEL' => true,
    'ALLOW_STICKED_COLUMNS' => true,
    'ALLOW_COLUMNS_SORT' => true,
    'ALLOW_COLUMNS_RESIZE' => true,
    'ALLOW_HORIZONTAL_SCROLL' => true,
    'ALLOW_SORT' => true,
    'ALLOW_PIN_HEADER' => true,
    'AJAX_OPTION_HISTORY' => 'N',
    // 'TILE_GRID_MODE' =>true
]);
if ($arResult["GENERATED_TIME"])
    echo '<div id="generated_time">Время генерациии: ' . $arResult["GENERATED_TIME"] . " сек.</div>";
if ($arResult["DATA_CACHED"])
    echo '<div id="data_cached">Данные прочитаны из кеша, получены из БД ' . FormatDate('x', $arResult["DATA_ACTUAL_TIME"]) . '</div>';
?>

<script>
    BX.ready(function () {
        // let filter = BX.Main.filterManager.getById('drivers_info');  /// DRIVERS_INFO???
        // let values = filter.getFilterFieldsValues();

        // values['DATE_COUNT'] = '15';

        // filter.getApi().setFields(values);
        // filter.getApi().apply();
    })
</script>