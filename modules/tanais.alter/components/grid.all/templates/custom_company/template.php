<?php

/**
 * @var $arParams
 * @var $arResult
 */

use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text;
use Bitrix\Main\UI\Extension;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

function sum($elems, $prop)
{
//    $result = [];

    foreach ($elems as $elem) {
        $result += $elem[$prop];
    }
    return $result;
}

/**
 * Кол-во дней согласования
 */
$arSelect = [
    "ID",
    "NAME",
    "PROPERTY_SROK_SOGLASOVANIYA"

];

Extension::load([
    'ui.design-tokens',
    'ui.fonts.opensans',
    'popup',
    'ui',
    'resize_observer',
    'loader',
    'ui.actionpanel',
    'ui.fonts.opensans',
    'ui.buttons',
    'dnd',
    'ui.hint',
    'ui.cnt',
    'ui.label',
    'ui.layout-form',
]);

global $APPLICATION, $USER;

$gridClasses = ['main-grid'];
if ($arResult["IS_AJAX"]) {
    $gridClasses[] = 'main-grid-load-animation';
}
//d($arParams['ROWS_CUSTOM']);
?>
<div class="<?= join(' ', $gridClasses) ?>" id="<?= $arParams["GRID_ID"] ?>"
     data-ajaxid="<?= $arParams["AJAX_ID"] ?>"<?= $arResult['IS_AJAX'] ? " style=\"display: none;\"" : "" ?>><?php
    ?>
    <form name="form_<?= $arParams["GRID_ID"] ?>" action="<?= POST_FORM_ACTION_URI; ?>" method="POST"><?php
        ?><?= bitrix_sessid_post() ?>
        <table class="custom">
            <tr>
                <?php foreach ($arParams['COLUMNS'] as $col) { ?>
                    <?php  if($col['id']=='COMPANY'){
                        continue;
                    }?>
                    <td><b><?=$col['name']?></b></td>
                <?php } ?>
            </tr>

            <?php
            foreach ($arParams['ROWS_CUSTOM'] as $titleDoc => $document) {
                $countDeals = current($document);
                $countNomenclatures = sum($document, 'COUNT_NOMENCLATURES');
                $countProbes = sum($document, 'COUNT_PROBE');
                $amountDeals = sum($document, 'AMOUNT_DEALS');
                ?>
                <tbody>
                <tr class="parent">
                    <td class="arrow"><?= $titleDoc ?><i> > </i></td>
                    <td><b><?=$countDeals['COUNT_DEALS']?></b></td>
                    <td><b><?=$countNomenclatures?></b></td>
                    <td><b><?=number_format($countProbes, 0, ',', ' ')?></b></td>
                    <td><?=number_format($amountDeals, 2, ',', ' ')?></td>
                </tr>
                <?php foreach ($document as $company):
                    $elem = 0;
                    ?>
                    <tr class="child" data-id="">
                        <td class="arrow"><?= $company['TITLE'] ?></td>
                        <td class="arrow"><?= number_format($company['COUNT_DEALS_NOMENCLATURE'], 0, ',', ' ') ?></td>
                        <td class="arrow"><?= $company['COUNT_NOMENCLATURES'] ?></td>
                        <td class="arrow"><?= number_format($company['COUNT_PROBE'], 0, ',', ' ')?></td>
                        <td class="arrow"><?= number_format($company['AMOUNT_DEALS'], 2, ',', ' ')?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            <?php } ?>

        </table>
    </form>