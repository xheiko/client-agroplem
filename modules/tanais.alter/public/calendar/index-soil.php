<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use Bitrix\Main\Page\Asset,
    Tanais\Alter\ProbeCalendar;

const MODULE_PATH = '/home/bitrix/www/local/modules/tanais.alter';

Asset::getInstance()->addJs("/alter/calendar/js/fullcalendar/main.min.js");
Asset::getInstance()->addJs("/alter/calendar/js/fullcalendar/ru.js");
Asset::getInstance()->addJs("/alter/calendar/js/popper/popper.min.js");
Asset::getInstance()->addJs("/alter/calendar/js/tooltip/tooltip.min.js");
Asset::getInstance()->addJs("/alter/calendar/js/custom.js");

Asset::getInstance()->addCss("/alter/calendar/css/style.css");

Asset::getInstance()->addString(" <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.css' rel='stylesheet'/>");

$APPLICATION->IncludeComponent(
    'bitrix:crm.control_panel',
    '',
    array(
        'ID' => 'PROBE_CALENDAR_SOIL',
        'ACTIVE_ITEM_ID' => 'PROBE_CALENDAR_SOIL',
    )
);

$events = ProbeCalendar::getParamsForTable();
$arrEvents = ProbeCalendar::generateInfoForSoilTable();
?>

    <div id='calendar'></div>
    <br>
    <p>Обозначения:</p>
    <div class="faq">
        <?php foreach ($events as $code => $arr): ?>
            <p class="data" style="background-color: <?= $arr['color'] ?>;"><?= $arr['name'] ?> <b>{<?= $code ?>}</b>
            </p>
            <p class="comment"><?= $arr['name'] ?> Комментарий <b>{<?= $arr['comment'] ?>}</b></p>
        <?php endforeach; ?>
    </div>

    <script>
        execCalendar(<?=json_encode($arrEvents)?>);
    </script>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>