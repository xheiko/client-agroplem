<?php
use Bitrix\Main;
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

Main\Loader::requireModule("tanais.alter");

$APPLICATION->IncludeComponent(
    "tanais.alter:sending.conclusion",
    "",
    [
    ],
    $component
);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
