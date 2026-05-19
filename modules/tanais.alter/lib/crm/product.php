<?

namespace Tanais\Alter\Crm;

use \Bitrix\Crm\Service;

// \Bitrix\Main\Loader::IncludeModule("catalog");
\Bitrix\Main\Loader::IncludeModule('im');
\Bitrix\Main\Loader::IncludeModule('iblock');

class Product
{
    const TYPE_PRODUCT = \Bitrix\Catalog\ProductTable::TYPE_PRODUCT; // 	Простой товар
    const TYPE_SET = \Bitrix\Catalog\ProductTable::TYPE_SET; //  Комплект
    const TYPE_SKU = \Bitrix\Catalog\ProductTable::TYPE_SKU; //  Товар с торговыми предложениями
    const TYPE_OFFER = \Bitrix\Catalog\ProductTable::TYPE_OFFER; //  Торговое предложение
    const TYPE_FREE_OFFER = \Bitrix\Catalog\ProductTable::TYPE_FREE_OFFER; //  Торговое предложение, у которого нет товара (не указан или удален).
    const TYPE_EMPTY_SKU = \Bitrix\Catalog\ProductTable::TYPE_EMPTY_SKU; //  Специфический тип, означает невалидный товар с торговыми предложениями.
    const IBLOCK_ID = 14; //  id инфоблока товаров


    public static function getCompatibleData($productId = null)
    {
        if ((empty($productId)) or (intval($productId) == 0))
            return null;
        $productType = self::getProductType($productId);
        if ($productType == self::TYPE_OFFER) {
            $offerId = $productId;
            $productId = self::getProductSKUId($productId);
        }
        if ($productType == self::TYPE_SKU) {
            $offerId = self::getOfferId($productId);
        }
        $productData = \CCatalogProduct::GetByIDEx($productId, true);
        $productData['OFFERS'] = \CCatalogProduct::GetByIDEx($offerId, true);
        return $productData;
    }

    public static function getName($productId = null)
    {
        if ((empty($productId)) or (intval($productId) == 0))
            return null;
        $productData = \CCatalogProduct::GetByIDEx($productId);
        return $productData["NAME"];
    }

    public static function getPropertyValue($productId = null, $propertyCode = null)
    {
        if ((empty($productId)) or (intval($productId) == 0))
            return null;
        if ((empty($propertyCode)))
            return null;
        if (self::isOffer($productId))
            $productId = self::getProductSKUId($productId);

        $productData = \CCatalogProduct::GetByIDEx($productId, true);

        return $productData["PROPERTIES"][$propertyCode]["VALUE"];
    }


    public static function isOffer($productId = null)
    {
        if (self::getProductType($productId) == self::TYPE_OFFER)
            return true;
        else
            return false;
    }

    public static function isProductSKU($productId = null)
    {
        if (self::getProductType($productId) == self::TYPE_SKU)
            return true;
        else
            return false;
    }

    public static function getProductType($productId = null)
    {
        if ((empty($productId)) or (intval($productId) == 0))
            return null;
        if ($product = \Bitrix\Catalog\ProductTable::getList(['select' => ['TYPE'], 'filter' => ['=ID' => $productId]])->fetch())
            return $product['TYPE'];
        else
            return null;
    }


    public static function getVendorCode($productId = null)
    {
        if ((empty($productId)) or (intval($productId) == 0))
            return null;
        return self::getPropertyValue(self::getProductSKUId($productId), 'VENDOR_CODE');
    }

    public static function getProductSKUId($offerId = null)
    {
        if ((empty($offerId)) or (intval($offerId) == 0))
            return null;

        if ($product = \Bitrix\Catalog\ProductTable::getList(['select' => ['ID', 'TYPE'], 'filter' => ['=ID' => $offerId]])->fetch()) {
            if ($product['TYPE'] == self::TYPE_SKU)
                return $offerId;
            if ($product['TYPE'] == self::TYPE_OFFER) {
                $product = \CCatalogSku::GetProductInfo($offerId);
                return $product["ID"];
            }
        } else
            return null;
    }

    public static function getOfferId($productId = null)
    {
        if ((empty($productId)) or (intval($productId) == 0))
            return null;
        $productType = self::getProductType($productId);
        if ($productType == self::TYPE_OFFER)
            return $productId;
        if ($productType == self::TYPE_SKU) {
            $offers = \CCatalogSKU::getOffersList($productId);
            return array_key_first($offers[$productId]);
        }
        return null;
    }

