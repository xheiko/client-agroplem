<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
$debugfilelog = "/home/bitrix/www/local/log/bitrix_send.debug";
\Bitrix\Main\Loader::includeModule('crm');
\Bitrix\Main\Loader::includeModule("main");

use Bitrix\Crm\Integration\Main\UISelector;
use \Bitrix\Crm\Service;


$sendDate = 'UF_CRM_4_SHIPMENT_DATE';


$emailFrom = $_POST['from'];
var_dump($_POST);
$dealId = $_POST['dealId'];
$smartId = $_POST['smartId'];
$companyId = $_POST['companyId'];
$idSmartProcessElement = $_POST['idSmartProcessElement'];

$arFiles = unserialize($_POST['files']);
//$arFiles = [90179, 93783];
//var_dump($arFiles);

$emailsTo = $_POST['email']['to'];
$emailsToSend = '';
foreach ($emailsTo as $emailTo) {
    $emailTo = json_decode($emailTo);
    $emailsToSend .= $emailTo->email . ',';
}
// $emailsToSend=implode(',',$emailsTo);


$emailsBCopy = $_POST['email']['copy'];
// file_put_contents($debugfilelog.'.emailsBCopy', var_export($emailsBCopy,true));
$emailsBCopySend = [];
foreach ($emailsBCopy as $emailBCopy) {
    $emailBCopy = json_decode($emailBCopy);
    $emailsBCopySend[] = $emailBCopy->email;
}
$BCC = implode(', ', $emailsBCopySend);
// file_put_contents($debugfilelog.'.BCC', var_export($BCC,true));


$title = $_POST['title'];

$bodySend = $_POST['bodysend'];


$arEventFields = array(
    "MESSAGE_ID" => 120,
    "EMAIL_FROM" => $emailFrom,
    "EMAIL_TO" => $emailsToSend,
    "EMAIL_COPY" => '',
    "EMAIL_BCOPY" => $BCC,
    "TITLE" => $title,
    "MAIL_TEXT" => $bodySend
);


$sendId = CEvent::Send("SENDING_REPORT", "s1", $arEventFields, $Duplicate = "N", '', $arFiles);

//обновления даты фактической отправки
if ($sendId != 0) {
    $now = new DateTime('now');
    $now = $now->format('d.m.Y');
//
//    $entityResult = \CCrmDeal::GetListEx(
//        [],
//        [
//            'ID' => $dealId
//        ],
//        false,
//        false,
//        [
//            'UF_CRM_1570104297940'
//        ]
//    );
//
//    $entity = $entityResult->fetch();
//
//    if (empty($entity['UF_CRM_1570104297940'])) {
//        $bCheckRight = false;
//        $entityFields = [
//            'UF_CRM_1570104297940' => $now,
//        ];
//        $entityObject = new \CCrmDeal($bCheckRight);
//        $isUpdateSuccess = $entityObject->Update(
//            $dealId,
//            $entityFields,
//            $bCompare = true,
//            $arOptions = []
//        );
//    }

    $container = Service\Container::getInstance();
    $factory = $container->getFactory(1042);
    $element = $factory->getItem($idSmartProcessElement);
    $elementData = $element->getData();
    if (empty($elementData[$sendDate])) {
        $element->set($sendDate, $now);
        $saveResult = $element->save();
    }

    // file_put_contents($debugfilelog, var_export($arEventFields,true));
    // file_put_contents($debugfilelog, var_export($sendId,true),FILE_APPEND);
    // file_put_contents($debugfilelog, '-------',FILE_APPEND);

    $textForTimeline = '<b>От кого: </b>' . $emailFrom . '<br><br>' .
        '<b>Кому: </b>' . $emailsToSend . '<br><br>' .
        '<b>Скрытая копия: </b>' . $BCC . '<br><br>' .
        '<b>Тема: </b>' . $title . '<br><br><br>' .
        $bodySend;
    var_dump($textForTimeline);
    //добавление в таймлайн сделки
    $fields = [
        'TYPE_ID' => CCrmActivityType::Email,
        'OWNER_TYPE_ID' => 1042,
        'SUBJECT' => $title,
        'COMPLETED' => 'Y',
        'RESPONSIBLE_ID' => 1,
        'PRIORITY' => CCrmActivityPriority::Medium,
        'DESCRIPTION' => $textForTimeline,
        'DESCRIPTION_TYPE' => \CCrmContentType::Html,
        'DIRECTION' => CCrmActivityDirection::Outgoing,
        'BINDINGS' => [
            [
                'OWNER_TYPE_ID' => 1042,
                'OWNER_ID' => $smartId
            ],
            [
                'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
                'OWNER_ID' => $dealId
            ],
            [
                'OWNER_TYPE_ID' => CCrmOwnerType::Company,
                'OWNER_ID' => $companyId
            ],
        ],
    ];
    CCrmActivity::Add($fields, true);
}
