<?php

namespace Tanais\Alter\Crm;

use Tanais\Alter\Config;
use Bitrix\Crm\Service;
use Bitrix\Crm\Item;

\Bitrix\Main\Loader::requireModule('crm');
\Bitrix\Main\Loader::requireModule('im');
\Bitrix\Main\Loader::requireModule('main');
\Bitrix\Main\Loader::includeModule('tasks');
\Bitrix\Main\Loader::includeModule('iblock');

class Company
{
    const  MODULE_ID = Config::MODULE_ID;

    //Отдает значение поля fieldName из dealId
    // \Tanais\Alter\Crm\Company::getFieldValue(1123,'TITLE');
    // \Tanais\Alter\Crm\Company::getList();
    public static function getFieldValue($companyId, $fieldName)
    {
        if ((!(intval($companyId) > 0)) or (!$fieldName))
            return false;

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Company);
        $company = $factory->getItem($companyId);
        if ((is_object($company)) && ($company->hasField($fieldName)))
            return $company->get($fieldName);
        else
            return false;
    }

    //Выбирает Company по реквизитам
    //\Tanais\Alter\Crm\Company::getIdbyRequisite(['RQ_INN'=>'6211006605']) Result: array ( 4 => array ( 0 => '2177', 1 => '886', ), )
    //\Tanais\Alter\Crm\Company::getIdbyRequisite(['RQ_INN'=>'6211006605','RQ_KPP'=>'621101001'])Result:  array ( 4 => array ( 0 => '2158', ), )
    //\Tanais\Alter\Crm\Company::getIdbyRequisite(['RQ_OGRN'=>'1247700509320']) Result: array ( 4 => array ( 0 => '2158', ), )
    //\Tanais\Alter\Crm\Company::getIdbyRequisite(['RQ_INN'=>'772618927229']) Result:  array ( 3 => array ( 0 => '474', ), )
    public static function getIdbyRequisite($parameters): array
    {
        foreach ($parameters as $key => $value)
            if (empty($value))
                unset($parameters[$key]);
        if (empty($parameters))
            return [];

        $filter = [];
        $filter["ENTITY_TYPE_ID"] = [\CCRMOwnerType::Company, \CCRMOwnerType::Contact];

        $requisiteFields = \Bitrix\Crm\RequisiteTable::getMap();
        foreach ($parameters as $parameterCode => $parameterValue)
            if (array_key_exists($parameterCode, $requisiteFields))
                if ($parameterValue)
                    $filter[$parameterCode] = $parameterValue;


        \Bitrix\Main\Application::getConnection()->startTracker();
        $requisite = \Bitrix\Crm\RequisiteTable::getList([
            "select" => ["ID", "ENTITY_ID", 'ENTITY_TYPE_ID'],
            "filter" => $filter,
            "order" => ["SORT" => "asc", "ID" => "asc"]
        ]);

        $return = [];
        while ($requisiteData = $requisite->Fetch()) {
            $return[$requisiteData['ENTITY_TYPE_ID']][] = $requisiteData["ENTITY_ID"];
        }
        return $return;
    }


    // При запуске скрипта ищем компании в CRM без реквизитов
    // Отправляем уведомление о компании без реквизитов пользователям, указанным в скрипте
    // Автор Абрамов В.А 24.08.20222
    // Перенесен 22.01.2025 Дружкова М.А.
    public static function getCompanyRequisite(): array
    {
        $entityRequisite = new \Bitrix\Crm\EntityRequisite;
        $requisite = [];
        $rsRequisite = $entityRequisite->getList([
            "select" => ["RQ_OGRN", "RQ_OGRNIP", "ENTITY_ID"],
            "filter" => ["ENTITY_TYPE_ID" => 4],
            "order" => ["SORT" => "desc", "ID" => "desc"]
        ]);
        while ($ar = $rsRequisite->Fetch()) {
            if ($ar["RQ_OGRN"]) {
                $requisite[$ar["RQ_OGRN"]] = $ar["ENTITY_ID"];
            }
            if ($ar["RQ_OGRNIP"]) {
                $requisite[$ar["RQ_OGRNIP"]] = $ar["ENTITY_ID"];
            }
        }
        return $requisite;
    }

    //Настройка переменные $from_user $notify_users ниже в тексте
    public static function checkCompany(): void
    {
        $from_user = 1;            // ID юзера, от которого приходит уведомление
        //Лисова, Абрамов, Системный,
        $notify_users = [8, 4, 1];  // ID юзеров которых нужно уведомить о компаниях без реквизитов.

        $strSql =
            'select * from
                    ( select c.id as id,c.title as title,r.id as riq
                        from   		b_crm_company as c  
                        left join   (select id,entity_id,entity_type_id from b_crm_requisite where entity_type_id=4) as r 
                        on c.id=r.entity_id
                        order by c.id) as res 
                    where res.riq is null
                    order by id';

        $connection = \Bitrix\Main\Application::getConnection();
        $sqlHelper = $connection->getSqlHelper();
        $recordset = $connection->query($strSql);
        while ($record = $recordset->fetch()) {
            $companies_no_req [] = $record;
        }


        if (count($companies_no_req) > 0) {
            $message = "В CRM найдено " . count($companies_no_req) . " компании без заполненных реквизитов. Пожалуйста, введите их в карточки компании в поле Реквизиты. [BR]";
            foreach ($companies_no_req as $company) {
                $message .= "[URL=/crm/company/details/" . $company['id'] . "/]" . $company['title'] . "[/URL]" . "[BR]";
            }

            foreach ($notify_users as $notify_user) {
                $arMessageFields = array(
                    "FROM_USER_ID" => $from_user,
                    "TO_USER_ID" => $notify_user,
                    "NOTIFY_TYPE" => IM_NOTIFY_FROM,
                    "NOTIFY_MODULE" => "main",
                    "NOTIFY_TAG" => "",
                    "NOTIFY_MESSAGE" => $message,
                );
                \CIMNotify::Add($arMessageFields);
            }
        }
    }

    // Просматриваем все компании и если поле Компании Регион пустое, то вычисляем на основе реквизитов
    // Автор Абрамов В.А 24.08.20222
    // Перенесено 22.01.2025 Дружкова М.А.
    //Настройка $list_region_iblock_id $uf_code_region

