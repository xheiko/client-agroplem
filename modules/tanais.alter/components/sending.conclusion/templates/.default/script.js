document.addEventListener('DOMContentLoaded', function () {
    const checkbox = document.getElementById('linkCheckbox');
    const filesBox = document.getElementById('filesBox');
    const filesSend = document.getElementById('filesSend');
    const hideDomainCheckbox = document.getElementById('hideDomainCheckbox');
    const button = document.getElementById('btn_send');
    const form = document.getElementById('custom-send');

    // Данные, переданные из PHP через BX.message()
    const fileLinksHide = BX.message('FILE_LINKS_HIDE') || '';
    const fileLinksShow = BX.message('FILE_LINKS_SHOW') || '';

    const filesSendOriginalValue = filesSend ? filesSend.value : '';
    const iframeSelector = '.bxlhe-editor-cell iframe';

    // --- вспомогательные функции ---
    function updateEditorBody(newBodyHtml) {
        const $iframe = $(iframeSelector);
        const $body = $iframe.contents().find('body');
        const $input = $('.bxlhe-frame').find('input');
        if ($body.length) {
            $body.html(newBodyHtml);
            $input.val(newBodyHtml);
        }
    }

    function getEditorHtml() {
        const $iframe = $(iframeSelector);
        return $iframe.contents().find('body').html() || '';
    }

    function removeMailFiles(html) {
        return html.replace(/<div[^>]*id=["']?mailFiles["']?[^>]*>[\s\S]*?<\/div>/gi, '');
    }

    function insertMailFiles(html, linksHtml) {
        if (html.includes('<div id="signature"')) {
            return html.replace(
                /(<div id=["']signature["'][^>]*>)/i,
                `<div id="mailFiles" class="mailFiles">${linksHtml}</div>$1`
            );
        }
        return html + `<div id="mailFiles" class="mailFiles">${linksHtml}</div>`;
    }

    // --- основная функция проверки чекбоксов ---
    function checkFiles() {
        const currentHtml = getEditorHtml();
        let newHtml = currentHtml;

        if (checkbox && checkbox.checked) {
            // Отправить ссылками
            filesBox?.style.setProperty('display', 'none');
            if (filesSend) filesSend.value = '';
            const linksHtml = hideDomainCheckbox?.checked ? fileLinksHide : fileLinksShow;
            newHtml = removeMailFiles(newHtml);
            newHtml = insertMailFiles(newHtml, linksHtml);
        } else {
            // Отправить файлами
            filesBox?.style.setProperty('display', 'flex');
            if (filesSend) filesSend.value = filesSendOriginalValue;
            newHtml = removeMailFiles(newHtml);
        }

        updateEditorBody(newHtml);
    }

    if (checkbox) checkbox.addEventListener('change', checkFiles);
    if (hideDomainCheckbox) hideDomainCheckbox.addEventListener('change', checkFiles);

    // --- обработчик отправки письма ---
    $(form).on('submit', function (event) {
        event.preventDefault();

        if (!$(button).hasClass('calm')) return; // не повторяем отправку

        const formData = $(form).serialize();
        const resultContainer = $('#rezult pre');

        $(button).removeClass('calm').addClass('loading');

        $.ajax({
            url: '/local/components/tanais.alter/sending.conclusion/bitrix_send.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function (data) {
                $(button).removeClass('loading');

                if (data.status === 'success') {
                    $(button).addClass('ready');
                } else {
                    $(button).addClass('error');
                }

                if (resultContainer.length) {
                    $(resultContainer).html(data.message || JSON.stringify(data));
                }

                setTimeout(() => {
                    $(button).removeClass('ready error').addClass('calm');
                }, 3000);
            },
            error: function (xhr) {
                $(button).removeClass('loading').addClass('error');
                if (resultContainer.length) {
                    $(resultContainer).html(xhr.responseText);
                }
                setTimeout(() => {
                    $(button).removeClass('error').addClass('calm');
                }, 3000);
            }
        });
    });
});
