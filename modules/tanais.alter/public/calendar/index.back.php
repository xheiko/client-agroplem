<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
use Bitrix\Main\Page\Asset;
use \Bitrix\Crm\Service;


$APPLICATION->SetTitle("Календарь проб");

\Bitrix\Main\Loader::includeModule('crm');

$APPLICATION->IncludeComponent(
    'bitrix:crm.control_panel',
    '',
    array(
        'ID' => 'PROBE_CALENDAR',
        'ACTIVE_ITEM_ID' => 'PROBE_CALENDAR',
    )
);
$userId = $USER->GetID();

$container = Service\Container::getInstance();
$factory = $container->getFactory( 139 );
$elements = $factory->getItemsFilteredByPermissions(
    [
        'filter' => [
        ]
    ],
    $userId
);

foreach ($elements as $element)
{
    $leadList[$element->getId()] = [
        'ID' => $element->getId(),
        'TITLE' => $element->getTitle(),
        'COMPANY_ID' => $element->get('COMPANY_ID'),
        'UF_CRM_7_TYPE' => $element->get('UF_CRM_7_TYPE'),
        'UF_CRM_7_BEGIN_TIME' => $element->get('UF_CRM_7_BEGIN_TIME'),
        'UF_CRM_7_END_TIME' => $element->get('UF_CRM_7_END_TIME'),
    ];

    $arrEvents[] = [
        'title' => $element->getTitle(),
        'start' => date('Y-m-d'),
        'color' => '#007dff',
        'display' => "list-item",
        'editable' => true,
        'classNames' => "lead",
        'url' => "/page/servisnyy_vizit/servisnyy_vizit/type/139/details/{$element->getId()}/"
    ];
}



$events = [
    'COMPANY_ID' => [
        'name' => 'Название компании',
        'style' => 'DateAdmissionToTheLaboratory',
        'color' => '#007dff',
        'dopParam' => 'UF_CRM_1573029329429',
        'prefix' => 'Пробы ',
        'comment' => 'UF_CRM_1650887152877',
    ],
    'UF_CRM_7_TYPE' => [
        'name' => 'Категория визита',
        'style' => 'DateCA',
        'color' => '#3caa3c',
        'dopParam' => false,
        'prefix' => 'КД ',
        'comment' => 'UF_CRM_1576149967633',
    ],
    'UF_CRM_7_BEGIN_TIME' => [
        'name' => 'Время начала визита',
        'style' => 'DateOfDispatchOfBags',
        'color' => '#f4a900',
        'dopParam' => 'UF_CRM_1608120843231',
        'prefix' => 'Сумки ',
        'comment' => 'UF_CRM_1654767465',
    ],
    'UF_CRM_7_END_TIME' => [
        'name' => 'Время завершения визита',
        'style' => 'DateOfDispatchOfBagsMulti',
        'color' => '#f4a900',
        'dopParam' => 'UF_CRM_1658736818',
        'prefix' => 'Сумки ',
        'comment' => 'UF_CRM_1656084725',
    ],
];

d($leadList);

