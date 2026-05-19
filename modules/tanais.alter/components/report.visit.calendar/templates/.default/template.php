<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

\Bitrix\UI\Toolbar\Facade\Toolbar::addFilter([
    'FILTER_ID' => $arParams['GRID_ID'],
    'GRID_ID' => $arParams['GRID_ID'],
    'FILTER' => $arResult['FILTER_UI'],
    'VALUE_REQUIRED' => true,
    'VALUE_REQUIRED_MODE' => true,
    'RESET_TO_DEFAULT_MODE' => true,
    'ENABLE_LIVE_SEARCH' => true,
    'ENABLE_LABEL' => true
]);
?>
<div id='calendar'></div>
<br>
<!--<p>Обозначения:</p>-->
<div class="faq">
    <? foreach ($arResult['EVENTS'] as $code => $arr): ?>
        <p class="data" style="background-color: <?= $arr['color'] ?>;"><?= $arr['name'] ?> <b>{<?= $code ?>}</b>
        </p>
    <? endforeach; ?>
</div>

<script>
    execCalendar(<?=json_encode($arResult['AR_EVENTS'])?>);
    BX.ready(function () {
        BX.addCustomEvent("BX.Main.Filter:apply", function (id, data, ctx) {
            location.reload();
        });
    });
</script>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
