<?
//https://altab24.agrochemist.ru/clientagr/incoming/client_handler.php
//ONCRMCOMPANYADD ONCRMCOMPANYUPDATE

// Отключаем лишние вещи для скорости и безопасности (по желанию)
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_SECURITY_SESSION_VARS_CHECK', false); // осторожно — см. замечание ниже
// Подключаем минимальный пролог Битрикс (без шаблона/эпилога)
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$logfilename = "/home/bitrix/www/local/log/tanais.clientagr/client_handler.log";
$tokens=[
    "bitrix.agroplem.ru"=>'qax1u8y5px7u03y526t02agkys02qe1r'
];
file_put_contents($logfilename,"-----".PHP_EOL.date("Y-m-d H:i:s").PHP_EOL,FILE_APPEND);
file_put_contents($logfilename,'$_REQUEST='.var_export($_REQUEST,true).PHP_EOL,FILE_APPEND);
echo "Hello!";
?>