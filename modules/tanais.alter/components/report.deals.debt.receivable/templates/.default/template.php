<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();
global $APPLICATION;

$APPLICATION->IncludeComponent(
    "tanais.alter:grid.all",
    "",
    [
        "GRID_ID" => preg_replace("/[^a-zA-Z0-9\s]/", '', $arParams['GRID_ID']),
        "REPORT_OBJECT" => $arParams['REPORT_OBJECT'],
        "HIDE_FILTER" => $arParams['HIDE_FILTER'],
        "HIDE_BUTTON_EXPORT" => $arParams['HIDE_BUTTON_EXPORT'],
    ],
    $component
);
// d($arParams);