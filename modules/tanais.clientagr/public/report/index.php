<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

/*
\Bitrix\Main\UI\Extension::load('tanais.alta.fontawesome');
Main\Loader::requireModule("tanais.alta");
*/

\Bitrix\Main\Loader::requireModule("tanais.clientagr");

$APPLICATION->IncludeComponent(
    "tanais.clientagr:report.clients",
    "",
    [
    ],
    $component
);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
