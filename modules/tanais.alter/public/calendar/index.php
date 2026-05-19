<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use Bitrix\Main\Page\Asset;

\Bitrix\Main\Loader::includeModule('crm');

const MODULE_PATH = '/home/bitrix/www/local/modules/tanais.alter';

Asset::getInstance()->addJs("/alter/calendar/js/fullcalendar/main.min.js");
Asset::getInstance()->addJs("/alter/calendar/js/fullcalendar/ru.js");
Asset::getInstance()->addJs("/alter/calendar/js/popper/popper.min.js");
Asset::getInstance()->addJs("/alter/calendar/js/tooltip/tooltip.min.js");
Asset::getInstance()->addJs("/alter/calendar/js/custom.js");

Asset::getInstance()->addCss("/alter/calendar/css/style.css");

Asset::getInstance()->addString(" <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.css' rel='stylesheet'/>");

$lab_id = $_REQUEST['lab'];

if ($lab_id == 800) {
    $APPLICATION->SetTitle("Календарь проб Москва");
    $APPLICATION->IncludeComponent(
        'bitrix:crm.control_panel',
        '',
        array(
            'ID' => 'PROBE_CALENDAR',
            'ACTIVE_ITEM_ID' => 'PROBE_CALENDAR_MSK',
        )
    );
} elseif ($lab_id == 811) {
    $APPLICATION->SetTitle("Календарь проб Екатеринбург");
    $APPLICATION->IncludeComponent(
        'bitrix:crm.control_panel',
        '',
        array(
            'ID' => 'PROBE_CALENDAR',
            'ACTIVE_ITEM_ID' => 'PROBE_CALENDAR_EKB',
        )
    );
}


$events = [
    'UF_CRM_632046BBB65E4' => [
        'parent' => "UF_CRM_632046BBB65E4",
        'name' => 'Дата поступления в лабораторию',
        'style' => 'DateAdmissionToTheLaboratory',
        'color' => '#007dff',
        'dopParam' => false,
        'prefix' => 'Пробы ',
        'comment' => 'UF_CRM_5DCBE3F96EEA7',
    ],
    'UF_CRM_1573737968714' => [
        'parent' => "UF_CRM_1573737968714",
        'name' => 'Дата КД',
        'style' => 'DateCA',
        'color' => '#3caa3c',
        'dopParam' => false,
        'prefix' => 'КД ',
        'comment' => false,
    ],
    'UF_CRM_6322DF3779F7E' => [
        'parent' => "UF_CRM_6322DF3779F7E",
        'name' => 'Дата отправки сумок (множ)',
        'style' => 'DateOfDispatchOfBagsMulti',
        'color' => '#f4a900',
        'dopParam' => 'UF_CRM_BAG_COUNT',
        'prefix' => 'Сумки ',
        'comment' => 'UF_CRM_6322DF38D513B',
    ]
];

$arSelect = [
    'ID',
    'TITLE',
    'COMPANY_TITLE',
    'CONTACT_FULL_NAME',
    "UF_CRM_632046BBB65E4", //дата поступления в лабораторию
    "UF_CRM_1573737968714", //Дата КД
    "UF_CRM_6322DF3779F7E", // Суцмки дата отправки
    "UF_CRM_5DCBE3F96EEA7", // проб запланировано
    "UF_CRM_6128FAF3AD9B7", // дата КД комментарий
    'UF_CRM_1618824524349', //Площадка ·
    'UF_CRM_BAG_COUNT', //Количество сумок (множ)
    "UF_CRM_6322DF38D513B" //сумки комментарий
];

$arFilter = [
    ["LOGIC" => "OR",
        ["!UF_CRM_632046BBB65E4" => null],
        ["!UF_CRM_1573737968714" => null],
        ["!UF_CRM_6322DF3779F7E" => null],
    ],
   // 'STAGE_ID' => ['C9:1', 'C1:1'],
    'UF_CRM_LABORATORY' => $lab_id,

];

// var_export($arFilter);
// var_export($arFilter);

