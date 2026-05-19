<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
$APPLICATION->ShowAjaxHead();
$APPLICATION->IncludeComponent('tanais.alter:company.leads.tab', '', [
"COMPANY_ID" => $_REQUEST['companyId'],
]);