<?php

namespace Tanais\Alter\Controller\Crm;

use \Bitrix\Main\Error;

class CopyPrice extends \Bitrix\Main\Engine\Controller
{
    public function copyPriceAction(): ?array
    {
        $prices = \Bitrix\Catalog\PriceTable::getList([
            'select' => ['PRICE', 'PRODUCT_ID',],
            'filter' => ['>PRICE' => 0],
        ]);

        while ($price = $prices->fetch()) {
            $arPrice[$price['PRODUCT_ID']] = $price['PRICE'];
        }

        $products = \CIBlockElement::GetList([],
            [
                'IBLOCK_ID' => \Tanais\Alter\Crm\Catalog::PRODUCT_IBLOCK_ID,
                'ACTIVE' => 'Y',
            ],
            false,
            false,
            ['ID', 'PROPERTY_78']
        );
        $count = 0;
        while ($product = $products->fetch()) {
            if (!empty($arPrice[$product['ID']]) && (int)$product['PROPERTY_78_VALUE'] != (int)$arPrice[$product['ID']]) {
                \CIBlockElement::SetPropertyValuesEx($product['ID'], \Tanais\Alter\Crm\Catalog::PRODUCT_IBLOCK_ID, ['PRICE' => (int)$arPrice[$product['ID']]]);
                $count += 1;
            }
        }
        $resultstr = "Обновлено: " . $count;
        return ['result' => $resultstr];
    }
}
