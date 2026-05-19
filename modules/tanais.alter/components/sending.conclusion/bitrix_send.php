<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;
use Bitrix\Crm\Service;
use Bitrix\Crm\Activity\Provider;

define('STOP_STATISTICS', true);
define('NO_AGENT_STATISTIC', true);
define('DisableEventsCheck', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

if (!check_bitrix_sessid()) {
    echo Json::encode(['status' => 'error', 'message' => 'Session expired']);
    die();
}

Loader::includeModule('crm');
Loader::includeModule('main');

$request = Application::getInstance()->getContext()->getRequest();

$sendDateField = 'UF_CRM_4_SHIPMENT_DATE';

$emailFrom = $request->getPost('from');
$dealId = (int)$request->getPost('dealId');
$smartId = (int)$request->getPost('smartId');
$companyId = (int)$request->getPost('companyId');
$idSmartProcessElement = (int)$request->getPost('idSmartProcessElement');
$title = trim($request->getPost('title'));
$bodySend = $request->getPost('bodysend');

$filesSerialized = $request->getPost('files');
$arFiles = $filesSerialized ? @unserialize(html_entity_decode($filesSerialized)) : [];

$emailsTo = $request->getPost('email')['to'] ?? [];
$emailsCopy = $request->getPost('email')['copy'] ?? [];

$emailsToSend = [];
foreach ($emailsTo as $item) {
    $decoded = json_decode($item);
    if (!empty($decoded->email)) {
        $emailsToSend[] = $decoded->email;
    }
}

$emailsBCC = [];
foreach ($emailsCopy as $item) {
    $decoded = json_decode($item);
    if (!empty($decoded->email)) {
        $emailsBCC[] = $decoded->email;
    }
}

// Отправляем письмо
$arEventFields = [
    "MESSAGE_ID" => 120,
    "EMAIL_FROM" => $emailFrom,
    "EMAIL_TO" => implode(',', $emailsToSend),
    "EMAIL_BCOPY" => implode(',', $emailsBCC),
    "TITLE" => $title,
    "MAIL_TEXT" => $bodySend,
];

$sendId = CEvent::Send("SENDING_REPORT", "s1", $arEventFields, "N", "", $arFiles);

if ($sendId) {
    $now = (new DateTime())->format('d.m.Y');
    $container = Service\Container::getInstance();
    $factory = $container->getFactory(1042);
    if ($factory && $idSmartProcessElement) {
        $element = $factory->getItem($idSmartProcessElement);
        if ($element && empty($element->getData()[$sendDateField])) {
            $element->set($sendDateField, $now);
            $element->save();
        }
    }

    // Добавляем в таймлайн
    $textForTimeline = sprintf(
        '<b>От кого:</b> %s<br><b>Кому:</b> %s<br><b>Скрытая копия:</b> %s<br><b>Тема:</b> %s<br><br>%s',
        htmlspecialcharsbx($emailFrom),
        htmlspecialcharsbx(implode(', ', $emailsToSend)),
        htmlspecialcharsbx(implode(', ', $emailsBCC)),
        htmlspecialcharsbx($title),
        $bodySend
    );

    $activityFields = [
        'TYPE_ID' => CCrmActivityType::Email,
        'OWNER_TYPE_ID' => 1042,
        'SUBJECT' => $title,
        'COMPLETED' => 'Y',
        'RESPONSIBLE_ID' => $GLOBALS['USER']->GetID(),
        'PRIORITY' => CCrmActivityPriority::Medium,
        'DESCRIPTION' => $textForTimeline,
        'DESCRIPTION_TYPE' => \CCrmContentType::Html,
        'DIRECTION' => CCrmActivityDirection::Outgoing,
        'BINDINGS' => [
            ['OWNER_TYPE_ID' => 1042, 'OWNER_ID' => $smartId],
            ['OWNER_TYPE_ID' => CCrmOwnerType::Deal, 'OWNER_ID' => $dealId],
            ['OWNER_TYPE_ID' => CCrmOwnerType::Company, 'OWNER_ID' => $companyId],
        ],
    ];
    CCrmActivity::Add($activityFields, true);

    echo Json::encode(['status' => 'success', 'message' => 'Письмо успешно отправлено']);
} else {
    echo Json::encode(['status' => 'error', 'message' => 'Ошибка при отправке письма']);
}
