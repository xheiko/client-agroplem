<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
$APPLICATION->ShowAjaxHead();
$APPLICATION->IncludeComponent('tanais.alter:deal.choosing.contract.tab', '', [
    "DEAL_ID" => $_REQUEST['dealId'],
]);