//    public static function getRegionForm(): void
//    {
//        global $USER;
//        if (!($USER->IsAuthorized())) {
//            $tempUserId = 1;
//            $USER->Authorize($tempUserId);
//        }
//
//        $listRegionIblockId = 16; //ID инфоблока Списки "CRM: Регионы"
//        $ufCodeRegion = 'UF_CRM_REGION';
//
//        $companies = [];
//        //    $ccompany = new \CCrmCompany(false);
//        //  $rscompanies = $ccompany->GetList(array("ID" => "DESC"), array("!STATUS_ID" => "D"));
//        $rsCompanies = \Bitrix\Crm\CompanyTable::getList([
//            'order' => ['ID' => 'DESC'],
//            'filter' => ['ID' => 613],
//            'select' => ['ID']
//        ]);
//
//        $req = new \Bitrix\Crm\EntityRequisite();
//        $arSelect = ["ID", "NAME"];
//        $arFilter = ["IBLOCK_ID" => $listRegionIblockId, "%NAME" => ""];
//
//        while ($company = $rsCompanies->fetch()) {
//            d($company);
//            $rs = $req->getList([
//                'filter' => [
//                    'ENTITY_ID' => $company['ID'],
//                    'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
//                    'PRESET_ID' => 1
//                ],
//                'select' => ['ID'],
//            ]);
//            $rq = $rs->fetch();
//            d($rq);
//            $addresses = \Bitrix\Crm\EntityRequisite::getAddresses($rq['ID']);
//            d($addresses);
//            foreach ($addresses as $address) {
//                if (!empty($company['RQ_REGION_F'])) {
//                    continue;
//                }
//                $company['RQ_REGION_F'] = $address['PROVINCE'];
//                // $company['RQ_REGION'] = $address[1]['PROVINCE'];
//                $company['RQ_REGION'] = trim($address['PROVINCE']);
//                if (($company[$ufCodeRegion] == null) && (empty($company['RQ_REGION']))) {
//                    $adrStr = explode(', ', $address['ADDRESS_1']);
//                    $company['RQ_REGION'] = trim($adrStr[1]);
//                }
//            }
//            d($company);
//            //отрезать мусор
//            $arrCut = ['-', ')', '(', '.', 'Кузбасс', 'Татарстан', 'Чувашия', '-', ')', '(', '.', 'Область', 'обл', 'респ', 'край', 'ублика', 'город', 'г', '-', ')', '(', '.'];
//            foreach ($arrCut as $cut) {
//                $cutLen = mb_strlen($cut);
//                $strLen = mb_strlen($company['RQ_REGION']);
//                if (mb_substr(mb_strtolower($company['RQ_REGION']), -$cutLen) == mb_strtolower($cut))
//                    $company['RQ_REGION'] = mb_substr($company['RQ_REGION'], 0, $strLen - $cutLen);
//                if (mb_substr(mb_strtolower($company['RQ_REGION']), 0, $cutLen) == $cut)
//                    $company['RQ_REGION'] = mb_substr($company['RQ_REGION'], $cutLen);
//                $company['RQ_REGION'] = trim($company['RQ_REGION']);
//            }
//            $company['RQ_REGION'] = trim($company['RQ_REGION']);
//            d($company);
//            if (mb_strlen($company['RQ_REGION']) > 5) {
//                $arFilter = ["IBLOCK_ID" => $listRegionIblockId, "%NAME" => $company['RQ_REGION']];
//                $res = \CIBlockElement::GetList(["SORT" => "ASC"], $arFilter, false, ['nPageSize' => 1], $arSelect);
//                $ob = $res->fetch();
//                $company['NEW_VALUE'] = $ob['ID'];
//            }
//            $companies[$company['ID']] = $company;
//        };
//        ksort($companies);
//
//        $tasks = [];
//        foreach ($companies as $company) {
//            if (($company[$ufCodeRegion] == null) && (empty($company['RQ_REGION_F']))) {
//                $ar = '';
//                $ar .= "[" . $company['ID'] . "] " . $company['TITLE'] . " нет региона в поле и  реквизит также без региона\r\n";
//                d($ar);
//            }
//            if (($company[$ufCodeRegion] == null) && ($company['NEW_VALUE'] != null)) {
//                $tasks[] = ['ID' => $company['ID'],
//                    'TITLE' => $company['TITLE'],
//                    // 'RQ_REGION_F' 	=> $company['RQ_REGION_F'],
//                    'RQ_REGION' => $company['RQ_REGION'],
//                    $ufCodeRegion => $company[$ufCodeRegion],
//                    'NEW_VALUE' => $company['NEW_VALUE'],
//                ];
//                d($tasks);
//            }
//        }
//
//        //$ccompany = new \CCrmCompany(false);
//        $container = Service\Container::getInstance();
//        $factory = $container->getFactory(\CCrmOwnerType::Company);
//        foreach ($tasks as $key => $task) {
//            $arFields = [$ufCodeRegion => $task['NEW_VALUE']];
//            if (!empty($task['NEW_VALUE'])) {
//                //  d($arFields);
//                //d($company['ID'] . ' ' . $task['NEW_VALUE']);
//                // $res = $ccompany->Update($task['ID'], $arFields);
//                $item = $factory->getItem($task['ID']);
//                $item->set($ufCodeRegion, $task['NEW_VALUE']);
//                $operation = $factory->getUpdateOperation($item);
//                $operation->disableAllChecks();
//                $operation->disableBizProc();
//                $operation->launch();
//            }
//        }
//
//        if ($tempUserId) {
//            $USER->Logout();
//        }
//
////        $arMessageFields = array(
////            "FROM_USER_ID" => 1,
////            "TO_USER_ID" => 1,
////            "NOTIFY_TYPE" => IM_NOTIFY_FROM,
////            "NOTIFY_MODULE" => "main",
////            "NOTIFY_TAG" => "",
////            "NOTIFY_MESSAGE" => $ar,
////        );
////        \CIMNotify::Add($arMessageFields);
//    }

    public static function getRegionForm(): void
    {
        global $USER;

        // Авторизация под временным пользователем
        // $tempUserId = null;
        // if ( (empty($USER)) or (!$USER->IsAuthorized()) ) {
        //     $tempUserId = 1;
        //     $USER->Authorize($tempUserId);
        // }

        $listRegionIblockId = 16;
        $ufCodeRegion = 'UF_CRM_REGION';

        $companies = [];

        $rsCompanies = \Bitrix\Crm\CompanyTable::getList([
            'order' => ['ID' => 'DESC'],
            'filter' => [$ufCodeRegion => null,],
            //'filter' => ['ID' => 3790],
            'select' => ['ID', $ufCodeRegion,]
        ]);

        $req = new \Bitrix\Crm\EntityRequisite();

        $arSelect = ["ID", "NAME"];

        while ($company = $rsCompanies->fetch()) {
            // Получаем реквизит компании
            $rs = $req->getList([
                'filter' => [
                    'ENTITY_ID' => $company['ID'],
                    'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
                    // 'PRESET_ID' => 1
                ],
                'select' => ['ID']
            ]);

            $rq = $rs->fetch();
            $addresses = \Bitrix\Crm\EntityRequisite::getAddresses($rq['ID']);
            $countryList = ['беларусь', 'казахстан'];

            // Извлекаем регион из адреса
            foreach ($addresses as $address) {
                if (!empty($company['RQ_REGION_F'])) {
                    continue;
                }

                $company['RQ_REGION_F'] = $address['PROVINCE'] ?? '';
                $company['RQ_REGION'] = trim($address['PROVINCE'] ?? '');

                if (in_array(mb_strtolower($address['COUNTRY']), array_map('mb_strtolower', $countryList))) {
                    $company['RQ_REGION_F'] = $address['COUNTRY'] ?? '';
                    $company['RQ_REGION'] = trim($address['COUNTRY'] ?? '');
                }

                // Если регион пустой, пробуем вырезать из адресной строки
                if (empty($company['RQ_REGION']) && !empty($address['ADDRESS_1'])) {
                    $adrStr = explode(', ', $address['ADDRESS_1']);
                    if (isset($adrStr[1])) {
                        $company['RQ_REGION'] = trim($adrStr[1]);
                    }
                }
            }


            // Список лишних слов, которые нужно убрать из названия региона
            $arrCut = [' - ', ')', '(', '.', 'Кузбасс', 'Чувашия', ')', '(', '.', 'Область', 'обл', 'республика', 'респ', 'край', 'ублика', 'город', 'АО', 'г', ')', '(', '.'];

            // Очистка региона от мусора
            $region = $company['RQ_REGION'];
            $region = preg_replace('/(?<=\p{L})-(?=\p{L})/u', '<<<DASH>>>', $region);
            foreach ($arrCut as $word) {
                $region = preg_replace('/\s*\b' . preg_quote($word, '/') . '\b\s*/iu', ' ', $region);
            }
            $region = str_replace('<<<DASH>>>', '-', $region);
            $region = trim(preg_replace('/\s+/', ' ', $region));

            $company['RQ_REGION'] = $region;
            // Если регион нормальный по длине — ищем элемент в списке "Регионы"
            // d($region);
            if (mb_strlen($region) > 3) {
                $arFilter = ["IBLOCK_ID" => $listRegionIblockId, "%NAME" => $region];
                $res = \CIBlockElement::GetList(["SORT" => "ASC"], $arFilter, false, ['nPageSize' => 1], $arSelect);
                if ($ob = $res->Fetch()) {
                    $company['NEW_VALUE'] = $ob['ID'];
                }
            }

            $companies[$company['ID']] = $company;
        }

        ksort($companies);

        $tasks = [];

        foreach ($companies as $company) {
            if (empty($company[$ufCodeRegion]) && empty($company['RQ_REGION']) && empty($company['RQ_REGION_F'])) {
                $ar = "[" . $company['ID'] . "] " . ($company['TITLE'] ?? '') . " — нет региона в поле и в реквизите\r\n";
            }

            if (empty($company[$ufCodeRegion]) && !empty($company['NEW_VALUE'])) {
                $tasks[] = [
                    'ID' => $company['ID'],
                    'RQ_REGION' => $company['RQ_REGION'],
                    $ufCodeRegion => $company[$ufCodeRegion],
                    'NEW_VALUE' => $company['NEW_VALUE'],
                ];
            }
        }

        // Обновляем компании
        $container = \Bitrix\Crm\Service\Container::getInstance();
        $factory = $container->getFactory(\CCrmOwnerType::Company);

        foreach ($tasks as $task) {
            $item = $factory->getItem($task['ID']);
            if ($item) {
                $item->set($ufCodeRegion, $task['NEW_VALUE']);
                $operation = $factory->getUpdateOperation($item);
                $operation->disableAllChecks();
                $operation->disableBizProc();
                $operation->launch();
            }
        }

        // if ($tempUserId) {
        //     $USER->Logout();
        // }
    }


    // SQL скрипт обновления поля Годового оборота компании на основании открытых и завершенных сделок
    // за последние 12 месяцев
    // Автор Абрамов В.А 30.08.2022 19.09.2023
    // Перенесено 22.01.2025 Дружкова М.А.
    // Добавлен обороты и сделки за 180 дней 31.05.2024 Дружкова М.А.

    public static function companyUpdateRevenue(): void
    {

        $connection = \Bitrix\Main\Application::getConnection();
        $sqlHelper = $connection->getSqlHelper();

        //Обнуляем Счётчики статистики
        $strSql = "UPDATE b_crm_company SET revenue=0";
        $connection->queryExecute($strSql);
        $strSql = "UPDATE b_uts_crm_company SET UF_CRM_STAT_DEALCOUNT_YEAR=0";
        $connection->queryExecute($strSql);
        $strSql = "UPDATE b_uts_crm_company SET UF_CRM_STAT_DEALCOUNT=0";
        $connection->queryExecute($strSql);
        $strSql = "UPDATE b_uts_crm_company SET UF_CRM_DEALCOUNT_180_DAYS=0";
        $connection->queryExecute($strSql);
        $strSql = "UPDATE b_uts_crm_company SET UF_CRM_STAT_180_DAYS=concat(0,'|RUB')";
        $connection->queryExecute($strSql);
        $strSql = "UPDATE b_uts_crm_company SET UF_CRM_STAT_REVENUE_TOTAL=0";
        $connection->queryExecute($strSql);
        $strSql = "UPDATE b_uts_crm_contact SET UF_CRM_STAT_REVENUE_TOTAL=0";
        $connection->queryExecute($strSql);
        $strSql = "UPDATE b_uts_crm_contact SET UF_CRM_REVENUE_WITH_CURRENCY=0";
        $connection->queryExecute($strSql);
        $strSql = "UPDATE b_uts_crm_contact SET UF_CRM_STAT_DEALCOUNT_YEAR=0";
        $connection->queryExecute($strSql);
        $strSql = "UPDATE b_uts_crm_contact SET UF_CRM_STAT_DEALCOUNT=0";
        $connection->queryExecute($strSql);

        //Устанавливаем обороты
        $strSql = "
                UPDATE 	b_crm_company AS c,
                        (SELECT company_id AS company_id,sum(OPPORTUNITY_ACCOUNT) AS revenue 
                        FROM b_crm_deal 
                        WHERE STAGE_SEMANTIC_ID<>'F' and BEGINDATE > DATE_SUB(now(), INTERVAL 12 MONTH) 
                        GROUP BY company_id) AS r
                SET   c.revenue=r.revenue 
                WHERE r.company_id=c.id
                ";
        $connection->queryExecute($strSql);

        $strSql = "
                UPDATE 	b_crm_company AS c,
                        (SELECT company_id AS company_id,sum(OPPORTUNITY_ACCOUNT) AS revenue 
                            FROM b_crm_deal 
                            WHERE STAGE_SEMANTIC_ID<>'F'  
                            GROUP BY company_id) AS r,
                        b_uts_crm_company AS u
                SET   u.UF_CRM_STAT_REVENUE_TOTAL=concat(r.revenue,'|RUB')
                WHERE r.company_id=c.id and u.value_id=c.id
                ";
        $connection->queryExecute($strSql);

        $strSql = "
                UPDATE 	b_crm_company AS c,
                        (SELECT company_id AS company_id,sum(OPPORTUNITY_ACCOUNT) AS revenue 
                            FROM b_crm_deal 
                            WHERE STAGE_SEMANTIC_ID<>'F' and BEGINDATE > DATE_SUB(now(), INTERVAL 180 DAY)  
                            GROUP BY company_id) AS r,
                        b_uts_crm_company AS u
                SET   u.UF_CRM_STAT_180_DAYS=concat(r.revenue,'|RUB')
                WHERE r.company_id=c.id and u.value_id=c.id
                ";
        $connection->queryExecute($strSql);

        //Устанавливаем количества сделок
        $strSql = "
                UPDATE 	b_crm_company AS c,
                        (SELECT company_id AS company_id,count(*) as deal_count
                            FROM b_crm_deal 
                            WHERE STAGE_SEMANTIC_ID<>'F' 
                            GROUP BY company_id) AS r,
                        b_uts_crm_company AS u
                SET   u.UF_CRM_STAT_DEALCOUNT=r.deal_count 
                WHERE r.company_id=c.id and u.value_id=c.id
                ";
        $connection->queryExecute($strSql);

        $strSql = "
                UPDATE 	b_crm_company AS c,
                        (SELECT company_id AS company_id,count(*) as deal_count
                            FROM b_crm_deal 
                            WHERE STAGE_SEMANTIC_ID<>'F' and BEGINDATE > DATE_SUB(now(), INTERVAL 12 MONTH) 
                            GROUP BY company_id) AS r,
                        b_uts_crm_company AS u
                SET   u.UF_CRM_STAT_DEALCOUNT_YEAR=r.deal_count 
                WHERE r.company_id=c.id and u.value_id=c.id
                ";
        $connection->queryExecute($strSql);

        $strSql = "
                UPDATE 	b_crm_company AS c,
                        (SELECT company_id AS company_id,count(*) as deal_count
                            FROM b_crm_deal 
                            WHERE STAGE_SEMANTIC_ID<>'F' and BEGINDATE > DATE_SUB(now(), INTERVAL 180 DAY) 
                            GROUP BY company_id) AS r,
                        b_uts_crm_company AS u
                SET   u.UF_CRM_DEALCOUNT_180_DAYS=r.deal_count 
                WHERE r.company_id=c.id and u.value_id=c.id
                ";
        $connection->queryExecute($strSql);

        //Обновляем поле Статистика: Оборот всего у контакта, если нет компании
        $strSql = "
                UPDATE 	b_crm_contact AS c,
                        (SELECT contact_id AS contact_id,sum(OPPORTUNITY_ACCOUNT) AS revenue 
                            FROM b_crm_deal 
                            WHERE STAGE_SEMANTIC_ID<>'F' and COMPANY_ID = 0
                            GROUP BY contact_id) AS r,
                        b_uts_crm_contact AS u
                SET   u.UF_CRM_STAT_REVENUE_TOTAL=CONCAT(r.revenue,'|RUB')
                WHERE r.contact_id=c.id and u.value_id=c.id
                ";
        $connection->queryExecute($strSql);

        //Обновляем поле Статистика: Годовой оборот у контакта, если нет компании
        $strSql = "
                UPDATE 	b_crm_contact AS c,
                        (SELECT contact_id AS contact_id,sum(OPPORTUNITY_ACCOUNT) AS revenue 
                            FROM b_crm_deal 
                            WHERE STAGE_SEMANTIC_ID<>'F' and COMPANY_ID = 0 and BEGINDATE > DATE_SUB(now(), INTERVAL 12 MONTH) 
                            GROUP BY contact_id) AS r,
                        b_uts_crm_contact AS u
                SET   u.UF_REVENUE_WITH_CURRENCY=CONCAT(r.revenue,'|RUB')
                WHERE r.contact_id=c.id and u.value_id=c.id
                ";
        $connection->queryExecute($strSql);

        //Обновляем поле Статистика: Сделок за год у контакта, если нет компании
        $strSql = "
                UPDATE 	b_crm_contact AS c,
                        (SELECT contact_id AS contact_id,count(*) as deal_count 
                            FROM b_crm_deal 
                            WHERE STAGE_SEMANTIC_ID<>'F' and COMPANY_ID = 0 and BEGINDATE > DATE_SUB(now(), INTERVAL 12 MONTH) 
                            GROUP BY contact_id) AS r,
                        b_uts_crm_contact AS u
                SET   u.UF_CRM_STAT_DEALCOUNT_YEAR=r.deal_count 
                WHERE r.contact_id=c.id and u.value_id=c.id
            
                ";
        $connection->queryExecute($strSql);

        //Обновляем поле Статистика: Всего сделок у контакта, если нет компании
        $strSql = "
                UPDATE 	b_crm_contact AS c,
                        (SELECT contact_id AS contact_id,count(*) as deal_count 
                            FROM b_crm_deal 
                            WHERE STAGE_SEMANTIC_ID<>'F' and COMPANY_ID = 0
                            GROUP BY contact_id) AS r,
                        b_uts_crm_contact AS u
                SET   u.UF_CRM_STAT_DEALCOUNT=r.deal_count 
                WHERE r.contact_id=c.id and u.value_id=c.id
                ";
        $connection->queryExecute($strSql);

//        $arMessageFields = array(
//            "FROM_USER_ID" => 1,
//            "TO_USER_ID" => 1,
//            "NOTIFY_TYPE" => IM_NOTIFY_FROM,
//            "NOTIFY_MODULE" => "main",
//            "NOTIFY_TAG" => "",
//            "NOTIFY_MESSAGE" => 'ОК',
//        );
//        \CIMNotify::Add($arMessageFields);
    }

    // SQL скрипт обновления поля Годового оборота компании на основании открытых и завершенных сделок
    // Автор Абрамов В.А 30.08.20222
    // Перенесен 22.01.2025 Дружкова М.А.
    public static function updateSpentDays(): void
    {
        $strSql = "update b_crm_company as c,
                    (select company_id as company_id,sum(OPPORTUNITY_ACCOUNT) as revenue from b_crm_deal where STAGE_SEMANTIC_ID<>'F' group by company_id) as r
                    set c.revenue=r.revenue 
                    where r.company_id=c.id";
        $connection = \Bitrix\Main\Application::getConnection();
        $sqlHelper = $connection->getSqlHelper();
        $connection->queryExecute($strSql);
    }

    public static function getList($filter = []): array
    {
        $arCompany = [];
        $entityResult = \Bitrix\Crm\CompanyTable::getList([
            'select' => [
                'ID',
                'TITLE',
            ],
            'filter' => $filter,
            'order' => ['TITLE']
        ]);

        foreach ($entityResult as $entity) {
            $arCompanyId[$entity['ID']] = $entity['TITLE'] . " (" . $entity['ID'] . ")";
        }

        return $arCompanyId;
    }

    //Функция получени ID комапнии по guid
    //игнорирует права
    static public function getCompanyIDbyGUID($guid)
    {
        if ((empty($guid)) || (strlen($guid) < 36))
            return false;
        $arOptions = [
            'CURRENT_USER' => 1,
            "CHECK_PERMISSIONS" => "N",
            'DISABLE_USER_FIELD_CHECK' => true,
            "DISABLE_REQUIRED_USER_FIELD_CHECK" => true
        ];
        $CrmCompany = new \CCrmCompany(false);
        $arCompanyUnit = [
            'ORIGIN_ID' => $guid,
            'CHECK_PERMISSIONS' => 'N'
        ];
        // $arCompanyOption = array();
        if ($arCompany = $CrmCompany->GetList(['ID' => 'ASC'], $arCompanyUnit, $arOptions)->fetch()) {
            return $arCompany['ID'];
        } else {
            return false;
        }
    }

    //Запускает БП с id=workflowTemplateId для Компании companyId
    public static function startBPWorkflow($workflowTemplateId, $companyId)
    {
        if ((empty($companyId)) or (intval($companyId) == 0))
            return false;
        if (empty($workflowTemplateId))
            return false;

        \Bitrix\Main\Loader::includeModule('bizproc');
        $runtime = \CBPRuntime::GetRuntime();
        try {
            $workflowTemplateId = intval($workflowTemplateId);
            $companyId = intval($companyId);
            $documentId = ['crm', 'CCrmDocumentCompany', 'COMPANY_' . $companyId];
            if (\CCrmCompany::Exists($companyId)) {
                $workflow = $runtime->CreateWorkflow($workflowTemplateId, $documentId, []);
                $workflow->Start();
                return true;
            } else
                echo "Нет компании $companyId<br>";
        } catch (Exception $error) {
            echo $error->getMessage() . " companyId=$companyId workflowTemplateId=$workflowTemplateId<br>";
            return false;
        }
        return false;
    }


    static public function getCompanyIDbyTitle($title)
    {
        if (empty($title))
            return null;

        $arOptions = [
            'CURRENT_USER' => 1,
            "CHECK_PERMISSIONS" => "N",
            'DISABLE_USER_FIELD_CHECK' => true,
            "DISABLE_REQUIRED_USER_FIELD_CHECK" => true
        ];


        $CrmCompany = new \CCrmCompany(false);
        $arCompanyUnit = [
            '%TITLE' => trim($title),
            'CHECK_PERMISSIONS' => 'N'
        ];
        // $arCompanyOption = array();
        if ($arCompany = $CrmCompany->GetList(['ID' => 'ASC'], $arCompanyUnit, $arOptions)->fetch()) {
            return $arCompany['ID'];
        } else {
            return null;
        }
    }

    public static function updateCompanyOverdueDebts(int $companyId = 0)
    {
        if (empty($companyId)) {
            return;
        }
        $newOverdueDebt = 0;
        $arDeals = \Bitrix\Crm\DealTable::getList([
            'filter' => [
                '!=UF_CRM_OVERDUE_DEBT' => null,
                '!CATEGORY_ID' => 8,
                'COMPANY_ID' => $companyId,
            ],
            'select' => ["ID", "UF_CRM_OVERDUE_DEBT"],
        ]);
        while ($deal = $arDeals->fetch()) {
            $newOverdueDebt += $deal['UF_CRM_OVERDUE_DEBT'];
        }
        $entityFields = ['UF_CRM_OVERDUE_DEBT' => $newOverdueDebt];
        $result = \Bitrix\Crm\CompanyTable::update($companyId, $entityFields);
    }

    public static function updateAllCompanyOverdueDebts()
    {
        $arCompany = \Bitrix\Crm\CompanyTable::getList([
            'select' => [
                'ID',
            ],
        ]);
        while ($company = $arCompany->fetch()) {
            self::updateCompanyOverdueDebts($company['ID']);
        }
    }

    public static function setUsedLaboratory()
    {
        $companyLaboratoriesMap = [];

        $dealResult = \CCrmDeal::GetListEx(
            ['ID' => 'ASC'],
            [
                '!COMPANY_ID' => false,
                'STAGE_SEMANTIC_ID' => ['P', 'S'], // P = в работе, S = успешная
                '!UF_CRM_LABORATORY' => false,
            ],
            false,
            false,
            [
                'ID',
                'COMPANY_ID',
                'UF_CRM_LABORATORY',
            ]
        );
        while ($deal = $dealResult->Fetch()) {
            $companyId = (int)($deal['COMPANY_ID'] ?? 0);
            $laboratoryId = $deal['UF_CRM_LABORATORY'] ?? null;
            $excludedLabs = [7706, 810];

            if ($companyId <= 0 || empty($laboratoryId) || in_array($laboratoryId, $excludedLabs)) {
                continue;
            }

            if (!isset($companyLaboratoriesMap[$companyId])) {
                $companyLaboratoriesMap[$companyId] = [];
            }

            $companyLaboratoriesMap[$companyId][$laboratoryId] = $laboratoryId;

        }

        if (empty($companyLaboratoriesMap)) {
            return;
        }

        $container = \Bitrix\Crm\Service\Container::getInstance();
        $factory = $container->getFactory(\CCrmOwnerType::Company);


        foreach ($companyLaboratoriesMap as $companyId => $laboratories) {
            $item = $factory->getItem($companyId);
            if ($item) {

                $newValues = array_values($laboratories);
                sort($newValues);

                $currentValues = $item->get('UF_CRM_LABORATORIES');
                if (empty($currentValues)) {
                    $currentValues = [];
                } elseif (!is_array($currentValues)) {
                    $currentValues = [$currentValues];
                }

                $currentValues = array_map('intval', $currentValues);
                $currentValues = array_filter($currentValues, static fn($value) => $value > 0);
                $currentValues = array_values(array_unique($currentValues));
                sort($currentValues);

                if ($currentValues == $newValues) {
                    continue;
                }

                $item->set('UF_CRM_LABORATORIES', $newValues);
                $saveResult = $item->save();
//                if ($saveResult->isSuccess()) {
//                    d($item->getId());
//                } else {
//                    d($saveResult->getErrors());
//                }
            }
        }
    }

}
