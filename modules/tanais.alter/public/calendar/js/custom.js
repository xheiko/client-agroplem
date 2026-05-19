function execCalendar(arrEvents) {
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');

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
            eventDrop: function(info) {
                var request = BX.ajax.runComponentAction('custom:ajax', 'updateDealUfDate', {
                    mode:'class',
                    data: {
                        propCode: info.event.extendedProps['propCode'],
                        index: info.event.extendedProps['propCodeIndex'],
                        leadId: info.event.extendedProps['leadId'],
                        dateTo: info.event.startStr,
                    }
                });
            },
            events: arrEvents

        });
        calendar.render();
    });
}
