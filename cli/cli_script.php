<?php
$_SERVER["DOCUMENT_ROOT"] = "/home/bitrix/www";
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

global $USER;
if(empty($USER)){
$USER = new \CUser();
}
$USER->Authorize(106); // авторизуем


/*

$companies = \Bitrix\Crm\CompanyTable::getList(array(
    'select' => ["ID","UF_CRM_1706860720"],
//    'filter' => ["ID"=> 3537]
))->fetchAll();

foreach ($companies as $company) {
    echo $company["ID"]." ".$company["UF_CRM_1706860720"]."\r\n";
    \Tanais\Alta\Crm\Company::startBPWorkflow(639,$company["ID"]);
}
*/


//\Tanais\Alta\Crm\Company::updateCompanyStat();
//\Tanais\Alta\Crm\Catalog::updateAltaIncBullsPhoto();

//\Tanais\Alta\Cron::doInMorning();
// \Tanais\Alta\Cron::doInLateMorning();
// \Bitrix\Main\Loader::includeModule("tanais.alter");
// $deals = \Bitrix\Crm\DealTable::getList(array(
//     'select' => ["ID", "UF_CRM_PAY_PROCENT", "UF_CRM_SHIPMENT_PROCENT", "UF_CRM_DEAL_PAYMENT_LASTDATE"],
//     'filter' => [
//         "<UF_CRM_PAY_PROCENT" => 100,
//         ">UF_CRM_SHIPMENT_PROCENT" => 0,
//         "UF_CRM_DEAL_PAYMENT_LASTDATE" => null,

//     ],
//     'order' => ["ID"]
// ))->fetchAll();

// $count = 0;
// foreach ($deals as $deal) {

//     $count++;
//     $result = \Tanais\Alter\Crm\Deal::startDealBPWorkflow(12, $deal["ID"]);
//     echo $deal["ID"]." " . var_export($result, true) . PHP_EOL;
// }
// echo "Всего:" . $count++;


\Bitrix\Main\Loader::includeModule('bizproc');
\Bitrix\Main\Loader::includeModule('crm');

$bzId = 12; //id-бп
$arErrorsTmp = []; 
$arDeals = \Bitrix\Crm\DealTable::getList([
'order'=>['ID' => 'DESC'],
'filter'=>['>UF_CRM_SHIPMENT_PROCENT'=> 0, '<DATE_MODIFY' => '03.04.2026','!CATEGORY_ID' => [0,8,],],
'select'=>['ID'],
//'limit'=>50,
]);
 //$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
  $webhook = 'https://bitrix.agroplem.ru/rest/106/z7otmsnmibr28fvr/crm.deal.update.json';
while( $entity = $arDeals->fetch() )
{

$dealId = $entity['ID']; // ID сделки

$data = [
    'id' => $dealId,
    'fields' => [
        'UF_CRM_1775215279730' => time(),
    ],
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $webhook,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => http_build_query($data),
]);

$result = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo 'cURL error: ' . $error;
} else {
    echo $result . ' DEAL_ID '. $dealId;
}
   // \Tanais\Alter\Crm\Deal::startDealBPWorkflow(12,$entity['ID']);
   //  $wfId = \CBPDocument::StartWorkflow(
   // $bzId,
   // array("crm", "CCrmDocumentDeal", "DEAL_".$entity['ID']),
   // array_merge(array(), array("TargetUser" => "user_106")),
   // $arErrorsTmp 
   // );

//     $deal = $factory->getItem($entity['ID']);
//         $deal->set('UF_CRM_1775215279730', "OK");
//        $operation = $factory->getUpdateOperation($deal);
//        $saveResult = $operation->launch();
//        if ( $saveResult->isSuccess() )
// {
//     echo $entity['ID'];
// }
// else
// {
//      d($saveResult->getErrorMessages());
// }
}