$arrEvents = [];
$resultDealsList = CCrmDeal::GetListEx([], $arFilter, false, false, $arSelect);
while ($deal = $resultDealsList->Fetch()) {
    $dealList[$deal['ID']] = $deal;

    foreach ($events as $code => $event) {
        $clientTitle = $deal['COMPANY_TITLE'] ? $deal['COMPANY_TITLE'] : $deal['CONTACT_FULL_NAME'];
        $prefix = "";
        if (!empty($deal[$code])) {
            if (!empty($event['dopParam'])) {
                $prefix = "[Не указано]";
            }
            $arrEvents[] = [
                'title' => $event['prefix'] . $clientTitle . ' ' . $deal['UF_CRM_1618824524349'] . ' ' . ($deal[$event['dopParam']] ? '[' . $deal[$event['dopParam']] . ']' : $prefix) . (!$deal[$event['comment']] ? "" : '[' . $deal[$event['comment']] . ']'),
                'description' => $deal[$event['comment']],
                'start' => !is_array($deal[$code]) ? date('Y-m-d', strtotime($deal[$code])) : strtotime(current($deal[$code])),
                'color' => $event['color'],
                'display' => "list-item",
                'editable' => true,
                'classNames' => "lead",
                'url' => "/crm/deal/details/{$deal['ID']}/",
                'leadId' => $deal['ID'],
                'propCode' => $code,
            ];
        }
    }
}

// Хак для множественного типа поля Дата
if ($dealList) {
    $multipleDateFromDeal = array_column($dealList, "UF_CRM_6322DF3779F7E", 'ID');

    foreach ($multipleDateFromDeal as $dealId => $dates) {
        $i = -1;
        foreach ($dates as $date) {
            $i++;
            $prefix = "";

            $clientTitle = $dealList[$dealId]['COMPANY_TITLE'] ? $dealList[$dealId]['COMPANY_TITLE'] : $dealList[$dealId]['CONTACT_FULL_NAME'];

            if (!empty($leadList[$dealId][$events['UF_CRM_6322DF3779F7E']['dopParam']][$i])) {
                $prefix = "[Не указано]";
            }
            if (!empty($dates)) {
                $arrEvents[] = [
                    'title' => $events['UF_CRM_6322DF3779F7E']['prefix'] . $clientTitle . ' ' . $dealList[$dealId]['UF_CRM_1618824524349'] . ' ' . ($dealList[$dealId][$events['UF_CRM_6322DF3779F7E']['dopParam']][$i] ? '[' . $dealList[$dealId][$events['UF_CRM_6322DF3779F7E']['dopParam']][$i] . ']' : $prefix) . (!$dealList[$dealId][$events['UF_CRM_6322DF3779F7E']['comment']][$i] ? "" : '[' . $dealList[$dealId][$events['UF_CRM_6322DF3779F7E']['comment']][$i] . ']'),
                    'start' => !is_array($date) ? date('Y-m-d', strtotime($date)) : "",
                    'color' => $events['UF_CRM_6322DF3779F7E']['color'],
                    'display' => "list-item",
                    'editable' => true,
                    'classNames' => "lead",
                    'description' => $dealList[$dealId][$events['UF_CRM_6322DF3779F7E']['dopParam']][$i] . ' ' . $dealList[$dealId][$events['UF_CRM_6322DF3779F7E']['comment']][$i],
                    'url' => "/crm/deal/details/{$dealList[$dealId]['ID']}/",
                    'leadId' => $dealList[$dealId]['ID'],
                    'propCode' => 'UF_CRM_6322DF3779F7E',
                    'propCodeIndex' => $i
                ];
            }
        }
    }

    ?>
    <div id='calendar'></div>
    <br>
    <p>Обозначения:</p>
    <div class="faq">
        <? foreach ($events as $code => $arr): ?>
            <p class="data" style="background-color: <?= $arr['color'] ?>;"><?= $arr['name'] ?> <b>{<?= $code ?>}</b>
            </p>
            <p class="comment"><?= $arr['name'] ?> Комментарий <b>{<?= $arr['comment'] ?>}</b></p>
        <? endforeach; ?>
    </div>

    <script>
        execCalendar(<?=json_encode($arrEvents)?>);
    </script>
    <?
} else
    echo "Пусто";
?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>