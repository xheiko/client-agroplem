<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use Bitrix\Main;

\Bitrix\Main\UI\Extension::load('tanais.alter.fontawesome');

Main\Loader::requireModule("tanais.alter");

$APPLICATION->IncludeComponent(
    "tanais.alter:report.new.clients",
    "",
    [
    ],
    $component
);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
