<?

namespace Tanais\Alter\Crm;

use \Bitrix\Crm\Service;
use \Tanais\Alter\Crm\Currency;

//\Tanais\Alter\Crm\Deal::startDealBPWorkflow(12,$dealId);
class Deal
{
    const  MODULE_ID = 'tanais.alter';

    //Функция получени ID комапнии по guid
    //игнорирует права \Tanais\Alta\Crm\Deal::getId('71f0c4a3-3aa0-11ef-8ea2-00155d114325');
    static public function getId($guid = "")
    {
        if ((empty($guid)) || (strlen($guid) < 36))
            return false;

        $arOptions = [
            'CURRENT_USER' => 1,
            "CHECK_PERMISSIONS" => "N",
            'DISABLE_USER_FIELD_CHECK' => true,
            "DISABLE_REQUIRED_USER_FIELD_CHECK" => true
        ];
        $CrmDeal = new \CCrmDeal(false);
        $arDealUnit = [
            'ORIGIN_ID' => $guid,
            'CHECK_PERMISSIONS' => 'N'
        ];
        // $arCompanyOption = array();
        if ($arDeal = $CrmDeal->GetList(['ID' => 'ASC'], $arDealUnit, $arOptions)->fetch()) {
            return $arDeal['ID'];
        } else {
            return false;
        }
    }


    //Значения поля $fieldName из сделки $dealId
    public static function getFieldValue($dealId, $fieldName)
    {
        if (!(intval($dealId) > 0))
            return false;
        if (!$fieldName)
            return false;

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);

        $deal = $factory->getItem($dealId);
        if ((is_object($deal)) && ($deal->hasField($fieldName)))
            return $deal->get($fieldName);
        else
            return false;
    }

    //Запускает БП с id=workflowTemplateId для сделки dealId
    public static function startDealBPWorkflow($workflowTemplateId, $dealId)
    {
        if ((empty($dealId)) or (intval($dealId) == 0))
            return false;
        if (empty($workflowTemplateId))
            return false;

        \Bitrix\Main\Loader::includeModule('bizproc');
        $runtime = \CBPRuntime::GetRuntime();
        try {
            $workflowTemplateId = intval($workflowTemplateId);
            $dealId = intval($dealId);
            $documentId = ['crm', 'CCrmDocumentDeal', 'DEAL_' . $dealId];
            if (\CCrmDeal::Exists($dealId)) {
                $workflow = $runtime->CreateWorkflow($workflowTemplateId, $documentId, []);
                $workflow->Start();
                return true;
            } else
                echo "Нет сделки $dealId<br>";
        } catch (Exception $e) {
            echo $e->getMessage() . " dealId=$dealId workflowTemplateId=$workflowTemplateId<br>" . PHP_EOL;
            return false;
        }
        return false;
    }

    public static function getCompatibleData($dealId)
    {
        if (empty($dealId))
            return false;
        $container = Service\Container::getInstance();
        $factory = $container->getFactory(\CCrmOwnerType::Deal);
        $deal = $factory->getItem($dealId);
        if ($deal)
            return $deal->getCompatibleData();
        return [];
    }


    //Получает номер Заказа
    public static function register($dealId, $reRegister = false)
    {
        if ((empty($dealId)) or (intval($dealId) == 0))
            return false;

        $factoryDeal = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
        $deal = $factoryDeal->getItem($dealId);
        if (empty($deal))
            return false;

        $dealData = $deal->getCompatibleData($dealId);

        //Номер есть, перерегистрация не нужна
        if (!empty($dealData['UF_CRM_ORDER_NUMBER']) and ($reRegister == false))
            return false;

        $categoryId = $dealData['CATEGORY_ID'];
        $newOrderNumber = self::getNextNumber($categoryId);

        $deal->set('UF_CRM_ORDER_NUMBER', $newOrderNumber);

        $operation = $factoryDeal->getUpdateOperation($deal);
        $operation->disableBizProc();
        $operation->disableAllChecks();
        $operationResult = $operation->launch();
        if ($operationResult->isSuccess())
            return $newOrderNumber;

        return false;
    }

    public static function getNextNumber(int $categoryId)
    {
        if ((empty($categoryId)) or (intval($categoryId) == 0))
            return false;

        $laboratoryData = \Tanais\Alter\Crm\Laboratory::getCompatibleData($categoryId);
        $laboratoryCode = $laboratoryData['CODE']['VALUE'];
        echo "code=$laboratoryCode<br>";
        if (empty($laboratoryCode))
            return false;
        $filter = ['UF_CRM_ORDER_NUMBER' => $laboratoryCode . date('y') . "%"];
        $sort = ["ID" => "DESC"];
        $options = [
            'CURRENT_USER' => 1,
            "CHECK_PERMISSIONS" => "N",
            'DISABLE_USER_FIELD_CHECK' => true,
            "DISABLE_REQUIRED_USER_FIELD_CHECK" => true
        ];
        $deals = \CCRMDeal::GetList(
            $sort,
            $filter,
            $options
        );
        while ($deal = $deals->fetch()) {
            $orderNumber = (explode('-', $deal['UF_CRM_ORDER_NUMBER']))[1];
            if ($orderNumber)
                $orderNumbers[] = $orderNumber;
        }
        if ($orderNumbers) {
            rsort($orderNumbers, SORT_NUMERIC);
            return $laboratoryCode . date('y') . "-" . str_pad($orderNumbers[0] + 1, 5, "0", STR_PAD_LEFT);
        }
        return $laboratoryCode . date('y') . "-" . str_pad(1, 5, "0", STR_PAD_LEFT);
    }

