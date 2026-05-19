<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();
global $APPLICATION;

$buttonMode = new \Bitrix\UI\Buttons\Button([
    "color" => \Bitrix\UI\Buttons\Color::PRIMARY_DARK,
    "link" => $APPLICATION->GetCurPageParam("EXPORT_REPORT=docx", ["EXPORT_REPORT"]),
    "text" => "Выгрузить отчёты",
]);
\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($buttonMode);

$APPLICATION->IncludeComponent(
    "tanais.alter:grid.all",
    "",
    [
        //"GRID_ID" => preg_replace("/[^a-zA-Z0-9\s]/", '', $arParams['GRID_ID']),
        "GRID_ID" => 'fdghytrehertyhevc467u657fdsghcchjjytjukdsdj',
        "REPORT_OBJECT" => $arParams['REPORT_OBJECT'],
        "HIDE_FILTER" => $arParams['HIDE_FILTER'],
        "HIDE_BUTTON_EXPORT" => $arParams['HIDE_BUTTON_EXPORT'],
    ],
    $component
);