    public static function ReplaceProductsFromCatalog($contractId, $discountRate, $labs)
    {
        if (!$contractId || !$labs) {
            return;
        }
        \Bitrix\Main\Loader::requireModule('crm');
        \Bitrix\Main\Loader::requireModule('iblock');

        $arSections = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => 17,
                'ID' => $labs,
            ],
            false,
            false,
            [
                'PROPERTY_112',
            ]);

        while ($section = $arSections->fetch()) {
            if ($section['PROPERTY_112_VALUE'] != null) {
                $sections[] = $section['PROPERTY_112_VALUE'];
            }
        }


        $products = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => self::IBLOCK_ID,
                'ACTIVE' => 'Y',
                '>PROPERTY_78' => 0,
                'SECTION_ID' => $sections,
            ],
            false,
            false,
            [
                'ID',
                'NAME',
                'PROPERTY_78',
                'DETAIL_TEXT',
            ]);

        while ($product = $products->fetch()) {
            if ($discountRate < 0) {
                $price = intval($product['PROPERTY_78_VALUE']) + (intval($product['PROPERTY_78_VALUE']) * abs($discountRate) / 100);
            } elseif ($discountRate == 0) {
                $price = intval($product['PROPERTY_78_VALUE']);
            } else {
                $price = intval($product['PROPERTY_78_VALUE']) - (intval($product['PROPERTY_78_VALUE']) * $discountRate / 100);
            }
            $price = round($price, 0);

            $arProductID[] = [
                'PRODUCT_NAME' => $product['NAME'],
                'PRODUCT_ID' => $product['ID'],
                'PRICE' => $price,
                'QUANTITY' => 1,
                'TAX_INCLUDED' => 'Y',
            ];
            $text .= $product['NAME'] . ' ';
        }

        //   return $text;

        if (is_array($arProductID)) {
            $container = Service\Container::getInstance();
            $factory = $container->getFactory(\Tanais\Alter\Crm\ClientContract::ENTITY_ID);
            $item = $factory->getItem($contractId);
            $item->setProductRowsFromArrays($arProductID);
            $updateOperation = $factory->getUpdateOperation($item);
            $operationResult = $updateOperation->launch();
        }
    }

    public static function checkArchivedProducts(int $elementId, int $elementTypeId, int $userId): void
    {

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($elementTypeId);
        $supported = $factory->isLinkWithProductsEnabled();
        if (!$supported) {
            return;
        }
        $elementAbbreviation = $factory->getEntityAbbreviation();
        $productRows = \CCrmProductRow::LoadRows($elementAbbreviation, $elementId);

        if (empty($productRows)) {
            return;
        }
        $productIds = array_column($productRows, 'PRODUCT_ID');

        $archivedProducts = self::productInArchive($productIds);

        if ($archivedProducts) {
            $title = $factory->getItem($elementId)->getTitle();
            $link = \Tanais\Alter\Helper::getLink($elementAbbreviation, $elementId);
            $message = "Вы изменили элемент <a href='{$link}'>{$title}</a>, в которой есть услуга из папки Архив. "
                . "Эти услуги нельзя применять для новых продаж. Просьба проверить и изменить состав услуг.";

            $arFields = array(
                "FROM_USER_ID" => 1,
                "MESSAGE" => $message,
                "MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
                "TO_USER_ID" => $userId,
                "NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
                "NOTIFY_MODULE" => "tasks",
            );

            \CIMMessenger::Add($arFields);
        }
    }

    public static function changeProductsImage($productId): void
    {
        if (empty($productId)) {
            return;
        }

        $fileCircleId = 180999;
        $fileStopId = 180718;

        $productInfo = \CCatalogSku::GetProductInfo($productId);
        if ($productInfo['ID']) {
            $productId = $productInfo['ID'];
        }

        $inArchive = self::productInArchive($productId);
      
        if ($inArchive) {
            $fileArray = \CFile::MakeFileArray($fileStopId);
        } else {
            $fileArray = \CFile::MakeFileArray($fileCircleId);
        }
        if (!empty($fileArray)) {
            \CIBlockElement::SetPropertyValuesEx($productId, self::IBLOCK_ID, [
                'MORE_PHOTO' => [
                    'VALUE' => $fileArray,
                    'DESCRIPTION' => ''
                ]
            ]);
        }
    }


    public static function productInArchive($productIds): bool
    {
        if (empty($productIds)) {
            return false;
        }
        if (is_array($productIds)) {
            foreach ($productIds as $productId) {
                $productInfo = \CCatalogSku::GetProductInfo($productId);
                if ($productInfo['ID']) {
                    $arProductsId[] = $productInfo['ID'];
                } else {
                    $arProductsId[] = $productId;
                }
            }
        } else{
            $productInfo = \CCatalogSku::GetProductInfo($productIds);
            if ($productInfo['ID']) {
                $arProductsId[] = $productInfo['ID'];
            } else {
                $arProductsId[] = $productIds;
            }
        }

        $arArchiveId = [];

        $arArchiveSection = \CIBlockSection::GetList([], ['IBLOCK_ID' => self::IBLOCK_ID, 'NAME' => 'Архив',], false, ['ID']);
        while ($archiveSection = $arArchiveSection->Fetch()) {
            $arArchiveId[] = $archiveSection['ID'];
        }

        $arSections = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => self::IBLOCK_ID, 'ID' => $arProductsId],
            false, false,
            ['IBLOCK_SECTION_ID']
        );
        while ($section = $arSections->Fetch()) {
            if ($section['IBLOCK_SECTION_ID']) {
                if (in_array($section['IBLOCK_SECTION_ID'], $arArchiveId)) {
                    return true;
                }
            }
        }

        return false;
    }


}
