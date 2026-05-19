<?php
$_SERVER["DOCUMENT_ROOT"] = "/home/bitrix/www";
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $USER;
$USER = new \CUser();
$USER->Authorize(1); // авторизуем

\Bitrix\Main\Loader::includeModule("tanais.clientagr");
$data = \Tanais\ClientAGR\Company::forceSynchronizeAllCompany('altab24.agrochemist.ru');
var_export($data);

?>