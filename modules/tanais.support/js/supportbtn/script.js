(function (w, d) {
    var cachedFullName = null;
    var moduleId = "tanais:support.support";

    // Функция получения ФИО через BX.ajax.runAction
    function getUserFullNameFromServer(callback) {
        if (cachedFullName) {
            callback(cachedFullName);
            return;
        }

        if (window.BX && BX.ajax && BX.ajax.runAction) {
            BX.ajax
                .runAction(moduleId + ".getDataFromServer", {
                    data: {}, // CSRF-токен добавляется автоматически
                })
                .then(function (response) {
                    if (response && response.data && response.data.fullName) {
                        cachedFullName = response.data.fullName;
                        callback(cachedFullName);
                    } else {
                        callback("Сотрудник");
                    }
                })
                .catch(function (error) {
                    callback("Сотрудник");
                });
        } else {
            callback("Сотрудник");
        }
    }

    // Функция получения имени сервера
    function getServerName() {
        return window.location.hostname;
    }

    // Функция открытия ссылки
    function openSupportLink() {
        var serverName = encodeURIComponent(getServerName());

        getUserFullNameFromServer(function (fullName) {
            openUrl(serverName, encodeURIComponent(fullName));
        });
    }

    function openUrl(serverName, fullName) {
        var url = "https://corp.tanais.ru/online/b24solutions";
        // url += "?SERVER_NAME=" + serverName;
        // url += "&USER_NAME=" + fullName;
        window.open(url, "_blank");
    }

    // Функция добавления кнопки
    function addSupportButton() {
        var container = d.querySelector(".air-header__buttons");
        if (!container || d.querySelector('[data-id="tanaissuppurtbtn"]')) return false;

        var btnWrapper = d.createElement("div");
        btnWrapper.className = "air-header__button";
        btnWrapper.setAttribute("data-id", "tanaissuppurtbtn");
        btnWrapper.innerHTML = '<button class="air-header-button"><span class="air-header-button__text">Поддержка</span><span class="air-header-button__counter"></span></button>';

        var button = btnWrapper.querySelector("button");
        button.addEventListener("click", openSupportLink);

        var licenseBtn = d.querySelector('[data-id="licenseWidgetWrapper"]');
        container.insertBefore(btnWrapper, licenseBtn || container.firstChild);
        
        return true;
    }

    // Функция для запуска observer (с проверкой наличия body)
    function initObserver() {
        // Проверяем, существует ли body
        if (!d.body) {
            // Если body нет, ждем загрузку DOM
            if (d.readyState === 'loading') {
                d.addEventListener('DOMContentLoaded', initObserver);
            }
            return;
        }

        // Сначала пробуем добавить кнопку без observer
        if (addSupportButton()) {
            return;
        }

        // Если кнопка не добавилась, запускаем observer
        var observer = new MutationObserver(function (mutations) {
            if (d.querySelector(".air-header__buttons") && !d.querySelector('[data-id="tanaissuppurtbtn"]')) {
                if (addSupportButton()) {
                    observer.disconnect();
                }
            }
        });

        observer.observe(d.body, {
            childList: true,
            subtree: true,
        });
    }

    // Запускаем инициализацию
    initObserver();
})(window, document);