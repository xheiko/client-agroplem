<?php

namespace Tanais\Alter\Crm;

use \Bitrix\Crm\Service;

class Catalog
{
    const  PRODUCT_IBLOCK_ID = 14; //Инфоблок номенклатуры

    const  OFFER_IBLOCK_ID = 15; //Инфоблок предложений
    const  OFFER_PROPERTY_CML2_LINK_ID = 62; //id свойства CML2_LINK инфоблока предложений 

    //Обновляет цену Офферов по полю Цена для каталога. 
    public static function updateOfferPrices(): ?array
    {
        $productsData = \Bitrix\Iblock\Elements\ElementProductsTable::getList([
            'select' => ['ID', 'NAME', 'PRICE_' => 'PRICE'],
            'filter' => ['IBLOCK_ID' => self::PRODUCT_IBLOCK_ID]
        ])->fetchAll($productsData);

        foreach ($productsData as &$productData) {
            $productData['OFFER_ID'] = \Tanais\Alter\Crm\Product::getOfferId($productData['ID']);
            $arFields = array(
                "PRODUCT_ID" => $productData['OFFER_ID'],
                "CATALOG_GROUP_ID" => 1,
                "PRICE" => explode('|', $productData['PRICE_VALUE'])[0],
                "CURRENCY" => "RUB",
                "QUANTITY_FROM" => 0,
                "QUANTITY_TO" => null
            );
            $price = \CPrice::GetList([], array("PRODUCT_ID" => $productData['OFFER_ID'], "CATALOG_GROUP_ID" => 1));
            if ($price = $price->Fetch()) {
                \CPrice::Update($price["ID"], $arFields);
            } else {
                $idPrice = \CPrice::Add($arFields);
            }
        }
        return ['result' => true];
    }

    public static function getFieldValue($productId, $fieldName)
    {
        if ((!(intval($productId) > 0)) or (!$fieldName))
            return false;

        $element = \Bitrix\Iblock\Elements\ElementProductsTable::getList([
            'select' => ['ID', $fieldName],
            'filter' => [
                'ID' => $productId,
            ],
            "cache" => ["ttl" => 3600],
        ])->fetch();

        if ($element[$fieldName])
            return $element[$fieldName];
        if ($element['IBLOCK_ELEMENTS_ELEMENT_PRODUCTS_' . $fieldName . '_VALUE'])
            return $element['IBLOCK_ELEMENTS_ELEMENT_PRODUCTS_' . $fieldName . '_VALUE'];

        return false;
    }

}
