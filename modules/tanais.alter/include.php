<?php
use Bitrix\Main;
use Bitrix\Main\DI;
use Bitrix\Main\Loader;

\Bitrix\Main\Loader::includeModule("crm");
\Bitrix\Main\Loader::includeModule("iblock");
\Bitrix\Main\Loader::includeModule("catalog");
\Bitrix\Main\Loader::includeModule("intranet");
\Bitrix\Main\Loader::includeModule("messageservice");
\Bitrix\Main\Loader::includeModule("currency");
\Bitrix\Main\Loader::includeModule("tasks");
require_once $_SERVER["DOCUMENT_ROOT"].'/bitrix/vendor/autoload.php'; //Bitrix Composer


DI\ServiceLocator::getInstance()->addInstanceLazy('crm.service.container', [
    'className' => '\\Tanais\\Alter\\Service\\Container',
]);



