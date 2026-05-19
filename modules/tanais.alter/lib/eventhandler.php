<?

namespace Tanais\Alter;

use Bitrix\Main\EventResult;
use Bitrix\Main\Event;

class EventHandler
{
    const LOG_PATH = '/home/bitrix/www/local/log/tanais/alter';
    const EVENT_HANDLERS = [
        //в начале визуальной части пролога сайта
        ["main", "OnProlog", "tanais.alter", "\Tanais\Alter\EventHandler", "doOnProlog"],
        //в начале выполняемой части пролога сайта, после подключения всех библиотек и отработки агентов
        ["main", "OnPageStart", "tanais.alter", "\Tanais\Alter\EventHandler", "doOnPageStart"],
        ['crm', 'onEntityDetailsTabsInitialized', "tanais.alter", "\Tanais\Alter\EventHandler", 'setCustomTabs'],
        ["crm", "OnAfterCrmControlPanelBuild", "tanais.alter", "\Tanais\Alter\EventHandler", "doOnAfterCrmControlPanelBuild"],
        ["documentgenerator", "onCreateDocument", "tanais.alter", "\Tanais\Alter\Crm\DocumentGenerator", "CombainedContractCreate"],
        ["documentgenerator", "onUpdateDocument", "tanais.alter", "\Tanais\Alter\Crm\DocumentGenerator", "CombainedContractCreate"],
        ['crm', 'OnAfterCrmDealUpdate', "tanais.alter", "\Tanais\Alter\EventHandler", 'h_onAfterCrmDealAddUpdate'],
        ['crm', 'OnAfterCrmDealAdd', "tanais.alter", "\Tanais\Alter\EventHandler", 'h_onAfterCrmDealAddUpdate'],
        ['crm', 'OnAfterCrmLeadUpdate', "tanais.alter", "\Tanais\Alter\EventHandler", 'h_onAfterCrmLeadAddUpdate'],
        ['crm', 'OnAfterCrmLeadAdd', "tanais.alter", "\Tanais\Alter\EventHandler", 'h_onAfterCrmLeadAddUpdate'],
        ['crm', 'OnBeforeCrmDealUpdate', "tanais.alter", "\Tanais\Alter\EventHandler", 'h_onBeforeCrmDealUpdate'],
        ['crm', 'OnBeforeCrmDealAdd', "tanais.alter", "\Tanais\Alter\EventHandler", 'h_onBeforeCrmDealAdd'],
        ["crm", "OnBeforeCrmCompanyUpdate", "tanais.alter", "\Tanais\Alter\EventHandler", "doOnBeforeCrmCompanyUpdate"],
        ["crm", "OnAfterCrmDealProductRowsSave", "tanais.alter", "\Tanais\Alter\EventHandler", "doOnAfterCrmDealProductRowsSave"],
        ['iblock', 'OnAfterIBlockElementUpdate', "tanais.alter", "\Tanais\Alter\EventHandler", 'doOnAfterIBlockElementUpdate'],
        ['iblock', 'OnAfterIBlockElementAdd', "tanais.alter", "\Tanais\Alter\EventHandler", 'doOnAfterIBlockElementAdd'],
        ['iblock', 'OnBeforeIBlockElementUpdate', "tanais.alter", "\Tanais\Alter\EventHandler", 'doOnBeforeIBlockElementUpdate'],
    ];

