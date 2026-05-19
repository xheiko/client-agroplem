<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php";
$APPLICATION->SetTitle("Кастомные отчёты Агроплем");

\Bitrix\Main\Loader::includeModule("tanais.alter");
\Bitrix\Main\UI\Extension::load('tanais.alter.report.report_list');
\Bitrix\Main\UI\Extension::load('tanais.alter.fontawesome');
const MODULE_PATH = '/home/bitrix/www/local/modules/tanais.alter/';

// $APPLICATION->SetAdditionalCss("/local/tanais/reports/report_style.css");

// зачем?
 $APPLICATION->IncludeComponent(
     'bitrix:crm.control_panel',
     '',
     [
         'ID' => 'CUSTOM_REPORTS_AGR',
         'ACTIVE_ITEM_ID' => 'CUSTOM_REPORTS_AGR',
     ]
 );
// /зачем?

$APPLICATION->IncludeComponent(
    "tanais.alter:report.list",
    "",
    []
);
?>
<?php require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"; ?>