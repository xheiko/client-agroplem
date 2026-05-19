<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CJSCore::Init(array('ajax', 'json', 'ls', 'session', 'jquery', 'popup', 'pull'));
global $USER, $APPLICATION;
$APPLICATION->ShowHeadStrings();
$APPLICATION->ShowHead();
\Bitrix\Main\UI\Extension::load('ui.entity-selector');
\Bitrix\Main\Loader::includeModule('disk');
\Bitrix\Main\Loader::includeModule('crm');
\Bitrix\Main\Loader::includeModule('iblock');
const MODULE_PATH = '/home/bitrix/www/local/modules/tanais.alter';


use Bitrix\Crm\Integration\Main\UISelector;
use \Bitrix\Crm\Service;

d($arResult);
$APPLICATION->SetAdditionalCss("/alter/crm/sending/conclusion/style.css");
?>
<script src="https://code.jquery.com/jquery-3.6.0.js"
        integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk="
        crossorigin="anonymous"></script>
<input type="hidden" name="from[]" value="0"/>
<div id="from_custom" name="from"></div>
<div class="mail">
    <form method="POST" action="" id="custom-send" enctype="multipart/form-data">
        <div class="type">
            <div class="text help">
                <span><?= $arResult["TYPE"] ?></span>
                <input type="hidden" name="smartId" value="<?= $arResult["SMART_ID"] ?>">
                <input type="hidden" name="dealId" value="<?= $arResult["DEAL_ID"] ?>">
                <input type="hidden" name="companyId" value="<?= $arResult['COMPANY_ID'] ?>">
                <input type="hidden" name="idSmartProcessElement" value="<?= $arResult["SMART_ID"] ?>">
            </div>
        </div>
        <?php if ($arResult['LAB'] == 'all') { ?>
            <div class="pattern">
                <span for="" class="pattern">Шаблон&nbsp;</span>
                <select id="select-pattern" name="pattern" onchange="changeFunc();">
                    <option class="set" id='1' value="1">Итоговое заключение</option>
                    <option class="set" id='2' value="2">Предварительное заключение</option>
                    <option class="set" id='3' value="3">Повторное Предварительное заключение</option>
                </select>
            </div>
        <?php } ?>
        <div class="body">
            <div class="header from ">
                <span for="" class="title">От кого:&nbsp;</span>
                <?php //$APPLICATION->IncludeComponent('bitrix:main.user.selector', ' ', $inputParamsFrom,);
                ?>
                <div class="wrap-title__input">
                    <span class="value2"><span><?= $arResult["FROM_NAME"] ?> [<?= $arResult["FROM_EMAIL"] ?>]</span></span>
                    <input type="hidden" name="from" value="<?= $arResult["FROM_EMAIL"] ?>">
                </div>
            </div>
            <br>

            <div class="header from ">
                <?php //implode(', ', $contactsEmailHTML)
                ?>
                <span for="" class="title">Кому:&nbsp;</span>
                <?php
                $APPLICATION->IncludeComponent('bitrix:main.user.selector', ' ', $arResult["SELECTOR_TO_LIST"]);
                ?>
            </div>
            <br>

            <?php if (!empty($toCopy)): ?>
                <div class="header from ">
                    <span for="" class="title">Копия:&nbsp;</span>
                    <span class="value"><span><?= implode(', ', $toCopy) ?></span></span>
                </div><br>
            <?php endif; ?>

            <?php if (!empty($arResult["SELECTOR_BCC_LIST"])): ?>
                <div class="header from ">
                    <span for="" class="title">С.Копия:&nbsp;</span>
                    <?php
                    $APPLICATION->IncludeComponent('bitrix:main.user.selector', ' ', $arResult["SELECTOR_BCC_LIST"]); ?>
                </div><br>
            <?php endif; ?>

            <div class="header from ">
                <span class="title">Тема:&nbsp;</span>
                <div class="wrap-title__input">
                    <input name="title" type="text" class="theme" value="<?= htmlspecialcharsbx($arResult['TOPIC']) ?>">
                </div>
            </div>

            <div class="body-text">
                <script>
                    const emailTemplates = <?= \Bitrix\Main\Web\Json::encode([
                        '1' => $arResult['EMAIL_BODIES']['gen_final'] ?? '',
                        '2' => $arResult['EMAIL_BODIES']['gen_pre'] ?? '',
                        '3' => $arResult['EMAIL_BODIES']['gen_pre_repeat'] ?? ''
                    ]) ?>;

                    function changeFunc() {
                        const select = document.getElementById("select-pattern");
                        const selectedValue = select.value;

                        if (!emailTemplates[selectedValue]) return;

                        const newContent = emailTemplates[selectedValue];
                        const $iframeBody = $('.bxlhe-editor-cell').find('iframe').contents().find('body');
                        const $hiddenInput = $('.bxlhe-frame').find('input');

                        $iframeBody.html(newContent);
                        $hiddenInput.val(newContent);
                    }
                </script>
                <br>
                <?php
                $APPLICATION->IncludeComponent(
                    "bitrix:fileman.light_editor",
                    "",
                    array(
                        "CONTENT" => $arResult['EMAIL_BODIES']['default'],
                        "INPUT_NAME" => "bodysend",
                        "INPUT_ID" => "bodysend",
                        "WIDTH" => "100%",
                        "HEIGHT" => "300px",
                        "RESIZABLE" => "Y",
                        "AUTO_RESIZE" => "Y",
                        "VIDEO_ALLOW_VIDEO" => "Y",
                        "VIDEO_MAX_WIDTH" => "640",
                        "VIDEO_MAX_HEIGHT" => "480",
                        "VIDEO_BUFFER" => "20",
                        "VIDEO_LOGO" => "",
                        "VIDEO_WMODE" => "transparent",
                        "VIDEO_WINDOWLESS" => "Y",
                        "VIDEO_SKIN" => "/bitrix/components/bitrix/player/mediaplayer/skins/bitrix.swf",
                        "USE_FILE_DIALOGS" => "Y",
                        "ID" => "",
                        "JS_OBJ_NAME" => "bodysend"
                    )
                );
                ?>
            </div>
            <br>
            <?php if (!empty($arResult['FILE_SIZE_TOTAL'])) { ?>
                <div class="files help" id="filesBox">
                    <span for="" class="title">Вложенные файлы:&nbsp;</span>
                    <span class="value"><?= $arResult['FILE_ORIGINAL_NAME'] ?></span>
                    <input name="files" id="filesSend" type="hidden"
                           value="<?= htmlentities(serialize($arResult['FILE_IDS'])) ?>">
                </div>
                <br>
                <input type="checkbox" id="linkCheckbox" name="linkCheckbox">
                <label for="linkCheckbox">Отправить файлы ссылками</label><br><br>
                <input type="checkbox" id="hideDomainCheckbox" name="hideDomainCheckbox" checked="checked" disabled>
                <label for="hideDomainCheckbox">Скрыть домен agrochemist.ru для файлов отправленных ссылками</label>
            <?php } ?>
        </div>
        <?php
        // if ($fileSize > 16777216) {
        //if ($fileSize > 9800000) {
        //
        ?>
        <!--                <div>-->
        <!--                    <button disabled id='btn_send' type='submit' class="button send calm"-->
        <!--                            style="background-color: gray; display:inline;">-->
        <!--                        <span class="calm">Отправить</span>-->
        <!--                    </button>-->
        <!--                    <span class="value2" style="font-weight: bold;">Размер приложенных к письму файлов превышает возможности почтового сервиса Unisender. Сообщение не может быть отправлено</span>-->
        <!--                </div>-->
        <?php //} else {
        ?>
        <button id='btn_send' type='submit' class="button send calm">
            <span class="calm">Отправить</span>
            <span class="loading"><img src="/crm-webhook/sendgrid-php-send-mail/spinning-circles.svg"></span>
            <span class="ready">Отправлено</span>
            <span class="error">Ошибка</span>
        </button>
        <?php // }
        ?>
    </form>
</div>
<script>
    BX.message({
        FILE_LINKS_HIDE: '<?= CUtil::JSEscape($arResult["FILE_LINKS_HIDE"] ?? "") ?>',
        FILE_LINKS_SHOW: '<?= CUtil::JSEscape($arResult["FILE_LINKS_SHOW"] ?? "") ?>'
    });
</script>
<?php
$APPLICATION->AddHeadScript($this->__path . '/script.js');
?>
