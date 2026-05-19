// function execCalendar(arrEvents) {
//     document.addEventListener('DOMContentLoaded', function () {
//         var calendarEl = document.getElementById('calendar');
//
//         var calendar = new FullCalendar.Calendar(calendarEl, {
//             initialView: 'dayGridMonth', //'dayGridWeek', 'timeGridDay', 'listWeek'
//             height: 700,
//             locale: 'ru',
//             headerToolbar: {
//                 left: 'dayGridMonth,timeGridWeek,timeGridDay list',
//                 center: 'title',
//                 right: 'today prevYear,prev,next,nextYear',
//             },
//             eventDidMount: function (info) {
//                 var tooltip = new Tooltip(info.el, {
//                     title: info.event.extendedProps.description,
//                     placement: 'top',
//                     trigger: 'hover',
//                     container: 'body'
//                 });
//             },
//             eventDrop: function (info) {
//                 var request = BX.ajax.runAction('tanais:alter.crm.managercalendar.updatemanagervisitdate', {
//                     mode: 'class',
//                     data: {
//                         propCodeBegin: info.event.extendedProps['propCodeBegin'],
//                         propCodeEnd: info.event.extendedProps['propCodeEnd'],
//                         elementId: info.event.extendedProps['elementId'],
//                         dateTo: info.event.startStr,
//                         dateFrom: info.event.startStr,
//                     }
//                 });
//             },
//             events:
//             arrEvents
//
//         });
//         calendar.render();
//     })
//     ;
// }
function pad(n) {
    return (n < 10 ? '0' : '') + n;
}

// function formatDateTime(d) {
//     // d is Date
//     return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
//         ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
// }

function formatDateTime(d) {
    // d is Date
    return pad(d.getDate()) + '.' + pad(d.getMonth() + 1) + '.' + d.getFullYear() +
        ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
}

function execCalendar(arrEvents) {
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 700,
            locale: 'ru',

            // важно для drag/resize
            editable: true,
            eventResizableFromStart: true,

            headerToolbar: {
                left: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
                center: 'title',
                right: 'today prevYear,prev,next,nextYear',
            },

            // ГЛАВНОЕ: разрешаем перемещение только для тех событий, где canUpdate = true
            eventAllow: function (dropInfo, draggedEvent) {
                return !!draggedEvent.extendedProps.canUpdate;
            },

            eventDidMount: function (info) {
                new Tooltip(info.el, {
                    title: info.event.extendedProps.description,
                    placement: 'top',
                    trigger: 'hover',
                    container: 'body'
                });
            },

            eventDrop: function (info) {
                if (!info.event.extendedProps.canUpdate) {
                    info.revert();
                    return;
                }
                saveEventDates(info);
            },

            eventResize: function (info) {
                if (!info.event.extendedProps.canUpdate) {
                    info.revert();
                    return;
                }
                saveEventDates(info);
            },

            events: arrEvents
        });

        function saveEventDates(info) {
            var ev = info.event;

            // В timeGrid ev.end может быть null — тогда сохраняем только start
            var dateStart = ev.start ? formatDateTime(ev.start) : null;
            var dateEnd = ev.end ? formatDateTime(ev.end) : null;

            BX.ajax.runAction('tanais:alter.crm.managercalendar.updateManagerVisitDate', {
                mode: 'class',
                data: {
                    elementId: ev.extendedProps.elementId,
                    dateStart: dateStart,
                    dateEnd: dateEnd,
                    propCodeBegin: ev.extendedProps.propCodeBegin,
                    propCodeEnd: ev.extendedProps.propCodeEnd
                }
            }).then(function () {
                // ok
            }).catch(function () {
                // если сервер отказал (нет прав/ошибка) — возвращаем событие назад
                info.revert();
            });
        }

        calendar.render();
    });
}
