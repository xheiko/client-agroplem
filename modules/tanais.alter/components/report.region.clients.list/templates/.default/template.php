<?php
defined('B_PROLOG_INCLUDED') || die;
global $APPLICATION;

Bitrix\Main\Page\Asset::getInstance()->addJs("/local/modules/tanais.alter/js/webdata/webdatarocks.toolbar.min.js");
Bitrix\Main\Page\Asset::getInstance()->addJs("/local/modules/tanais.alter/js/webdata/webdatarocks.js");
Bitrix\Main\Page\Asset::getInstance()->addCss("/local/modules/tanais.alter/js/webdata/webdatarocks.min.css");
?>

<div id="wdr-component"></div>

<script>
    window.CLIENTS_REPORT_DATA = <?= $arResult['LIST']['DATA'] ?>;

</script>

<script src="<?= $templateFolder ?>/script.js"></script>