//    public static function updateDealProductsPriceByContractPrice($deal_id = null)
//    {
//        if ((empty($deal_id))) {
//            return false;
//        }
//
//        $logfilename = '/home/bitrix/www/local/tanais/log/updateDealProductsPriceByContractPrice.log';
//        // file_put_contents($logfilename, "Обновляем цены товаров сделки ID=$deal_id\r\n");
//        // var_dump("Обновляем цены товаров сделки ID=$deal_id\r\n");
//
//        //Пересчитывает сделку по Ценам клиента
//
//        $rsdeals = \CCrmDeal::GetList(array("ID" => "DESC"), array("ID" => $deal_id));
//        if (($deal = $rsdeals->GetNext()) && ($deal['UF_CRM_CLIENT_CONTRACT'])) { //Если есть сделка и договор указан
//            $entityTypeId = 1050;     //Смарт-процесс Договоры с клиентами
//            $factory2 = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
//            $arSelect = ['*'];
//            $arFilter = ['ID' => $deal['UF_CRM_CLIENT_CONTRACT']];
//            $contracts = $factory2->getItems(['select' => $arSelect, 'filter' => $arFilter,]);
//            if ($contracts[0]['ID'] > 0) { //Если нашли цены
//                $contract = $contracts[0];
//                // file_put_contents($logfilename, "Нашли и прочитали договор ID=" . $contract['ID'] . "\r\n", FILE_APPEND);
//                // var_dump("Нашли и прочитали договор ID=" . $contract['ID'] . "\r\n");
//                // file_put_contents($logfilename,"ПРАЙС\r\n",FILE_APPEND);
//                // Товары из сделки
//                if ($deal_products = \CCrmDeal::LoadProductRows($deal_id)) {
//                    $countProductDeals = count($deal_products);
//                    $entityResult = \CCrmDeal::GetListEx([], ['ID' => $deal_id], false, false, ['TITLE', 'CURRENCY_ID']);
//                    while ($dealInfo = $entityResult->fetch()) {
//                        $dealCurrency = $dealInfo['CURRENCY_ID'];
//                        $dealName = $dealInfo['TITLE'];
//                    }
//                    if ($contract['CURRENCY_ID'] == $dealCurrency) {
//                        /*foreach ($deal_products as $deal_product) {
//                            $dealProductsId[] = $deal_product['PRODUCT_ID'];
//                        }
//                        $productCurrency = \CIBlockElement::GetList([], ['IBLOCK_ID' => 13, 'CURRENCY' => $dealCurrency, 'ID' => $dealProductsId], false, false, ['ID']);
//                        while ($obElement = $productCurrency->Fetch()) {
//                            $countProductCurrency[] = $obElement;
//                        }
//                        $countProductCurrency = (count($countProductCurrency));
//                        if ($countProductDeals == $countProductCurrency) {*/
//                        // file_put_contents($logfilename, "Загрузили товары Сделки в количестве " . $countProductDeals . " позиций \r\n", FILE_APPEND);
//                        // var_dump("Загрузили товары Сделки в количестве " . $countProductDeals . " позиций \r\n");
//                        //Получаем товары с ценами из Смарт-процесса Договор с клиентом
//                        $arSelect = ['*'];
//                        $arFilter = ['OWNER_ID' => $contract['ID'], 'OWNER_TYPE' => 'T41a'];
//                        if ($prices = \Bitrix\Crm\ProductRowTable::getList(['select' => $arSelect, 'filter' => $arFilter])->fetchAll()) {
//                            file_put_contents($logfilename, "Загрузили товары Договора в количестве " . count($prices) . " позиций \r\n", FILE_APPEND);
//                            $fields = ['PRICE', 'PRICE_EXCLUSIVE', 'PRICE_NETTO', 'PRICE_BRUTTO', 'PRICE_ACCOUNT', 'DISCOUNT_TYPE_ID', 'DISCOUNT_RATE', 'DISCOUNT_SUM', 'TAX_RATE', 'TAX_INCLUDED'];
//                            $is_changed = false;
//                            foreach ($prices as $price_key => $price_product) {
//                                foreach ($deal_products as $deal_key => $deal_product) {
//                                    if ($price_product['PRODUCT_ID'] == $deal_product['PRODUCT_ID']) {
//                                        $is_changed = true;
//                                        file_put_contents($logfilename, "Обновляем цену позиции PRODUCT_ID=" . $deal_product['PRODUCT_ID'] . " PRICE " . $deal_products[$deal_key]['PRICE'] . "->" . $price_product['PRICE'] . "\r\n", FILE_APPEND);
//                                        $deal_products[$deal_key]['PRICE'] = $price_product['PRICE'];
//                                        $deal_products[$deal_key]['PRICE_EXCLUSIVE'] = $price_product['PRICE_EXCLUSIVE'];
//                                        $deal_products[$deal_key]['PRICE_NETTO'] = $price_product['PRICE_NETTO'];
//                                        $deal_products[$deal_key]['PRICE_BRUTTO'] = $price_product['PRICE_BRUTTO'];
//                                        //$deal_products[$deal_key]['PRICE_ACCOUNT'] = $price_product['PRICE_ACCOUNT'];
//                                        //$deal_products[$deal_key]['DISCOUNT_TYPE_ID'] = $price_product['DISCOUNT_TYPE_ID'];
//                                        //$deal_products[$deal_key]['DISCOUNT_RATE'] = $price_product['DISCOUNT_RATE'];
//                                        //$deal_products[$deal_key]['DISCOUNT_SUM'] = $price_product['DISCOUNT_SUM'];
//                                        $deal_products[$deal_key]['TAX_RATE'] = $price_product['TAX_RATE'];
//                                        $deal_products[$deal_key]['TAX_INCLUDED'] = $price_product['TAX_INCLUDED'];
//                                    }
//                                }
//                            }
//                            if ($is_changed) {
//                                \CCrmDeal::SaveProductRows($deal_id, $deal_products);
//                                $entityResult = \CCrmDeal::GetListEx([], ['ID' => $deal_id], false, false, ['OPPORTUNITY', 'CURRENCY_ID']);
//                                while ($dealInfo = $entityResult->fetch()) {
//                                    if ($dealInfo['CURRENCY_ID'] == 'USD') {
//                                        $now = new \DateTime();
//                                        $nowDate = $now->format('d.m.Y');
//                                        $current = Converter::getCurrencyForDate($nowDate);
//                                        $entityFields['UF_CRM_CONVERSION_RATE'] = $current;
//                                        $entityFields['UF_CRM_SUM_DEAL_FOR_REPORT'] = $current * $dealInfo['OPPORTUNITY'];
//                                    } else {
//                                        $entityFields['UF_CRM_SUM_DEAL_FOR_REPORT'] = $dealInfo['OPPORTUNITY'];
//                                    }
//                                }
//                                $entityObject = new \CCrmDeal(true);
//                                $isUpdateSuccess = $entityObject->Update($deal_id, $entityFields, $bCompare = true, $arOptions = []);
//                                // file_put_contents($logfilename, "Товары сделки сохранены с новыми ценами " . count($deal_products) . " позиций\r\n", FILE_APPEND);
//                                return "Товары сделки сохранены с новыми ценами " . count($deal_products) . " позиций";
//                            } else {
//                                // file_put_contents($logfilename, "Цены не поменялись \r\n", FILE_APPEND);
//                                return "Цены не поменялись";
//                            }
//                        } else {
//                            // file_put_contents($logfilename, "У Договора с клиентом нет товаров. Прайс лист не определён\r\n", FILE_APPEND);
//                            return "У Договора с клиентом нет товаров. Прайс лист не определён";
//                        }
//                    } else {
//                        // file_put_contents($logfilename, "Валюта договора отличается от валюты сделки\r\n", FILE_APPEND);
//                        $message = 'Услуги сделки [URL=/crm/deal/details/' . $deal_id . '/] ' . $dealName . ' [/URL] не были пересчитаны согласно условиям договора, так как валюта сделки не совпадает с валютой договора';
//                        $arMessageFields = array(
//                            "FROM_USER_ID" => 1,
//                            "TO_USER_ID" => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
//                            "NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
//                            "NOTIFY_MODULE" => "main",
//                            "NOTIFY_TAG" => "INVOICE_NOTIFY",
//                            "NOTIFY_MESSAGE" => $message,
//                        );
//                        \CIMNotify::Add($arMessageFields);
//                        return "валюта сделки не совпадает с валютой договора";
//                    }
//                } else {
//                    // file_put_contents($logfilename, "У сделки нет товаров\r\n", FILE_APPEND);
//                    return "У сделки нет товаров";
//                }
//            } else {
//                // file_put_contents($logfilename, "Не нашли элемент Договор с клиентом по ID, указанным в сделке. Возможно удалён.\r\n", FILE_APPEND);
//                return "Не нашли элемент Договор с клиентом по ID, указанным в сделке. Возможно удалён.";
//            }
//        } else {
//            // file_put_contents($logfilename, "Не смогли прочитать сделку или не указан Договор с клиентом\r\n", FILE_APPEND);
//            return "Не смогли прочитать сделку или не указан Договор с клиентом";
//        }
//    }


    public static function updateDealProductsPriceByContractPrice($dealId = null)
    {
        if ((empty($dealId))) {
            return false;
        }
        $startStageId = \CCrmDeal::GetStartStageID(\CCrmDeal::GetCategoryID($dealId));


        //Пересчитывает сделку по Ценам клиента
        $dealTable = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal)->getDataClass();
        $deal = $dealTable::getList([
            "select" => [
                'ID',
                'TITLE',
                'CURRENCY_ID',
                'UF_CRM_CLIENT_CONTRACT',
                'OPPORTUNITY',
                'STAGE_ID',
            ],
            'filter' => ['ID' => $dealId],
        ])->fetch();


        if ($deal['STAGE_ID'] != $startStageId) {
            return "Стадия не черновик";
        }
        // $deals = \CCrmDeal::GetList(array("ID" => "DESC"), array("ID" => $deal_id));
        if (!$deal['UF_CRM_CLIENT_CONTRACT']) {
            return "Не смогли прочитать сделку или не указан Договор с клиентом";
        }
        //Если есть сделка и договор указан
        $contractTable = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\Tanais\Alter\Crm\ClientContract::ENTITY_ID)->getDataClass();//Смарт-процесс Договоры с клиентами
        $contract = $contractTable::getList([
            'select' => ['ID', 'CURRENCY_ID'],
            'filter' => ['ID' => $deal['UF_CRM_CLIENT_CONTRACT']],
            'limit' => 1
        ])->fetch();
        if (!$contract['ID']) { //Если нашли цены
            return "Не нашли элемент Договор с клиентом по ID, указанным в сделке. Возможно удалён.";
        }
        $dealProducts = \CCrmDeal::LoadProductRows($dealId);

        if (!$dealProducts) {
            return "У сделки нет товаров";
        }

        $dealName = $deal['TITLE'];

        if ($contract['CURRENCY_ID'] != $deal['CURRENCY_ID']) {
            $message = 'Услуги сделки [URL=/crm/deal/details/' . $dealId . '/] ' . $dealName . ' [/URL] не были пересчитаны согласно условиям договора, так как валюта сделки не совпадает с валютой договора';
            $arMessageFields = array(
                "FROM_USER_ID" => 1,
                "TO_USER_ID" => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
                "NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
                "NOTIFY_MODULE" => "main",
                "NOTIFY_TAG" => "INVOICE_NOTIFY",
                "NOTIFY_MESSAGE" => $message,
            );
            \CIMNotify::Add($arMessageFields);
            return "валюта сделки не совпадает с валютой договора";
        }

        //Получаем товары с ценами из Смарт-процесса Договор с клиентом
        $prices = \Bitrix\Crm\ProductRowTable::getList([
            'select' => ['*'],
            'filter' => ['OWNER_ID' => $contract['ID'], 'OWNER_TYPE' => 'T41a']
        ])->fetchAll();

        if (!$prices) {
            return "У Договора с клиентом нет товаров. Прайс лист не определён";
        }

        $isChanged = false;
        foreach ($prices as $priceKey => $priceProduct) {
            foreach ($dealProducts as $dealKey => $deal_product) {
                if ($priceProduct['PRODUCT_ID'] == $deal_product['PRODUCT_ID']) {
                    $isChanged = true;
                    $dealProducts[$dealKey]['PRICE'] = $priceProduct['PRICE'];
                    $dealProducts[$dealKey]['PRICE_EXCLUSIVE'] = $priceProduct['PRICE_EXCLUSIVE'];
                    $dealProducts[$dealKey]['PRICE_NETTO'] = $priceProduct['PRICE_NETTO'];
                    $dealProducts[$dealKey]['PRICE_BRUTTO'] = $priceProduct['PRICE_BRUTTO'];
                    $dealProducts[$dealKey]['TAX_RATE'] = $priceProduct['TAX_RATE'];
                    $dealProducts[$dealKey]['TAX_INCLUDED'] = $priceProduct['TAX_INCLUDED'];
                }
            }
        }
        if (!$isChanged) {
            return "Цены не поменялись";
        }
        \CCrmDeal::SaveProductRows($dealId, $dealProducts);

        if ($deal['CURRENCY_ID'] == 'USD') {
            $now = new \DateTime();
            $nowDate = $now->format('d.m.Y');
            $current = Converter::getCurrencyForDate($nowDate);
            $entityFields['UF_CRM_CONVERSION_RATE'] = $current;
            $entityFields['UF_CRM_SUM_DEAL_FOR_REPORT'] = $current * $deal['OPPORTUNITY'];
            $updateFields = [
                'UF_CRM_SUM_DEAL_FOR_REPORT' => $current * $deal['OPPORTUNITY'],
            ];
        } else {
            $entityFields['UF_CRM_SUM_DEAL_FOR_REPORT'] = $deal['OPPORTUNITY'];
            $updateFields = [
                'UF_CRM_SUM_DEAL_FOR_REPORT' => $deal['OPPORTUNITY'],
            ];
        }

        //  $entityObject = new \CCrmDeal(true);
        // $isUpdateSuccess = $entityObject->Update($dealId, $entityFields, $bCompare = true, $arOptions = []);
        $result = \Bitrix\Crm\DealTable::update($dealId, $updateFields);

        if ($result->isSuccess()) {
            return "Товары сделки сохранены с новыми ценами " . count($dealProducts) . " позиций";
        }
    }

    public static function fixCents($update = false, $dealId = null)
    {
        $dealFilter = '';
        if (!empty($dealId)) {
            $dealId = (int)$dealId;
            $dealFilter = " AND d.ID = {$dealId} ";
        }

        $connection = \Bitrix\Main\Application::getConnection();
        $sqlHelper = $connection->getSqlHelper();
        $selectSql = "
        SELECT 
            d.ID,
            d.DATE_CREATE,
            d.TITLE,
            r.PRODUCT_NAME,
            r.PRICE,
            r.PRICE_BRUTTO,
            ROUND(ROUND(r.PRICE*20)/20,2) AS FIX_PRICE,
            ROUND(ROUND(r.PRICE_BRUTTO*20)/20,2) AS FIX_BRUTTO
        FROM b_crm_product_row r
        JOIN b_crm_deal d ON r.OWNER_ID = d.ID
        JOIN b_uts_crm_deal u ON u.VALUE_ID = d.ID
        WHERE r.OWNER_TYPE='D'
        --  AND d.OPENED='Y'
        AND YEAR(d.DATE_CREATE) >= 2025
        {$dealFilter}
        AND ABS(r.PRICE-r.PRICE_BRUTTO) = 0.01
        -- только кривые копейки
        AND (ROUND(ROUND(r.PRICE*20)/20,2) <> ROUND(r.PRICE,2)
            OR ROUND(ROUND(r.PRICE_BRUTTO*20)/20,2) <> ROUND(r.PRICE_BRUTTO,2))
        ORDER BY d.DATE_CREATE DESC
        ";

        $updateSql = "
                    UPDATE b_crm_product_row r
                    JOIN b_crm_deal d ON r.OWNER_ID = d.ID
                    SET 
                        r.PRICE        = ROUND(ROUND(r.PRICE*20)/20,2),
                        r.PRICE_BRUTTO = ROUND(ROUND(r.PRICE_BRUTTO*20)/20,2)
                    WHERE r.OWNER_TYPE='D'
                    AND d.OPENED='Y'
                    AND YEAR(d.DATE_CREATE) >= 2025
                    {$dealFilter}
                    AND ABS(r.PRICE - r.PRICE_BRUTTO) = 0.01
                    -- только кривые копейки
                    AND (
                        ROUND(ROUND(r.PRICE*20)/20,2) <> ROUND(r.PRICE,2)
                        OR ROUND(ROUND(r.PRICE_BRUTTO*20)/20,2) <> ROUND(r.PRICE_BRUTTO,2)
                    );

			";
        if ($update) {
            $dbData = $connection->queryExecute($updateSql);
            return true;
        }

        $dbData = $connection->query($selectSql);
        while ($row = $dbData->fetch()) {
            echo "<pre>" . var_export($row, true) . "</pre><br>";
        }
    }


    public static function getProductsForDeal()
    {
        $arProducts = [];

        $products = \Bitrix\Crm\ProductRowTable::getList([
            'select' => ['PRODUCT_ID', 'QUANTITY', 'OWNER_ID'],
            'filter' => ['OWNER_TYPE' => 'D']
        ])->fetchAll();

        foreach ($products as $product) {
            $arProducts[$product['OWNER_ID']][] = $product;
        }

        return $arProducts;
    }

    public static function getProductsWithProps()
    {
        $arProducts = [];

        $arSelect = ["ID", "PROPERTY_COUNT_SERVICES"];
        $arFilter = ["IBLOCK_ID" => 14];

        $res = \CIBlockElement::GetList([], $arFilter, false, false, $arSelect);

        while ($ob = $res->fetch()) {
            $arProducts[$ob['ID']] = $ob['PROPERTY_COUNT_SERVICES_VALUE'];
        }

        return $arProducts;
    }

    public static function updateCountServices($dealId = false, $doNotUpdateDeal = false)
    {
        if (empty($dealId)) return false;

        $filter = [];
        if ($dealId) {
            $filter['ID'] = $dealId;
        }

        $factoryDeal = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
        $deals = $factoryDeal->getItems([
            'filter' => $filter,
        ]);
        foreach ($deals as $deal) {
            // echo $deal->getId()."<hr>";
            $products = $deal->getProductRows()->toArray();
            $totalServicesCount = 0;
            foreach ($products as $product) {
                $productId = \Tanais\Alter\Crm\Product::getProductSKUId($product["PRODUCT_ID"]);
                $servicesCount = \Tanais\Alter\Crm\Product::getPropertyValue($productId, 'COUNT_SERVICES');
                if (empty($servicesCount))
                    $servicesCount = 1;
                $totalServicesCount += $product['QUANTITY'] * $servicesCount;
                // echo $totalServicesCount."<br>";
            }
            if ($deal->get('UF_CRM_COUNT_SERVICES') != $totalServicesCount) {
                $deal->set('UF_CRM_COUNT_SERVICES', $totalServicesCount);

                $operation = $factoryDeal->getUpdateOperation($deal);
                $operation->disableBizProc()
                    ->disableAllChecks()
                    ->disableCheckAccess();
                $operationResult = $operation->launch();
                // var_export($operationResult);
            }
        }
    }


    public static function processClientContract($dealId = false)
    {
        if (empty($dealId)) return false;
        $factoryDeal = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
        $factoryClientContract = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\Tanais\Alter\Crm\ClientContract::ENTITY_ID);

        $deal = $factoryDeal->getItem($dealId);
        $companyLab = $deal->get('UF_CRM_LABORATORY');
        // d($deal->getCompatibleData());
        if (!empty($deal->get('COMPANY_ID'))) {
            $contractData = \Tanais\Alter\Crm\ClientContract::getCompatibleData($deal->get('UF_CRM_CLIENT_CONTRACT'));
            //    d($contractData);
            //Если контракт другой компаниий
            if (($deal->get('UF_CRM_CLIENT_CONTRACT')) and ($deal->get('COMPANY_ID') != $contractData['COMPANY_ID'])) {
                //      d("Если контракт другой компаниий");
                $deal->set('UF_CRM_CLIENT_CONTRACT', null);
                $operation = $factoryDeal->getUpdateOperation($deal);
                $operation->disableBizProc()
                    ->disableAllChecks()
                    ->disableCheckAccess();
                $operationResult = $operation->launch();
                //   d("убрали договор");
            }

            if (empty($deal->get('UF_CRM_CLIENT_CONTRACT'))) {
                //   d("пустой договор");
                $clientContracts = $factoryClientContract->getItems([
                    'filter' => [
                        'COMPANY_ID' => $deal->get('COMPANY_ID'),
                        'STAGE_ID' => 'DT1050_19:CLIENT',
                        'UF_CRM_6_LABORATORIES' => [$companyLab]
                    ],
                    'order' => ['ID' => 'DESC'],
                    'limit' => 1,
                ]);

                foreach ($clientContracts as $clientContract) {
                    if ($clientContract->getId()) {
                        $contractId = $clientContract->getId();
                        if ($contractId) {
                            //     d("Есть догвоор ID");
                            $deal->set('UF_CRM_CLIENT_CONTRACT', $contractId);
                            $operation = $factoryDeal->getUpdateOperation($deal);
                            $operation->disableBizProc()
                                ->disableAllChecks()
                                ->disableCheckAccess();
                            $operationResult = $operation->launch();
                            //    d("Обновили");
                        }
                    }
                }
            }

            /*
            $deals = $factoryDeal->getItems([
                'filter' => $filter,
            ]);
            foreach ($deals as $deal) {
                $products = $deal->getProductRows()->toArray();
                $totalServicesCount = 0;
                foreach ($products as $product) {
                    $productId = \Tanais\Alter\Crm\Product::getProductSKUId($product["PRODUCT_ID"]);
                    $servicesCount = \Tanais\Alter\Crm\Product::getPropertyValue($productId, 'COUNT_SERVICES');
                    if (empty($servicesCount))
                        $servicesCount = 1;

                    $totalServicesCount += $product['QUANTITY'] * $servicesCount;
                    if ($deal->get('UF_CRM_COUNT_SERVICES') != $totalServicesCount) {
                        $deal->set('UF_CRM_COUNT_SERVICES', $totalServicesCount);

                        $operation = $factoryDeal->getUpdateOperation($deal);
                        $operation->disableBizProc()
                            ->disableAllChecks()
                            ->disableCheckAccess();
                        $operationResult = $operation->launch();
                    }
                }
            }
                */
        }
    }

    public static function regulatoryDeadline()
    {
        $arNotify = [];

        \Bitrix\Main\Loader::requireModule('im');
        $dateNow = new \DateTime();
        $dateNow = $dateNow->format('d.m.Y');
        $dateNow = new \DateTime($dateNow);

        $entityResult = \CCrmDeal::GetListEx(
            [
                'SOURCE_ID' => 'DESC'
            ],
            [
                '!CLOSED' => 'Y',
                '!UF_CRM_CONCLUSION_DELIVERY_DATE_PLAN' => null,
                'UF_CRM_1570104297940' => null,
                'CHECK_PERMISSIONS' => 'N',
                '!STAGE_ID' => [
                    'C9:NEW',
                    'C9:1',
                    'C1:NEW',
                    'C1:WON',
                    'C1:LOSE',
                    'C1:1',
                    'C2:NEW',
                    'C2:UC_EW2TK4',
                    'C2:UC_FKWH6M',
                    'C2:UC_WUB754',
                    'C2:UC_DRASYD',
                    'C2:FINAL_INVOICE',
                    'C3:NEW',
                    'C4:NEW',
                    'C5:NEW',
                    'C6:NEW',
                    'C7:NEW'
                ],
            ],
            false,
            false,
            [
                'ID',
                'TITLE',
                'UF_CRM_CONCLUSION_DELIVERY_DATE_PLAN',
                'UF_CRM_SALESMANAGER',
                'ASSIGNED_BY_ID'
            ]
        );

        while ($entity = $entityResult->fetch()) {
            $observers = \Bitrix\Crm\Observer\ObserverManager::getEntityObserverIDs(\CCrmOwnerType::Deal, $entity['ID']);

            $day = new \DateTime($entity['UF_CRM_CONCLUSION_DELIVERY_DATE_PLAN']);
            $day = $day->format('d.m.Y');
            $day = new \DateTime($day);

            if ($day != 0 && $day == $dateNow) {

                $arNotify[$entity['UF_CRM_SALESMANAGER']]['TITLEE'] = 'Сегодня заканчивается регламентный срок завершения сделки:<br>';
                $arNotify[$entity['UF_CRM_SALESMANAGER']]['NOTT'] .= "[url=/crm/deal/details/" . $entity['ID'] . "/]" . $entity['TITLE'] . "[/url] <br>";
                if ($entity['ASSIGNED_BY_ID'] != $entity['UF_CRM_SALESMANAGER']) {
                    $arNotify[$entity['ASSIGNED_BY_ID']]['TITLEE'] = 'Сегодня заканчивается регламентный срок завершения сделки:<br>';
                    $arNotify[$entity['ASSIGNED_BY_ID']]['NOTT'] .= "[url=/crm/deal/details/" . $entity['ID'] . "/]" . $entity['TITLE'] . "[/url] <br>";
                }
                if ($observers) {
                    foreach ($observers as $observer) {
                        if ($entity['ASSIGNED_BY_ID'] != $observer && $entity['UF_CRM_SALESMANAGER'] != $observer) {
                            $arNotify[$observer]['TITLEE'] = 'Сегодня заканчивается регламентный срок завершения сделки:<br>';
                            $arNotify[$observer]['NOTT'] .= "[url=/crm/deal/details/" . $entity['ID'] . "/]" . $entity['TITLE'] . "[/url] <br>";
                        }
                    }
                }
            }
            if ($day != 0 && $day < $dateNow) {
                $interval = $dateNow->diff($day);
                $interval = $interval->days;
                $arNotify[$entity['UF_CRM_SALESMANAGER']]['TITLE'] = 'Не соблюден регламентный срок оказания услуг по следующим сделкам:<br>';
                $arNotify[$entity['UF_CRM_SALESMANAGER']]['NOT'] .= "[url=/crm/deal/details/" . $entity['ID'] . "/]" . $entity['TITLE'] . "[/url] на " . $interval . " рабочих дней <br>";
                if ($entity['ASSIGNED_BY_ID'] != $entity['UF_CRM_SALESMANAGER']) {
                    $arNotify[$entity['ASSIGNED_BY_ID']]['TITLE'] = 'Не соблюден регламентный срок оказания услуг по следующим сделкам:<br>';
                    $arNotify[$entity['ASSIGNED_BY_ID']]['NOT'] .= "[url=/crm/deal/details/" . $entity['ID'] . "/]" . $entity['TITLE'] . "[/url] на " . $interval . " рабочих дней <br>";
                }
                if ($observers) {
                    foreach ($observers as $observer) {
                        if ($entity['ASSIGNED_BY_ID'] != $observer && $entity['UF_CRM_SALESMANAGER'] != $observer) {
                            $arNotify[$observer]['TITLE'] = 'Не соблюден регламентный срок оказания услуг по следующим сделкам:<br>';
                            $arNotify[$observer]['NOT'] .= "[url=/crm/deal/details/" . $entity['ID'] . "/]" . $entity['TITLE'] . "[/url] на " . $interval . " рабочих дней <br>";
                        }
                    }
                }
            }
        }

        foreach ($arNotify as $id => $notify) {
            // $notify = implode(" ", $notify);
            $notify = $notify['TITLEE'] . ' ' . $notify['NOTT'] . ' ' . $notify['TITLE'] . ' ' . $notify['NOT'];
            $arMessageFields = array(
                "FROM_USER_ID" => 1,
                "MESSAGE" => $notify,
                "MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
                "TO_USER_ID" => $id,
                "NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
                "NOTIFY_MODULE" => "tasks",
            );
            \CIMNotify::Add($arMessageFields);
        }
    }

    public static function uppdateImportedDeals()
    {
        $factoryDeal = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
        $deals = $factoryDeal->getItems([
            'select' => ['*'],
            'filter' => [
                'CATEGORY_ID' => '0',
                // 'UF_CRM_ORDER_NUMBER'=> '',

            ],
        ]);

        foreach ($deals as $deal) {
            // echo "<pre>".var_export($deal->getCompatibleData(),true)."</pre>";
            if ($deal->get('UF_CRM_ORDER_NUMBER'))
                continue;
            if (str_contains($deal->get('COMMENTS'), 'Номер заказа')) {
                $orderNumber = mb_substr($deal->get('COMMENTS'), 13);
                $orderNumber = str_replace('¶', ' ', $orderNumber);
                // echo $orderNumber.strpos($orderNumber,'Договор');
                $orderNumber = substr($orderNumber, 0, strpos($orderNumber, 'Договор ') - 1);
                echo $deal->getId() . ' [' . $orderNumber . "] " . $deal->getId() . " " . $deal->getTitle() . '       ' . $deal->get('COMMENTS') . "<br>";
                if ($orderNumber) {
                    $deal->set('UF_CRM_ORDER_NUMBER', $orderNumber);
                    $deal->set('TITLE', $orderNumber . " " . $deal->getTitle());
                    $operation = $factoryDeal->getUpdateOperation($deal);
                    // $operationResult = $operation->launch();
                    // die();
                }
            }
        }
        return false;
    }

    public static function autoName($id = false)
    {
        if (!($id)) {
            return false;
        }

        $dealTable = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal)->getDataClass();
        $arItem = $dealTable::getList([
            "select" => [
                'ID',
                'TITLE',
                'COMPANY_ID',
                'CONTACT_ID',
                'UF_CRM_ORDER_NUMBER',
                'UF_CRM_1618824524349',
                'COMPANY_TITLE' => 'COMPANY.TITLE',
                'CONTACT_NAME' => 'CONTACT.FULL_NAME',
            ],
            "runtime" => [
                'COMPANY' => [
                    'data_type' => '\Bitrix\Crm\CompanyTable',
                    'reference' => [
                        '=this.COMPANY_ID' => 'ref.ID',
                    ]
                ],
                'CONTACT' => [
                    'data_type' => '\Bitrix\Crm\ContactTable',
                    'reference' => [
                        '=this.CONTACT_ID' => 'ref.ID',
                    ]
                ],
            ],
            'filter' => ['ID' => $id],
        ])->fetch();

        $title = str_replace(['"', '»', '«', '&quot;'], "", $arItem['TITLE']);
        $title = str_replace(['_'], " ", $title);
        $title = trim($title);
        $title = rtrim($title, '.');
        $arItem['TITLE'] = $title;

        if (stripos($arItem['TITLE'], $arItem['UF_CRM_ORDER_NUMBER']) === false) {
            $arItem['TITLE'] = $arItem['UF_CRM_ORDER_NUMBER'] . ' ' . $arItem['TITLE'];
        }

        $client = '';
        if ($arItem['COMPANY_TITLE']) {
            $client = $arItem['COMPANY_TITLE'];
        }
        if (!$arItem['COMPANY_TITLE'] && $arItem['CONTACT_NAME']) {
            $client = $arItem['CONTACT_NAME'];
        }

        if (!empty($client) && stripos($arItem['TITLE'], $client) === false) {
            $pos = strpos($arItem['TITLE'], ' ');

            if ($pos === false) {
                $arItem['TITLE'] = $arItem['TITLE'] . ' ' . $client;
            }
            $firstPart = substr($arItem['TITLE'], 0, $pos + 1);
            $rest = substr($arItem['TITLE'], $pos + 1);
            $arItem['TITLE'] = $firstPart . $client . ' ' . $rest;
        }

        if (stripos($arItem['TITLE'], $arItem['UF_CRM_1618824524349']) === false) {
            $arItem['TITLE'] = $arItem['TITLE'] . ' ' . $arItem['UF_CRM_1618824524349'];
        }

        return $arItem['TITLE'];
    }

    public static function changeDealType($id = false): bool
    {
        if (!($id)) {
            return false;
        }

        $arDeals = \Bitrix\Crm\DealTable::getList([
            'order' => ['ID' => 'DESC'],
            'filter' => ['ID' => $id],
            'select' => ['ID', 'TYPE_ID', 'UF_CRM_CLIENT_CONTRACT', 'CLOSED', 'CATEGORY_ID', 'STAGE_ID', 'UF_CRM_PAYMENT_DATE'],
        ])->fetchAll();
        foreach ($arDeals as $deal) {
            if ($deal['CLOSED'] == 'N') {

                $updateFields['IS_REPEATED_APPROACH'] = 'N';

                if ($deal['TYPE_ID'] == null) {
                    $updateFields['TYPE_ID'] = 'SERVICES_PREPAY';
                }

                $firstSourceStatus = \CCrmStatus::GetFirstStatusID('DEAL_STAGE_' . $deal['CATEGORY_ID']);

                if ($deal['TYPE_ID'] == 'SERVICES_PREPAY' && $deal['!UF_CRM_CLIENT_CONTRACT'] == null && $deal['STAGE_ID'] == $firstSourceStatus) {
                    $today = new \DateTime();
                    $payment = $deal['UF_CRM_PAYMENT_DATE'];
                    if (!$payment instanceof \DateTime) {
                        $payment = new \DateTime($deal['UF_CRM_PAYMENT_DATE']);
                    }

                    $interval = $today->diff($payment);
                    $days = (int)$interval->format('%r%a');
                    if ($days > 7) {
                        $updateFields['TYPE_ID'] = 'SERVICES_POSTPAY';
                    }
                }
                $result = \Bitrix\Crm\DealTable::update($id, $updateFields);
            }
        }

        return true;
    }


    public static function updateOverdueDebt(int $dealId = 0): string
    {
        if (empty($dealId)) {
            return 'Не передан корректный ID сделки';
        }

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
        if (!$factory) {
            return 'Не удалось получить фабрику сделок';
        }

        $deal = $factory->getItem($dealId);
        if (!$deal) {
            return "Сделка {$dealId} не найдена";
        }

        $newOverdueDebt = 0;
        $now = new \DateTimeImmutable();

        $productRows = $deal->get('PRODUCT_ROWS');
        $integrationBan = $deal->get('UF_CRM_ONEC_INTEGRATION_BAN');
        $stageSemanticId = $deal->get('STAGE_SEMANTIC_ID');
        $categoryId = $deal->get('CATEGORY_ID');
        $shipmentProcent = $deal->get('UF_CRM_SHIPMENT_PROCENT');
        $stageId = $deal->get('STAGE_ID');
        $currencyId = $deal->get('CURRENCY_ID');

        $paySum1C = self::extractMoneyValue($deal->get('UF_CRM_PAY_SUM'));
        $overdueDebt = self::extractMoneyValue($deal->get('UF_CRM_OVERDUE_DEBT'));
        $dealSum = self::extractMoneyValue($deal->get('OPPORTUNITY'));
        $payDateRaw = $deal->get('UF_CRM_DEAL_PAYMENT_LASTDATE');

        if ($currencyId == 'USD') {
            $nowDate = $now->format('d.m.Y');
            $current = Converter::getCurrencyForDate($nowDate);
            $dealSum = $current * $dealSum;
            $paySum1C = $current * $paySum1C;
        }

        $payDate = new \DateTimeImmutable($payDateRaw);

        if ($payDate < $now) {
            $newOverdueDebt = max(0, $dealSum - $paySum1C);
        }


        if ($stageSemanticId == 'F' || //для проигранных
            str_contains($stageId, 'NEW') || // в стадии черновик
            $categoryId == 8 || //тестовая воронка
            $dealSum == 0 || //без суммы сделок
            $shipmentProcent == 0 || //без отгрузок
            $integrationBan == 'Y' ||
            empty($productRows) //без товаров
        ) {
            $newOverdueDebt = 0;
        }

        if ($newOverdueDebt === $overdueDebt) {
            return "Обновление сделки {$dealId} не требуется. Просроченная задолженность {$newOverdueDebt} руб.";
        }

        $deal->set('UF_CRM_OVERDUE_DEBT', $newOverdueDebt . '|RUB');
        $saveResult = $deal->save();
//        $operation = $factory->getUpdateOperation($deal);
//        $operation->enableBizProc();
//
//        $saveResult = $operation->launch();


        if (!$saveResult->isSuccess()) {
            return "Ошибка сохранения сделки {$dealId}: " . implode(', ', $saveResult->getErrorMessages());
        }

        return "Обновляем сделку {$dealId}. Было {$overdueDebt}. Новое значение {$newOverdueDebt}";
    }

    /**
     * Извлекает числовую часть из значения формата "1000|RUB"
     */
    private static function extractMoneyValue($value): float
    {
        if (empty($value)) {
            return 0;
        }

        if (is_numeric($value)) {
            return (float)$value;
        }

        if (is_string($value)) {
            $parts = explode('|', $value);
            return isset($parts[0]) && is_numeric($parts[0]) ? (float)$parts[0] : 0;
        }

        return 0;
    }

    public static function updateAllOverdueDebts()
    {
        $arDeals = \Bitrix\Crm\DealTable::getList([
            'filter' => [
                '!=UF_CRM_DEAL_PAYMENT_LASTDATE' => null,
                '!CATEGORY_ID' => 8,
                //  '<DATE_MODIFY' => '31.03.2026',
            ],
            'select' => ["ID"],
            //'limit' => 100,
        ]);
        while ($deal = $arDeals->fetch()) {
            self::updateOverdueDebt($deal['ID']);
            // $ar[] = $deal['ID'];
        }
        // d($ar);
    }

    public static function updateCompanyDealsOverdueDebts(int $companyId = 0)
    {
        if (empty($companyId)) {
            return;
        }
        $arDeals = \Bitrix\Crm\DealTable::getList([
            'filter' => [
                '!=UF_CRM_DEAL_PAYMENT_LASTDATE' => null,
                '!CATEGORY_ID' => 8,
                'COMPANY_ID' => $companyId,
            ],
            'select' => ["ID"],
        ]);
        while ($deal = $arDeals->fetch()) {
            self::updateOverdueDebt($deal['ID']);
            // $ar[] = $deal['ID'];
        }
        // d($ar);
    }

    public static function updateContractDealsOverdueDebts(int $contractId = 0)
    {
        if (empty($contractId)) {
            return;
        }
        $arDeals = \Bitrix\Crm\DealTable::getList([
            'filter' => [
                '!=UF_CRM_DEAL_PAYMENT_LASTDATE' => null,
                '!CATEGORY_ID' => 8,
                'UF_CRM_CLIENT_CONTRACT' => $contractId,
            ],
            'select' => ["ID"],
        ]);
        while ($deal = $arDeals->fetch()) {
            self::updateOverdueDebt($deal['ID']);
            // $ar[] = $deal['ID'];
        }
        //  d($ar);
    }

    public static function resendTodayDealsToOne($date = null): bool
    {
        if (!\Bitrix\Main\Loader::includeModule('crm')) {
            return false;
        }
        $date = $date ?: date('d.m.Y');

        try {
            $dayStartPhp = new \DateTime($date . ' 00:00:00');
            $dayEndPhp = new \DateTime($date . ' 23:59:59');
        } catch (\Throwable $e) {
            self::notifyBot('Некорректная дата для resendTodayDealsToOne: ' . $date);
            return false;
        }

        $dayStart = \Bitrix\Main\Type\DateTime::createFromPhp($dayStartPhp);
        $dayEnd = \Bitrix\Main\Type\DateTime::createFromPhp($dayEndPhp);

        $context = new \Bitrix\Crm\Service\Context();
        $context->setUserId(1);

        $filter = [
            '>=DATE_MODIFY' => $dayStart,
            '<=DATE_MODIFY' => $dayEnd,

            //    '!STAGE_ID' => ['C9:NEW', 'C1:NEW', 'C2:NEW', 'C3:NEW', 'C4:NEW', 'C5:NEW', 'C6:NEW'], //стадии черновика
            '!STAGE_ID' => ['%:NEW'], //стадии черновика

            '!STAGE_SEMANTIC_ID' => 'F', // неуспешные сделки

            '!UF_CRM_DEAL_3867773618127' => false, //Номер 1с

            '!CATEGORY_ID' => [8, 0],
        ];

        $dealIds = [];
        $updatedIds = [];

        $res = \Bitrix\Crm\DealTable::getList([
            'select' => ['ID'],
            'filter' => $filter,
           // 'limit' => 1,
        ]);

        while ($row = $res->fetch()) {
            $dealIds[] = (int)$row['ID'];
        }

        if (count($dealIds) > 500) {
            \Bitrix\Main\Loader::includeModule('im');
            \CIMNotify::Add([
                'TO_USER_ID' => 106, //танаис бот
                'FROM_USER_ID' => 1,
                'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
                'NOTIFY_MODULE' => 'tanais.alter',
                'MESSAGE' => 'Запуск \Tanais\Alter\Crm\Deal::resendTodayDealsToOne не выполнен: найдено более 500 сделок.',
            ]);
            return false;
        }

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);

        foreach ($dealIds as $dealId) {
            //  $item = $factory->getItem($dealId);

//            if (!$item) {
//                continue;
//            }
            // "Дополнительно об источнике"
//            $sourceDescription = $item->get('UF_CRM_UF_DEBUG');
//            $sourceDescription .= '.';
//            $item->set('UF_CRM_UF_DEBUG', $sourceDescription);
//
//            $operation = $factory->getUpdateOperation($item, $context);
//            $operation->disableCheckAccess();
//
//            $result = $operation->launch();
            //       $dealId = 31197;

            $data = [
                'id' => $dealId,
                'fields' => [
                    'UF_CRM_UF_DEBUG' => time(),
                ],
            ];
            try {
                $webhook = 'https://bitrix.agroplem.ru/rest/1/y28ul9dnvqsh57mt/crm.deal.update.json';
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $webhook,
                    CURLOPT_POST => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POSTFIELDS => http_build_query($data),
                ]);

                $curlResult = curl_exec($ch);
                $curlError = curl_error($ch);
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

                curl_close($ch);

                if ($curlError) {
                    $error = $curlError;
                    \Tanais\Alter\Log::saveToFile('resendTodayDealsToOne', $error, FILE_APPEND);
                }

                if ($httpCode < 200 || $httpCode >= 300) {
                    $error = 'HTTP ошибка: ' . $httpCode . '. Ответ: ' . $curlResult;
                    \Tanais\Alter\Log::saveToFile('resendTodayDealsToOne', $error . $curlResult, FILE_APPEND);
                }

                $response = json_decode($curlResult, true);

                if (!is_array($response)) {
                    $error = 'Некорректный JSON-ответ: ' . $curlResult;
                    \Tanais\Alter\Log::saveToFile('resendTodayDealsToOne', $error, FILE_APPEND);
                }

                if (isset($response['error'])) {
                    $errorDescription = $response['error_description'] ?? '';
                    $error = 'REST ошибка: ' . $response['error'] . ' — ' . $errorDescription;
                    \Tanais\Alter\Log::saveToFile('resendTodayDealsToOne', 'REST ошибка: ' . $error, FILE_APPEND);
                }

                $updatedIds[] = $dealId;

            } catch (\Throwable $e) {
                \Bitrix\Main\Loader::includeModule('im');
                \CIMNotify::Add([
                    'TO_USER_ID' => 106, //танаис бот
                    'FROM_USER_ID' => 1,
                    'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
                    'NOTIFY_MODULE' => 'tanais.alter',
                    'MESSAGE' => 'Ошибка обновления сделки \Tanais\Alter\Crm\Deal::resendTodayDealsToOne ID: ' . $dealId . ': ' . $error,
                ]);
                continue;
            }
        }
        \Bitrix\Main\Loader::includeModule('im');
        \CIMNotify::Add([
            'TO_USER_ID' => 106, //танаис бот
            'FROM_USER_ID' => 1,
            'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
            'NOTIFY_MODULE' => 'tanais.alter',
            'MESSAGE' => 'Обновлены сделки ' . implode(', ', $updatedIds),
        ]);

        return true;
    }

    public static function resendTodayDealsToYear(): bool
    {
        if (!\Bitrix\Main\Loader::includeModule('crm')) {
            return false;
        }
        $todayStart = \Bitrix\Main\Type\DateTime::createFromPhp(
            new \DateTime('today 00:00:00')
        );

        $todayEnd = \Bitrix\Main\Type\DateTime::createFromPhp(
            new \DateTime('today 23:59:59')
        );

        $filter = [
            '>=DATE_CREATE' => \Bitrix\Main\Type\DateTime::createFromPhp(
                new \DateTime('2026-05-01 00:00:00')
            ),
            '<=DATE_CREATE' => \Bitrix\Main\Type\DateTime::createFromPhp(
                new \DateTime('2026-06-01 23:59:59')
            ),
            [
                'LOGIC' => 'OR',
                '<DATE_MODIFY' => $todayStart,
                '>DATE_MODIFY' => $todayEnd,
            ],
            '!STAGE_ID' => ['%:NEW'], //стадии черновика

            '!STAGE_SEMANTIC_ID' => 'F', // неуспешные сделки

            '!UF_CRM_DEAL_3867773618127' => false, //Номер 1с

            '!CATEGORY_ID' => [8, 0],
        ];

        $dealIds = [];
        $updatedIds = [];

        $res = \Bitrix\Crm\DealTable::getList([
            'select' => ['ID'],
            'filter' => $filter,
            //  'limit' => 20,
        ]);

        while ($row = $res->fetch()) {
            $dealIds[] = (int)$row['ID'];
        }

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);

        foreach ($dealIds as $dealId) {

            $data = [
                'id' => $dealId,
                'fields' => [
                    'UF_CRM_UF_DEBUG' => time(),
                ],
            ];
            $webhook = 'https://bitrix.agroplem.ru/rest/1/y28ul9dnvqsh57mt/crm.deal.update.json';
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $webhook,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => http_build_query($data),
            ]);

            $result = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);


            if (!$error) {
                $updatedIds[] = $dealId;
            }

            if ($error) {
                \Bitrix\Main\Loader::includeModule('im');
                \CIMNotify::Add([
                    'TO_USER_ID' => 106, //танаис бот
                    'FROM_USER_ID' => 1,
                    'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
                    'NOTIFY_MODULE' => 'tanais.alter',
                    'MESSAGE' => 'Ошибка обновления сделки \Tanais\Alter\Crm\Deal::resendTodayDealsToOne ID: ' . $dealId . ': ' . $error,
                ]);
            }
        }
        \Bitrix\Main\Loader::includeModule('im');
        \CIMNotify::Add([
            'TO_USER_ID' => 106, //танаис бот
            'FROM_USER_ID' => 1,
            'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
            'NOTIFY_MODULE' => 'tanais.alter',
            'MESSAGE' => 'Обновлены сделки ID:' . implode(', ', $updatedIds) . ' в количестве ' . count($updatedIds),
        ]);

        return true;
    }
}
