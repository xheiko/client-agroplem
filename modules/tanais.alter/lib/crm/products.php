<?php

namespace Tanais\Alter\Crm;

use \Bitrix\Crm\Service;

class Products
{
    public static function ReplaceProductsFromCatalog($contractId, $discountRate, $iblockId, $fieldPriceId)
    {
        if (!$contractId || !$iblockId || !$fieldPriceId) {
            return;
        }

        $products = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                '>' . $fieldPriceId => 0,
            ],
            false,
            false,
            [
                'ID',
                'NAME',
                $fieldPriceId,
                'DETAIL_TEXT',
            ]);

        while ($product = $products->fetch()) {
            if ($discountRate < 0) {
                $price = intval($product[$fieldPriceId . '_VALUE']) + (intval($product[$fieldPriceId . '_VALUE']) * abs($discountRate) / 100);
            } elseif ($discountRate == 0) {
                $price = intval($product[$fieldPriceId . '_VALUE']);
            } else {
                $price = intval($product[$fieldPriceId . '_VALUE']) - (intval($product[$fieldPriceId . '_VALUE']) * $discountRate / 100);
            }
            $price = round($price, 0);

            $arProductID[] = [
                'PRODUCT_NAME' => $product['NAME'],
                'PRODUCT_ID' => $product['ID'],
                'PRICE' => $price,
                'QUANTITY' => 1,
                'TAX_INCLUDED' => 'Y',
            ];
        }

        \Bitrix\Main\Loader::requireModule('crm');
        if (is_array($arProductID)) {
            $container = Service\Container::getInstance();
            $factory = $container->getFactory(1050);
            $item = $factory->getItem($contractId);
            $item->setProductRowsFromArrays($arProductID);
            $updateOperation = $factory->getUpdateOperation($item);
            $operationResult = $updateOperation->launch();
        }
    }
}