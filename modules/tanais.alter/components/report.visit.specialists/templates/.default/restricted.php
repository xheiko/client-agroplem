<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();
\Bitrix\Main\Page\Asset::getInstance()->addCss(str_replace(\Bitrix\Main\Application::getDocumentRoot(), '', __DIR__ . '/restricted.css'));


if (!$arParams['IFRAME']) {

    $APPLICATION->IncludeComponent(
        'bitrix:crm.control_panel',
        '',
        [
            'ID' => 'CUSTOM_REPORTS',
            'ACTIVE_ITEM_ID' => 'CUSTOM_REPORTS',
        ]
    );
}
?>
<div class="restricted-inner">
    <div class="restricted-inner-ico"><i class="fa-thin fa-face-worried"></i></div>
    <div class="restricted-inner-text">Извините, но доступ к данному функционалу запрещен</div>
    <div class="restricted-inner-component"><?= str_replace('tanais.', '', $arResult['COMPONENT_NAME']) ?></div>
</div>