    public static function registerHandlers()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        foreach (self::EVENT_HANDLERS as $handler)
            $eventManager->registerEventHandler($handler[0], $handler[1], $handler[2], $handler[3], $handler[4]);
    }

    public static function unRegisterHandlers()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        foreach (self::EVENT_HANDLERS as $handler)
            $eventManager->unregisterEventHandler($handler[0], $handler[1], $handler[2], $handler[3], $handler[4]);
    }

    public static function listHandlers($module = 'main', $event = 'OnBuildGlobalMenu')
    {
        echo "<hr><pre>" . var_export(GetModuleEvents($module, $event, true), true) . "</pre><hr>";
    }


    public static function doOnProlog()
    {
        $scriptURL = $_SERVER['SCRIPT_URL'];

        if (str_contains($scriptURL, '/crm/catalog/'))
            \Bitrix\Main\UI\Extension::load('tanais.alter.crm.productcatalog.priceListButton');
        if (str_contains($scriptURL, '/bi/dashboard/detail/24/'))
            \Bitrix\Main\UI\Extension::load('tanais.alter.crm.dashboardcompanyfilter');
    }

    public static function doOnPageStart()
    {
    }


    static function setCustomTabs(Event $event): EventResult
    {
        $entityId = $event->getParameter('entityID');
        $entityTypeID = $event->getParameter('entityTypeID');
        $tabs = $event->getParameter('tabs');

        $crmCustomTabManager = new TabManager();

        $tabs = $crmCustomTabManager->getActualEntityTab($entityId, $entityTypeID, $tabs);

        return new EventResult(EventResult::SUCCESS, [
            'tabs' => $tabs,
        ]);
    }

    public static function doOnAfterCrmControlPanelBuild(&$menuItems)
    {
        foreach ($menuItems as &$item) {
            if ($item['ID'] == 'crm_analytics') {
                $item['ITEMS'][] = [
                    'ID' => 'PROBE_CALENDAR_MSK',
                    'NAME' => 'Календарь проб Молоко Москва',
                    'URL' => '/alter/calendar/?lab=800',
                    'IS_ACTIVE' => false,
                    'TEXT' => 'Календарь проб Молоко Москва',
                ];
                $item['ITEMS'][] = [
                    'ID' => 'PROBE_CALENDAR_EKB',
                    'NAME' => 'Календарь проб Молоко Екатеринбург',
                    'URL' => '/alter/calendar/?lab=811',
                    'IS_ACTIVE' => false,
                    'TEXT' => 'Календарь проб Молоко Екатеринбург',
                ];
                $item['ITEMS'][] = [
                    'ID' => 'PROBE_CALENDAR_SOIL',
                    'NAME' => 'Календарь проб лаборатории почв',
                    'URL' => '/alter/calendar/index-soil.php',
                    'IS_ACTIVE' => false,
                    'TEXT' => 'Календарь проб лаборатории почв',
                ];
                $item['ITEMS'][] =
                    [
                        'ID' => 'CUSTOM_REPORTS_AGR',
                        'MENU_ID' => 'CUSTOM_REPORTS_AGR',
                        'NAME' => 'Кастомные отчеты Агроплем',
                        'TITLE' => 'Кастомные отчеты Агроплем',
                        'URL' => '/alter/report/',
                    ];
            }

            $currentUser = \Bitrix\Main\Engine\CurrentUser::get();
            if (in_array($currentUser->getId(), [106, 6,])) {
                if ($item['ID'] == 'crm_catalogue') {
                    $item['ITEMS'][] = [
                        'ID' => 'COPY_PRICE',
                        'NAME' => 'Копирование розничной цены',
                        'URL' => '/alter/crm/updateprice.php',
                        'IS_ACTIVE' => false,
                        'TEXT' => 'Копирование розничной цены',
                    ];
                }
            }

            //Сортировка в пункте меню Смарт-процессов
            if ($item['ID'] == 'DYNAMIC_ITEMS') {

                $item['ITEMS'][] =
                    [
                        'ID' => 'DELIMITER_CLIENT',
                        'IS_DELIMITER' => true,
                        'IS_ACTIVE' => false,
                        'TEXT' => 'Клиентские данные',
                    ];

                $item['ITEMS'][] =
                    [
                        'ID' => 'DELIMITER_PROCESS',
                        'IS_DELIMITER' => true,
                        'IS_ACTIVE' => false,
                        'TEXT' => 'Процессы',
                    ];

                $order = [
                    // 'DELIMITER_CLIENT',
                    'DYNAMIC_1074', // Договор с поставщиком
                    'DYNAMIC_1046', // Счет поставщика на оплату
                    'DELIMITER_PROCESS',
                    'DYNAMIC_1038', // Несоответствие
                    'DYNAMIC_1062', // Изменение услуг
                    'DYNAMIC_1078', // Транспортные накладные
                    'DELIMITER_CLIENT',
                    'DYNAMIC_1042', // Заключения
                    'DYNAMIC_1050', // Договор с клиентом
                    'DYNAMIC_1070', // Поступление оплаты от клиента
                ];

                usort($item['ITEMS'], function ($a, $b) use ($order) {
                    $posA = array_search($a['ID'], $order);
                    $posB = array_search($b['ID'], $order);

                    // Если ID нет в порядке, ставим его в конец
                    if ($posA === false) {
                        $posA = PHP_INT_MAX;
                    }
                    if ($posB === false) {
                        $posB = PHP_INT_MAX;
                    }

                    return $posA - $posB;
                });
                \Tanais\Alter\Log::saveToFile('doOnAfterCrmControlPanelBuild', $menuItems);
            }
        }


        // Логирование вызова
        // \Tanais\Alta\Log::saveToFile('doOnAfterCrmControlPanelBuild', $menuItems);
    }

    public static function h_onAfterCrmLeadAddUpdate(&$arFields)
    {
        \Tanais\Alter\Crm\Product::checkArchivedProducts($arFields['ID'], 1, $arFields['MODIFY_BY_ID']);
    }

    public static function doOnAfterIBlockElementAdd(&$arFields)
    {
        if ($arFields['IBLOCK_ID'] == \Tanais\Alter\Crm\Catalog::PRODUCT_IBLOCK_ID) {

            \Tanais\Alter\Crm\Product::changeProductsImage($arFields['ID']);

            // \Tanais\Alter\Log::saveToFile('doOnAfterIBlockElementUpdate', $arFields);
        }
    }

    public static function doOnAfterIBlockElementUpdate(&$arFields)
    {
        if ($arFields['IBLOCK_ID'] == \Tanais\Alter\Crm\Catalog::PRODUCT_IBLOCK_ID) {

            \Tanais\Alter\Crm\Product::changeProductsImage($arFields['ID']);

            // \Tanais\Alter\Log::saveToFile('doOnAfterIBlockElementUpdate', $arFields);
        }
    }

    public static function doOnBeforeIBlockElementUpdate(&$arFields)
    {
        if ($arFields['IBLOCK_ID'] == \Tanais\Alter\Crm\Catalog::OFFER_IBLOCK_ID) {
            \Bitrix\Catalog\ProductTable::update($arFields['ID'], ["VAT_INCLUDED" => 'Y']);
        }
    }


    public static function h_OnBeforeCrmDealAdd(&$arFields)
    {
        file_put_contents('/home/bitrix/www/local/log/deal.log', "--- " . date("Y-m-d H:i:s") . "\r\n", FILE_APPEND);
        file_put_contents('/home/bitrix/www/local/log/deal.log', "add start " . $arFields['ID'] . "\r\n\r\n", FILE_APPEND);
        file_put_contents('/home/bitrix/www/local/log/deal.log', var_export($arFields, true) . "\r\n\r\n", FILE_APPEND);
        //--------------------------------------------------------------
        // СОЗДАНИЕ. Если установлена при создании компания, то выбираем самый последний договор с ней
        if (!empty($arFields['COMPANY_ID']) && empty($arFields['UF_CRM_CLIENT_CONTRACT'])) {
            $company_lab = $arFields['UF_CRM_LABORATORY'];
            $entityTypeId = 1050;     //Смарт-процесс Договоры с клиентами
            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
            $arFilter = ['COMPANY_ID' => $arFields['COMPANY_ID'], 'UF_CRM_6_LABORATORIES' => [$company_lab]];
            $contract_id = null;
            $sp_contracts = $factory->getItems(['select' => [], 'filter' => $arFilter,]);
            //Найдем с максимальным ID и вытащим его данные
            foreach ($sp_contracts as $sp_contract) {
                if ($sp_contract['ID'] > $contract_id) {
                    $contract_id = $sp_contract['ID'];
                    $contract_number = $sp_contract['UF_CRM_6_CONTRACT_NUMBER'];
                    $contract_date = $sp_contract['UF_CRM_6_CONTRACT_SIGNDATE'];
                }
            }
            //Если есть номер договора , то сохраним в сделке
            if ($contract_id) {
                $arFields['UF_CRM_CLIENT_CONTRACT'] = $contract_id;
            }
        }
        //--------------------------------------------------------------
        // СОЗДАНИЕ. Если установлен при создании контакт, то выбираем самый последний договор с ним
        if (!empty($arFields['CONTACT_ID']) && empty($arFields['UF_CRM_CLIENT_CONTRACT'])) {
            $company_lab = $arFields['UF_CRM_LABORATORY'];
            $entityTypeId = 1050;     //Смарт-процесс Договоры с клиентами
            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
            $arFilter = ['CONTACT_ID' => $arFields['CONTACT_ID'], 'UF_CRM_6_LABORATORIES' => [$company_lab]];
            $contract_id = null;
            $sp_contracts = $factory->getItems(['select' => [], 'filter' => $arFilter,]);
            //Найдем с максимальным ID и вытащим его данные
            foreach ($sp_contracts as $sp_contract) {
                if ($sp_contract['ID'] > $contract_id) {
                    $contract_id = $sp_contract['ID'];
                    $contract_number = $sp_contract['UF_CRM_6_CONTRACT_NUMBER'];
                    $contract_date = $sp_contract['UF_CRM_6_CONTRACT_SIGNDATE'];
                }
            }
            //Если есть номер договора , то сохраним в сделке
            if ($contract_id) {
                $arFields['UF_CRM_CLIENT_CONTRACT'] = $contract_id;
            }
        }
        //--------------------------------------------------------------
        //пересчет валюты сделок

        if ($arFields['CURRENCY_ID'] == 'USD' and empty($arFields['UF_CRM_CONVERSION_RATE']) and empty($arFields['UF_CRM_SUM_DEAL_FOR_REPORT'])) {
            $now = new \DateTime();
            $nowDate = $now->format('d.m.Y');
            $current = \Tanais\Alter\Crm\Currency\Converter::getCurrencyForDate($nowDate);
            $arFields['UF_CRM_CONVERSION_RATE'] = $current;
            $arFields['UF_CRM_SUM_DEAL_FOR_REPORT'] = $current * $arFields['OPPORTUNITY'];
        }

        if ($arFields['OPPORTUNITY'] and $arFields['CURRENCY_ID'] == 'RUB') {
            $arFields['UF_CRM_SUM_DEAL_FOR_REPORT'] = $arFields['OPPORTUNITY'];
        }

        if ($arFields['OPPORTUNITY'] == '0.00') {
            $arFields['UF_CRM_SUM_DEAL_FOR_REPORT'] = '0.00';
        }

        if ($arFields['OPPORTUNITY'] == '') {
            $arFields['UF_CRM_SUM_DEAL_FOR_REPORT'] = '0.00';
        }

        //Проверка на существование номера заказа
        if ($arFields['UF_CRM_ORDER_NUMBER']) {

            //проверка номера заказа на русские буквы
            $transliteration = [
                'А' => 'A',
                'В' => 'B',
                'Е' => 'E',
                'К' => 'K',
                'М' => 'M',
                'О' => 'O',
                'Р' => 'P',
                'С' => 'C',
                'Т' => 'T',
                'Н' => 'H',
                'У' => 'Y',

                'а' => 'a',
                'е' => 'e',
                'к' => 'k',
                'о' => 'o',
                'р' => 'p',
                'с' => 'c',
                'у' => 'y',
            ];

            $arFields['UF_CRM_ORDER_NUMBER'] = strtr($arFields['UF_CRM_ORDER_NUMBER'], $transliteration);


            $rsdeals = \CCRMDeal::GetList(["ID" => "DESC"], ["UF_CRM_ORDER_NUMBER" => $arFields['UF_CRM_ORDER_NUMBER']], ['ID']);
            if (($deal = $rsdeals->fetch()) && (!\Bitrix\Main\Engine\CurrentUser::get()->isAdmin())) {
                $arFields['RESULT_MESSAGE'] = 'Вы не можете сохранить сделку, этот номер заказа уже используется';
                return false;
            }
        }
        //--------------------------------------------------------------
        //--- установка организации
        if ($arFields['UF_CRM_LABORATORY']) {
            if ($arFields['UF_CRM_LABORATORY'] == 800 or $arFields['UF_CRM_LABORATORY'] == 801 or $arFields['UF_CRM_LABORATORY'] == 811) {
                $arFields['UF_CRM_DEAL_3867773618165'] == 44;
            } elseif ($arFields['UF_CRM_LABORATORY'] == 810) {
                $arFields['UF_CRM_DEAL_3867773618165'] == 42;
            } else {
                $arFields['UF_CRM_DEAL_3867773618165'] == 43;
            }
        }

        //--------------------------------------------------------------
        //--- Логирование и выход
        file_put_contents('/home/bitrix/www/local/log/deal.log', "--- " . date("Y-m-d H:i:s") . "\r\n", FILE_APPEND);
        file_put_contents('/home/bitrix/www/local/log/deal.log', "add End " . $arFields['ID'] . "\r\n\r\n", FILE_APPEND);
        $logfilename = '/home/bitrix/www/local/tanais/log/h_OnBeforeCrmDealAdd.log';
        file_put_contents($logfilename, "--- " . date("Y-m-d H:i:s") . " h_OnBeforeCrmDealAdd\r\n", FILE_APPEND);
        file_put_contents($logfilename, var_export($arFields, true) . "\r\n\r\n", FILE_APPEND);
        return true;
    }

    public static function h_OnBeforeCrmDealUpdate(&$arFields)
    {
//        if ($arFields["ID"] == 22938) {
//            file_put_contents('/home/bitrix/www/local/log/deal.log', "--- " . date("Y-m-d H:i:s") . "\r\n", FILE_APPEND);
//            file_put_contents('/home/bitrix/www/local/log/deal.log', "Update start " . $arFields['ID'] . "\r\n\r\n", FILE_APPEND);
//            file_put_contents('/home/bitrix/www/local/log/deal.log', var_export($arFields, true) . "\r\n\r\n", FILE_APPEND);
//        }

//        if ($arFields["ID"] == 22938) {
//            return true;
//        }
        //--------------------------------------------------------------
        // ИЗМЕНЕНИЕ. Если меняем компанию и пустой договор, то меняем поле договора
        // $companyId = $arFields['COMPANY_ID'];
        // if (empty($companyId))
        //     $companyId = \Tanais\Alter\CRM\Deal::getFieldValue($arFields['ID'], 'COMPANY_ID');

        // if (($arFields['COMPANY_ID'] or $arFields['CONTACT_IDS']) && (empty($arFields['UF_CRM_CLIENT_CONTRACT']))) {
        //     if ($arFields['COMPANY_ID']) {
        //         $arFilter['COMPANY_ID'] = $arFields['COMPANY_ID'];
        //     } elseif ($arFields['CONTACT_IDS']) {
        //         $arFilter['CONTACT_ID'] = $arFields['CONTACT_IDS'][0];
        //     }
        //     $factoryClientContract = \Bitrix\Crm\Service\Container::getInstance()->getFactory(1050);
        //     $contract_id = null;
        //     $sp_contracts = $factory->getItems(['select' => [], 'filter' => $arFilter,]);

        //Найдем с максимальным ID
        //     foreach ($sp_contracts as $sp_contract) {
        //         if ($sp_contract['ID'] > $contract_id) {
        //             $contract_id = $sp_contract['ID'];
        //             $contract_number = $sp_contract['UF_CRM_6_CONTRACT_NUMBER'];
        //             $contract_date = $sp_contract['UF_CRM_6_CONTRACT_SIGNDATE'];
        //         }
        //     }
        //     //Если есть новый номер договора, то обновим. Если нет, то сотрём
        //     if ($contract_id) {
        //         $arFields['UF_CRM_CLIENT_CONTRACT'] = $contract_id;
        //     } else {
        //         $arFields['UF_CRM_CLIENT_CONTRACT'] = null;
        //     }
        // }
        //--------------------------------------------------------------
        // Если при обновлении сделки "Менеджер по продажам" пусто копируем его из компании
        //        if (($arFields['UF_CRM_SALESMANAGER'] == '') && ($arFields['COMPANY_ID'] != '')) {
        //            $rscompany = \CCrmCompany::GetList(["ID" => "DESC"], ["ID" => $arFields['COMPANY_ID']], ['ID', 'TITLE', 'UF_CRM_SALESMANAGER']);
        //            if ($company = $rscompany->GetNext()) {
        //                $arFields['UF_CRM_SALESMANAGER'] = $company['UF_CRM_SALESMANAGER'];
        //            }
        //        }

        //--------------------------------------------------------------
        // Если меняется поле "Дата поступления проб в лабораторию"(UF_CRM_632046BBB65E4) и "Дата выдачи заключения (факт) ·"(UF_CRM_1570104297940),
        // то обновляем поле "Количество затраченных дней ·"(UF_CRM_SPENT_DAYS)
        $dateReceiptSamples = 'UF_CRM_632046BBB65E4';
        $dateReportRelease = 'UF_CRM_1570104297940';
        if ((!empty($arFields[$dateReceiptSamples])) || (!empty($arFields[$dateReportRelease]))) {
            \Bitrix\Main\Loader::includeModule('crm');
            $deals = \CCRMDeal::GetList(["ID" => "DESC"], ["ID" => $arFields['ID']], [$dateReceiptSamples, $dateReportRelease, 'CLOSED']);
            if ($deal = $deals->fetch()) {
                if ($deal['CLOSED'] == 'N') {
                    if (isset($arFields[$dateReceiptSamples]))
                        $receiptSamples = $arFields[$dateReceiptSamples];
                    else
                        $receiptSamples = $deal[$dateReceiptSamples];

                    if (isset($arFields[$dateReportRelease]))
                        $reportRelease = $arFields[$dateReportRelease];
                    else
                        $reportRelease = $deal[$dateReportRelease];

                    $receiptSamples = date_create_from_format('d.m.Y', $receiptSamples);
                    $reportRelease = date_create_from_format('d.m.Y', $reportRelease);

                    if ($receiptSamples && $reportRelease) {
                        $diff = date_diff($receiptSamples, $reportRelease);
                        $diff = $diff->format('%a');
                        $arFields['UF_CRM_SPENT_DAYS'] = $diff;
                    } else
                        $arFields['UF_CRM_SPENT_DAYS'] = '';
                }
            }
        }
        //--------------------------------------------------------------
        // Если сделка в стадии выиграно, то записываем дату в поле "Фактическая дата выставления УПД" текущая дата, если оно пустое
        if (substr($arFields['STAGE_ID'], -3) == 'WON') {
            $deal = \Bitrix\Crm\DealTable::getList(['order' => ['ID' => 'DESC'], 'filter' => ['ID' => $arFields['ID']], 'limit' => 1, 'select' => ['UF_CRM_1777477907', 'UF_CRM_1636553061'],])->fetch();
            $integrationBan = $arFields['UF_CRM_1777477907'] ? $arFields['UF_CRM_1777477907'] : $deal['UF_CRM_1777477907'];
            if ($integrationBan != 805 && empty($deal['UF_CRM_1636553061'])) {
                $arFields['UF_CRM_1636553061'] = date('d.m.Y');
            }
        }
        //--------------------------------------------------------------
        //пересчет валюты сделок
        $rsdeals = \CCRMDeal::GetList(["ID" => "DESC"], array("ID" => $arFields['ID']));
        if ($deal = $rsdeals->Fetch()) {
            if ($deal['CURRENCY_ID'] == 'USD' and empty($deal['UF_CRM_CONVERSION_RATE']) and empty($deal['UF_CRM_SUM_DEAL_FOR_REPORT'])) {
                $now = new \DateTime();
                $nowDate = $now->format('d.m.Y');
                $current = \Tanais\Alter\Crm\Currency\Converter::getCurrencyForDate($nowDate);
                $arFields['UF_CRM_CONVERSION_RATE'] = $current;
                $arFields['UF_CRM_SUM_DEAL_FOR_REPORT'] = $current * $deal['OPPORTUNITY'];
            }
        }
        //пересчет валюты сделок при изменении суммы
        if ($arFields['OPPORTUNITY'] and $arFields['CURRENCY_ID'] == 'USD') {
            $now = new \DateTime();
            $nowDate = $now->format('d.m.Y');
            $current = \Tanais\Alter\Crm\Currency\Converter::getCurrencyForDate($nowDate);
            $arFields['UF_CRM_CONVERSION_RATE'] = $current;
            $arFields['UF_CRM_SUM_DEAL_FOR_REPORT'] = $current * $arFields['OPPORTUNITY'];
        }


        if ($arFields['OPPORTUNITY'] and $arFields['CURRENCY_ID'] == 'RUB') {
            $arFields['UF_CRM_SUM_DEAL_FOR_REPORT'] = $arFields['OPPORTUNITY'];
        }

        if ($arFields['OPPORTUNITY'] == '0.00') {
            $arFields['UF_CRM_SUM_DEAL_FOR_REPORT'] = '0.00';
        }

        //Проверка на существование номера заказа
        if ($arFields['UF_CRM_ORDER_NUMBER']) {
            //проверка номера заказа на русские буквы
            $transliteration = [
                'А' => 'A',
                'В' => 'B',
                'Е' => 'E',
                'К' => 'K',
                'М' => 'M',
                'О' => 'O',
                'Р' => 'P',
                'С' => 'C',
                'Т' => 'T',
                'Н' => 'H',
                'У' => 'Y',

                'а' => 'a',
                'е' => 'e',
                'к' => 'k',
                'о' => 'o',
                'р' => 'p',
                'с' => 'c',
                'у' => 'y',
            ];

            $arFields['UF_CRM_ORDER_NUMBER'] = strtr($arFields['UF_CRM_ORDER_NUMBER'], $transliteration);

            $rsdeals = \CCRMDeal::GetList(["ID" => "DESC"], ["!ID" => $arFields['ID'], "UF_CRM_ORDER_NUMBER" => $arFields['UF_CRM_ORDER_NUMBER']]);
            if ($deal = $rsdeals->fetch() && (!\Bitrix\Main\Engine\CurrentUser::get()->isAdmin())) {
                $arFields['RESULT_MESSAGE'] = 'Вы не можете сохранить сделку, этот номер заказа уже используется';
                return false;
            }
        }

        //--------------------------------------------------------------
        //--- установка организации
        if ($arFields['UF_CRM_LABORATORY']) {
            if ($arFields['UF_CRM_LABORATORY'] == 800 or $arFields['UF_CRM_LABORATORY'] == 801 or $arFields['UF_CRM_LABORATORY'] == 811) {
                $arFields['UF_CRM_DEAL_3867773618165'] = 44;
            } elseif ($arFields['UF_CRM_LABORATORY'] == 810) {
                $arFields['UF_CRM_DEAL_3867773618165'] = 42;
            } else {
                $arFields['UF_CRM_DEAL_3867773618165'] = 43;
            }
        }

        //--------------------------------------------------------------
        //--- Логирование и выход
        if ($arFields["ID"] == 22938) {
            file_put_contents('/home/bitrix/www/local/log/deal.log', "--- " . date("Y-m-d H:i:s") . "\r\n", FILE_APPEND);
            file_put_contents('/home/bitrix/www/local/log/deal.log', "UpdateEnd " . $arFields['ID'] . "\r\n\r\n", FILE_APPEND);
        }
        $logfilename = '/home/bitrix/www/local/tanais/log/h_OnBeforeCrmDealUpdate.log';
        file_put_contents($logfilename, "--- " . date("Y-m-d H:i:s") . " h_OnBeforeCrmDealUpdate\r\n", FILE_APPEND);
        file_put_contents($logfilename, var_export($arFields, true) . "\r\n\r\n", FILE_APPEND);

        return true;
    }

    public static function doOnAfterCrmDealProductRowsSave(&$id)
    {
       // \Tanais\Alter\Log::saveToFile('doOnAfterCrmDealProductRowsSave', $id);
        try {
            \Tanais\Alter\Crm\Deal::fixCents(true, $id);
        } catch (Exception $e) {
            \Tanais\Alter\Log::saveToFile('h_onAfterCrmDealAddUpdate', $id);
        }

    }

    public static function h_onAfterCrmDealAddUpdate(&$arFields)
    {
//        if ($arFields["ID"] == 22938) {
//            file_put_contents('/home/bitrix/www/local/log/deal.log', "--- " . date("Y-m-d H:i:s") . "\r\n", FILE_APPEND);
//            file_put_contents('/home/bitrix/www/local/log/deal.log', "addUpdate start " . $arFields['ID'] . "\r\n\r\n", FILE_APPEND);
//            file_put_contents('/home/bitrix/www/local/log/deal.log', var_export($arFields, true) . "\r\n\r\n", FILE_APPEND);
//        }

//        if ($arFields['ID'] == 22938) {
//            return true;
//        }
        //--------------------------------------------------------------
        //Если нет компании и пустой менеджер по продажам
        $arDeals = \CCRMDeal::GetList(["ID" => "DESC"], ["ID" => $arFields['ID']], ['CONTACT_ID', 'COMPANY_ID', 'ID', 'UF_CRM_SALESMANAGER']);
        if ($deal = $arDeals->fetch()) {
            if (empty($deal['UF_CRM_SALESMANAGER']) and empty($deal['COMPANY_ID']) and !empty($deal['CONTACT_ID'])) {
                $rscontact = \CCrmContact::GetList(["ID" => "DESC"], ["ID" => $deal['CONTACT_ID']], ['ID', 'TITLE', 'UF_CRM_SALESMANAGER']);
                if ($contact = $rscontact->fetch()) {
                    if ($contact['UF_CRM_SALESMANAGER']) {
                        $fields['UF_CRM_SALESMANAGER'] = $contact['UF_CRM_SALESMANAGER'];
                        $deal = new \CCrmDeal;
                        $deal->Update($arFields['ID'], $fields);
                    }
                }
            }
        }
        //--------------------------------------------------------------
        // если UF_CRM_LABORATORY из сделки не указан в компании, то добавляем его
        //убран 21.04.2026 новый механизм обновления через крон
//        $rsdeals = \CCRMDeal::GetList(["ID" => "DESC"], array("ID" => $arFields['ID']));
//        if ($deal = $rsdeals->GetNext()) {
//            if (!empty($deal['COMPANY_ID'])) {
//                $rscompanies = \CCrmCompany::GetList(array("ID" => "DESC"), array("ID" => $deal['COMPANY_ID']));
//                if ($company = $rscompanies->GetNext()) {
//                    if (empty($company['UF_CRM_LABORATORIES']) or !in_array($deal['UF_CRM_LABORATORY'], $company['UF_CRM_LABORATORIES'])) {
//                        $arFields2 = ['UF_CRM_LABORATORIES' => $company['UF_CRM_LABORATORIES'],];
//                        if (empty($arFields2['UF_CRM_LABORATORIES']))
//                            $arFields2['UF_CRM_LABORATORIES'] = [];
//                        array_push($arFields2['UF_CRM_LABORATORIES'], $deal['UF_CRM_LABORATORY']);
//                        $ccrmcompany = new \CCrmCompany(false);
//                        $ccrmcompany->update($company['ID'], $arFields2);
//                    }
//                }
//            }
//        }


        // Если при Добавлении/обновлении сделки было записано  "Договор с клиентом", то обновляем Товары
        if (array_key_exists('UF_CRM_CLIENT_CONTRACT', $arFields)) {
            \Tanais\Alter\Crm\Deal::updateDealProductsPriceByContractPrice($arFields['ID']);
        }

        \Tanais\Alter\Crm\Product::checkArchivedProducts($arFields['ID'], 2, $arFields['MODIFY_BY_ID']);


        //--------------------------------------------------------------
        //--- Логирование и выход
        if ($arFields["ID"] == 22938) {
            file_put_contents('/home/bitrix/www/local/log/deal.log', "--- " . date("Y-m-d H:i:s") . "\r\n", FILE_APPEND);
            file_put_contents('/home/bitrix/www/local/log/deal.log', "addUpdateEnd " . $arFields['ID'] . "\r\n\r\n", FILE_APPEND);
            \Tanais\Alter\Log::saveToFile('h_onAfterCrmDealAddUpdate', $arFields);
        }
        $logfilename = '/home/bitrix/www/local/tanais/log/h_onAfterCrmDealAddUpdate.log';
        file_put_contents($logfilename, "--- " . date("Y-m-d H:i:s") . " h_onAfterCrmDealAddUpdate\r\n", FILE_APPEND);
        file_put_contents($logfilename, var_export($arFields, true) . "\r\n\r\n", FILE_APPEND);
        return true;
    }


    public static function doOnBeforeCrmCompanyUpdate(&$arFields)
    {
        if (!\Bitrix\Main\Engine\CurrentUser::get()->isAdmin() && $arFields['IS_MY_COMPANY'] == 'Y') {
            $errorMessage = 'Запрещено. Вам не нужно менять нашу компанию';
            $arFields['RESULT_MESSAGE'] = $errorMessage;
            $GLOBALS['APPLICATION']->ThrowException($errorMessage);
            return false;
        }
    }
}