Asset::getInstance()->addJs("https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.js");
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/styles/fix.css");
Asset::getInstance()->addString(" <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.css' rel='stylesheet'/>");
?>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.css' rel='stylesheet'/>
    <script type="text/javascript" src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.js'></script>
    <script type="text/javascript" src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/locales/ru.js'></script>

    <script src="https://unpkg.com/popper.js/dist/umd/popper.min.js"></script>
    <script src="https://unpkg.com/tooltip.js/dist/umd/tooltip.min.js"></script>

    <style type="text/css">
        #calendar {
            font-family: OpenSans-Regular, "Helvetica Neue", Arial, Helvetica, sans-serif;
        }

        .popper,
        .tooltip {
            position: absolute;
            z-index: 9999;
            background: #6b7280;
            color: white;
            width: 150px;
            border-radius: 3px;
            box-shadow: 0 0 2px rgba(0, 0, 0, 0.5);
            padding: 10px;
            text-align: center;
        }

        .popper .popper__arrow,
        .tooltip .tooltip-arrow {
            width: 0;
            height: 0;
            border-style: solid;
            position: absolute;
            margin: 5px;
        }

        .tooltip .tooltip-arrow,
        .popper .popper__arrow {
            border-color: #6b7280;
        }

        .popper[x-placement^="top"],
        .tooltip[x-placement^="top"] {
            margin-bottom: 5px;
        }

        .popper[x-placement^="top"] .popper__arrow,
        .tooltip[x-placement^="top"] .tooltip-arrow {
            border-width: 5px 5px 0 5px;
            border-left-color: transparent;
            border-right-color: transparent;
            border-bottom-color: transparent;
            bottom: -5px;
            left: calc(50% - 5px);
            margin-top: 0;
            margin-bottom: 0;
        }

        .popper[x-placement^="bottom"],
        .tooltip[x-placement^="bottom"] {
            margin-top: 5px;
        }

        .tooltip[x-placement^="bottom"] .tooltip-arrow,
        .popper[x-placement^="bottom"] .popper__arrow {
            border-width: 0 5px 5px 5px;
            border-left-color: transparent;
            border-right-color: transparent;
            border-top-color: transparent;
            top: -5px;
            left: calc(50% - 5px);
            margin-top: 0;
            margin-bottom: 0;
        }

        .tooltip[x-placement^="right"],
        .popper[x-placement^="right"] {
            margin-left: 5px;
        }

        .popper[x-placement^="right"] .popper__arrow,
        .tooltip[x-placement^="right"] .tooltip-arrow {
            border-width: 5px 5px 5px 0;
            border-left-color: transparent;
            border-top-color: transparent;
            border-bottom-color: transparent;
            left: -5px;
            top: calc(50% - 5px);
            margin-left: 0;
            margin-right: 0;
        }

        .popper[x-placement^="left"],
        .tooltip[x-placement^="left"] {
            margin-right: 5px;
        }

        .popper[x-placement^="left"] .popper__arrow,
        .tooltip[x-placement^="left"] .tooltip-arrow {
            border-width: 5px 0 5px 5px;
            border-top-color: transparent;
            border-right-color: transparent;
            border-bottom-color: transparent;
            right: -5px;
            top: calc(50% - 5px);
            margin-left: 0;
            margin-right: 0;
        }

        .lead .fc-event-title {
            color: #535c69;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');

            <?
            /*
                    var Draggable = FullCalendar.Draggable;
                    new Draggable(calendarEl, {
                    itemSelector: '.fc-event',
                    eventData: function(eventEl) {
                            console.log(eventEl);
                    }
                  });
            */
            ?>

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth', //'dayGridWeek', 'timeGridDay', 'listWeek'
                height: 700,
                locale: 'ru',
                headerToolbar: {
                    left: 'dayGridMonth,timeGridWeek,timeGridDay list',
                    center: 'title',
                    right: 'today prevYear,prev,next,nextYear',
                },
                eventDidMount: function (info) {
                    var tooltip = new Tooltip(info.el, {
                        title: info.event.extendedProps.description,
                        placement: 'top',
                        trigger: 'hover',
                        container: 'body'
                    });
                },
                <?

                function event($lead, $code, $arr, $startDate, $key)
                {
                    $event['title'] = '';
                    if (!empty($arr['prefix'])) {
                        $event['title'] .= $arr['prefix'];
                    }
                    $event['title'] .= $lead['TITLE'];
                    if (!empty($arr['dopParam'])) {
                        if (empty($lead[$arr['dopParam']])) {
                            $event['title'] .= ' [не указано]';
                        } else {
                            $dopParam = $lead[$arr['dopParam']];
                            if (is_array($dopParam)) {
                                if (!empty($dopParam[$key])) {
                                    $event['title'] .= ' [' . $dopParam[$key] . ']';
                                }
                            } else {
                                if (!empty($dopParam)) {
                                    $event['title'] .= ' [' . $dopParam . ']';
                                }
                            }
                        }
                    }
                    $comment = $lead[$arr['comment']];
                    if (is_array($comment)) {
                        if (!empty($comment[$key])) {
                            $event['title'] .= ' - ' . $comment[$key] . '';
                        }
                    } else {
                        if (!empty($comment)) {
                            $event['title'] .= ' - ' . $comment . '';
                        }
                    }

                    $event['start'] = date('Y-m-d', strtotime($startDate));
                    $event['color'] = $arr['color'];
                    $event['display'] = 'list-item';
                    $event['editable'] = 'true';
                    $event['classNames'] = 'lead';
                    $event['url'] = '/crm/lead/details/' . $lead['ID'] . '/';
                    return $event;
                }
                $calevents = [];
                foreach ($leadList as $lead) {
                    foreach ($events as $code => $arr) {
                        if (!empty($lead[$code])) {
                            if (is_array($lead[$code])) {
                                foreach ($lead[$code] as $key => $c) {
                                    $event = event($lead, $code, $arr, $c, $key);
                                    $calevents[] = $event;
                                }
                            } else {
                                $event = event($lead, $code, $arr, $lead[$code], false);
                                $calevents[] = $event;
                            }
                        }
                    }
                }
                ?>
                events: <?=json_encode($arrEvents)?>

            });
            calendar.render();
        });
    </script>

    <div id='calendar'></div>

    <br>

    <style type="text/css">
        .faq .data {
            padding: 5px;
            color: white;
            margin-bottom: 4px;
        }

        .faq .comment {
            padding: 1px 0px 8px 4px;
            margin-top: 4px;
        }
    </style>
    <p>Обозначения:</p>
    <div class="faq">
        <? foreach ($events as $code => $arr): ?>
            <p class="data" style="background-color: <?= $arr['color'] ?>;"><?= $arr['name'] ?> <b>{<?= $code ?>}</b>
            </p>
            <p class="comment"><?= $arr['name'] ?> Комментарий <b>{<?= $arr['comment'] ?>}</b></p>
        <? endforeach; ?>
    </div>

<?
if ($USER->IsAdmin()) {
    //d($leadList[3423]);
    //d($debugarr);
    d($calevents);
}
?>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>