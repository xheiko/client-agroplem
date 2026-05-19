<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_CRONTAB", true);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

define('C_REST_WEB_HOOK_URL', 'https://agroplemaobitrix.agrochemist.ru/rest/1/9jhy8832r25ty54m/');
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/modules/tanais.alter/lib/crest/crest.php');

echo "Hello!";