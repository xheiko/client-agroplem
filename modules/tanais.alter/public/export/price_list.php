<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
if (empty($_SERVER["DOCUMENT_ROOT"])) $_SERVER["DOCUMENT_ROOT"] = '/home/bitrix/www';

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_CRONTAB", true);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule("tanais.alter");
if (isset($_GET['SECTION_ID'])) {
    $sectionId = intval($_GET['SECTION_ID']);
}

\Tanais\Alter\Crm\DocumentGenerator::createPriceList($sectionId);


