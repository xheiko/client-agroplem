<?php

namespace Tanais\ClientAGR;

\Bitrix\Main\Loader::includeModule('crm');

class Company
{
    const REFERENCE_FIELD = [
        'UF_CRM_COMPANY_AGR_REGION' => 'listIdRegion',
        'UF_CRM_COMPANY_AGR_AGROHOLDING' => 'listIdHolding',
        'UF_CRM_COMPANY_AGR_INFORMATION_SYSTEMS' => 'listIdSoftware',
        'UF_CRM_COMPANY_AGR_ACTIVITY_TYPE' => 'listIdBusiness',
        'UF_CRM_COMPANY_AGR_A_CLIENT' => 'listIdMycompany',
        'UF_CRM_COMPANY_AGR_B_CLIENT' => 'listIdMycompany',
        'UF_CRM_COMPANY_AGR_C_CLIENT' => 'listIdMycompany',
    ];

    public static function updateABC(): void
    {
        \Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Company::updateABC Запущен метод расёта ABC-клиентов");
        //Поулчаем данные для анализа и формируем массив $revenueData, по которому будем считать
        $companiesData = \Bitrix\Crm\CompanyTable::getList([
            'select' => ['ID', 'REVENUE', 'UF_CRM_COMPANY_AGR_A_CLIENT', 'UF_CRM_COMPANY_AGR_B_CLIENT', 'UF_CRM_COMPANY_AGR_C_CLIENT'],
            // 'filter' => ['!REVENUE' => 0],
            // 'filter' => ['ID' => 3529],
            'order' => ['REVENUE' => 'DESC'],
        ])->fetchAll();
        $revenueData = [];
        $totalRevenue = 0; //Сумма всех оборотов
        foreach ($companiesData as $companyData) {
            $revenueData[$companyData['ID']] = $companyData;
            $totalRevenue += $companyData['REVENUE'];
        }
        if (empty($revenueData))
            return;

        //Расчёт ABC
        $accumulatedRevenue = 0;
        foreach ($revenueData as $companyId => &$companyData) {
            $accumulatedRevenue += $companyData['REVENUE'];
            if ($accumulatedRevenue / $totalRevenue <= 0.8)
                $companyData['NEW_VALUE'] = 'A';
            else
                if ($accumulatedRevenue / $totalRevenue <= 0.95)
                    $companyData['NEW_VALUE'] = 'B';
                else
                    $companyData['NEW_VALUE'] = 'C';
        }

        //Обновление данных
        $thisServerId = \Tanais\ClientAGR\Reference::getThisServerRef()['ID'];
        foreach ($revenueData as $companyId => &$companyData) {
            $newValueA = (!empty($companyData['UF_CRM_COMPANY_AGR_A_CLIENT']) && is_array($companyData['UF_CRM_COMPANY_AGR_A_CLIENT'])) ? $companyData['UF_CRM_COMPANY_AGR_A_CLIENT'] : [];
            $newValueB = (!empty($companyData['UF_CRM_COMPANY_AGR_B_CLIENT']) && is_array($companyData['UF_CRM_COMPANY_AGR_B_CLIENT'])) ? $companyData['UF_CRM_COMPANY_AGR_B_CLIENT'] : [];
            $newValueC = (!empty($companyData['UF_CRM_COMPANY_AGR_C_CLIENT']) && is_array($companyData['UF_CRM_COMPANY_AGR_C_CLIENT'])) ? $companyData['UF_CRM_COMPANY_AGR_C_CLIENT'] : [];

            if ($companyData['NEW_VALUE'] == 'A') {
                $newValueA[] = $thisServerId;
                $newValueB = array_diff($newValueB, [$thisServerId]);
                $newValueC = array_diff($newValueC, [$thisServerId]);
            }
            if ($companyData['NEW_VALUE'] == 'B') {
                $newValueA = array_diff($newValueA, [$thisServerId]);
                $newValueB[] = $thisServerId;
                $newValueC = array_diff($newValueC, [$thisServerId]);
            }
            if ($companyData['NEW_VALUE'] == 'C') {
                $newValueA[] = $thisServerId;
                $newValueA = array_diff($newValueA, [$thisServerId]);
                $newValueB = array_diff($newValueB, [$thisServerId]);
                $newValueC[] = $thisServerId;
            }
            $newValueA = array_unique($newValueA);
            $newValueB = array_unique($newValueB);
            $newValueC = array_unique($newValueC);

            sort($newValueA);
            sort($newValueB);
            sort($newValueC);

            $needToUpdate = false;
            if ($companyData['UF_CRM_COMPANY_AGR_A_CLIENT'] != $newValueA)
                $needToUpdate = true;
            if ($companyData['UF_CRM_COMPANY_AGR_B_CLIENT'] != $newValueB)
                $needToUpdate = true;
            if ($companyData['UF_CRM_COMPANY_AGR_C_CLIENT'] != $newValueC)
                $needToUpdate = true;
            if ($needToUpdate) {
                \Bitrix\Crm\CompanyTable::update($companyId, [
                    'UF_CRM_COMPANY_AGR_A_CLIENT' => $newValueA,
                    'UF_CRM_COMPANY_AGR_B_CLIENT' => $newValueB,
                    'UF_CRM_COMPANY_AGR_C_CLIENT' => $newValueC,
                ]);
            }
        }
    }

    //Получить компанию с $server в виде массива. При отдаче значения поле ссылок на спрвочники меняется на CODE того же справочника
    public static function getCompatibleData($companyId = 0, $server = ""): array
    {
        if (!(intval($companyId) > 0)) {
            return [];
        }
        //Если запрашиваем локальную компанию
        if ($server == "") {
            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Company);
            $company = $factory->getItem($companyId)->getCompatibleData();
            //Пересохраняем поля, так чтобы они не были обработаны штатным образом, но были доступны при синхронизации
            $company['AGR_A_CLIENT_VALUE'] = $company['UF_CRM_COMPANY_AGR_A_CLIENT'];
            $company['AGR_B_CLIENT_VALUE'] = $company['UF_CRM_COMPANY_AGR_B_CLIENT'];
            $company['AGR_C_CLIENT_VALUE'] = $company['UF_CRM_COMPANY_AGR_C_CLIENT'];

            //Заменяем значение полей справочников с ID на CODE из того же справочника
            foreach (self::REFERENCE_FIELD as $fieldCode => $optionName) {
                $fieldValue = $company[$fieldCode];
                //Если поле пустое, то ничего менять не нужно
                if (empty($fieldValue))
                    continue;
                $entityTypeId = \Bitrix\Main\Config\Option::get('tanais.clientagr', $optionName);
                $factoryRef = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
                $ufPrefix = "UF_CRM_" . (\Bitrix\Crm\Model\Dynamic\TypeTable::getByEntityTypeId($entityTypeId)->fetch())["ID"] . "_";
                //Если у поля меняется ID на код, то очищаем ID 
                unset($company[$fieldCode]);

                //Если в поле одно значение
                if (!is_array($fieldValue)) {
                    $refElement = $factoryRef->getItem($fieldValue);
                    $refElementCode = $refElement->get("{$ufPrefix}CODE");
                    if ($refElementCode)
                        $company[$fieldCode] = $refElementCode;
                }
                //Если в поле множественное
                if (is_array($fieldValue)) {
                    foreach ($fieldValue as $referenceElementId) {
                        $refElement = $factoryRef->getItem($referenceElementId);
                        if ($refElement) {
                            $refElementCode = $refElement->get("{$ufPrefix}CODE");
                            if ($refElementCode)
                                $company[$fieldCode][] = $refElementCode;
                        }
                    }
                }
            }
            //Формируем FAKE поля  ABC_CODE, ABC_CLIENT, AGR_A_CLIENT_VALUE,AGR_B_CLIENT_VALUE,AGR_C_CLIENT_VALUE         
            //Такие поля не будут обработаны штатным способом

            $serverCode = \Tanais\ClientAGR\Reference::getThisServerRef()['CODE'];
            $company['ABC_CODE'] = $serverCode;
            if (is_array($company['UF_CRM_COMPANY_AGR_C_CLIENT']) && in_array($serverCode, $company['UF_CRM_COMPANY_AGR_C_CLIENT']))
                $company['ABC_CLIENT'] = 'C';
            if (is_array($company['UF_CRM_COMPANY_AGR_B_CLIENT']) && in_array($serverCode, $company['UF_CRM_COMPANY_AGR_B_CLIENT']))
                $company['ABC_CLIENT'] = 'B';
            if (is_array($company['UF_CRM_COMPANY_AGR_A_CLIENT']) && in_array($serverCode, $company['UF_CRM_COMPANY_AGR_A_CLIENT']))
                $company['ABC_CLIENT'] = 'A';
            unset($company['UF_CRM_COMPANY_AGR_A_CLIENT']);
            unset($company['UF_CRM_COMPANY_AGR_B_CLIENT']);
            unset($company['UF_CRM_COMPANY_AGR_C_CLIENT']);
            return $company;
        }
        $webhook = \Tanais\ClientAGR\Helper::getWebhook($server);
        if (empty($webhook))
            return [];
        $data = \Tanais\ClientAGR\Helper::callRestApi($webhook, 'tanais.clientagr.company.get.json', ['companyId' => $companyId]);
        return $data;
    }

    //Устанавливает сортировку всех пользовтельских полей типа решения Клиенты AGR
    public static function setSortUserField()
    {
        \Bitrix\Main\Loader::includeModule("crm");

        // Найдём пользовательские поля
        $res = \CUserTypeEntity::GetList([], [
            "ENTITY_ID" => "CRM_COMPANY",
        ]);

        $userTypeEntity = new \CUserTypeEntity();
        while ($field = $res->Fetch()) {
            if (str_starts_with($field["FIELD_NAME"], "UF_CRM_COMPANY_AGR_") === false) {
                continue;
            }
            $result = $userTypeEntity->Update($field["ID"], ["SORT" => 7000]);
        }
        return true;
    }

    static public function linkAutoAllServers($server)
    {
        $servers = \Tanais\ClientAGR\Helper::getAllPartnerServer();
        foreach ($servers as $server) {
            self::linkAutoByOGRN($server);
        }
    }

    //\Tanais\ClientAGR\CRM\Company::linkAutoByOGRN();
    //Синхронизурет локальный справочник $referenceId со справочником на сервером $server
    static public function linkAutoByOGRN($server)
    {
        if (empty($server))
            return false;

        $result = [
            'SUCCESS' => false,
            'LOCAL_COMPANY_COUNT' => 0,
            'PARTNER_COMPANY_COUNT' => 0,
            'PARTNER_SERVER' => $server,
            'COMMON_COMPANY_FOUND' => 0,
            'COMPANY_LINK_UPDATED' => 0,
            'COMPANY_LINK_NOUPDATE_REQUIRED' => 0,
        ];

        \Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Company::linkAutoByOGRN Запущен метод сопаставления компаний по ОГРН с сервером {$server}");
        //Получаем компании с партнёрского сервера
        $webhook = \Tanais\ClientAGR\Helper::getWebhook($server);
        $partnerCompanyData = \Tanais\ClientAGR\Helper::callRestApi($webhook, 'tanais.clientagr.company.list.json', ['refId' => $referenceId]);
        if (empty($partnerCompanyData))
            return $result;

        $result['PARTNER_COMPANY_COUNT'] = is_countable($partnerCompanyData) ? count($partnerCompanyData) : 0;

        //Формируем массив внешних компаний по ключам ОГРН и ИНН/КПП, чтобы потом сопаставить с нашими компаниями
        $filteredPartnerCompanyData = [];
        $doubles = [];
        foreach ($partnerCompanyData as $companyKey => $companyData) {
            if (empty($companyData['RQ_OGRN']) && empty($companyData['RQ_INN']))
                continue;
            if (empty($companyData['RQ_OGRN']) && empty($companyData['RQ_KPP']))
                continue;
            $ogrnKey = $companyData['RQ_OGRN']; //Ключ записи по ОГРН
            $innKppKey = $companyData['RQ_INN'] . "/" . $companyData['RQ_KPP']; //Ключ записи по ИНН/КПП

            //Полученная компания учтена дважды, очищаем                                   
            if (isset($filteredPartnerCompanyData[$ogrnKey])) {
                $filteredPartnerCompanyData[$ogrnKey] = [];
                $doubles[] = $ogrnKey;
            }
            //Если не дубль, то добавляем в массив по ключу ОГРН
            if (!isset($filteredPartnerCompanyData[$ogrnKey]) && !empty($companyData['RQ_OGRN']))
                $filteredPartnerCompanyData[$ogrnKey] = $companyData;

            //Полученная компания учтена дважды, очищаем                                   
            if (isset($filteredPartnerCompanyData[$innKppKey])) {
                $filteredPartnerCompanyData[$innKppKey] = [];
                $doubles[] = $innKppKey;
            }
            //Если не дубль, то добавляем в массив по ключу ИНН/КПП
            if (!isset($filteredPartnerCompanyData[$innKppKey]) && !empty($companyData['RQ_INN']) && !empty($companyData['RQ_KPP']))
                $filteredPartnerCompanyData[$innKppKey] = $companyData;
        }
        ksort($filteredPartnerCompanyData);
        unset($partnerCompanyData);

        //Делаем тоже самое с локальными компаниями
        $companyController = new  \Tanais\ClientAGR\Controller\Company();
        $myCompanyData = $companyController->listAction();
        $result['LOCAL_COMPANY_COUNT'] = is_countable($myCompanyData) ? count($myCompanyData) : 0;
        if (empty($myCompanyData))
            return $result;

        $filteredMyCompanyData = [];
        foreach ($myCompanyData as $companyKey => $companyData) {
            if (empty($companyData['RQ_OGRN']) && empty($companyData['RQ_INN']))
                continue;
            if (empty($companyData['RQ_OGRN']) && empty($companyData['RQ_KPP']))
                continue;

            $ogrnKey = $companyData['RQ_OGRN']; //Ключ записи по ОГРН                        
            $innKppKey = $companyData['RQ_INN'] . "/" . $companyData['RQ_KPP']; //Ключ записи по ИНН/КПП
            //Полученная компания учтена дважды, очищаем                                   
            if (isset($filteredPartnfilteredMyCompanyDataerCompanyData[$ogrnKey])) {
                $filteredMyCompanyData[$ogrnKey] = [];
                $doubles[] = $ogrnKey;
            }
            //Если не дубль, то добавляем в массив по ключу ОГРН
            if (!isset($filteredMyCompanyData[$ogrnKey]) && !empty($companyData['RQ_OGRN']))
                $filteredMyCompanyData[$ogrnKey] = $companyData;

            //Полученная компания учтена дважды, очищаем                                   
            if (isset($filteredMyCompanyData[$innKppKey])) {
                $filteredMyCompanyData[$innKppKey] = [];
                $doubles[] = $innKppKey;
            }
            //Если не дубль, то добавляем в массив по ключу ИНН/КПП
            if (!isset($filteredMyCompanyData[$innKppKey]) && !empty($companyData['RQ_INN']) && !empty($companyData['RQ_KPP']))
                $filteredMyCompanyData[$innKppKey] = $companyData;
        }
        unset($myCompanyData);
        ksort($filteredMyCompanyData);
        // d($filteredPartnerCompanyData);
        // d($filteredMyCompanyData);


        //Компания, которые есть и у меня и у партнёра по ОГРН
        $filteredMyCompanyData = array_filter($filteredMyCompanyData); //Удаляем элементы с пустыми занчениями
        $filteredPartnerCompanyData = array_filter($filteredPartnerCompanyData); //Удаляем элементы с пустыми занчениями

        $commonCompanies = array_intersect(array_keys($filteredPartnerCompanyData), array_keys($filteredMyCompanyData)); //'1071838000805'
        $result['COMMON_COMPANY_FOUND'] = is_countable($commonCompanies) ? count($commonCompanies) : 0;
        if (empty($commonCompanies))
            return $result;
        // d($filteredPartnerCompanyData);
        // d($filteredMyCompanyData);
        // d($commonCompanies);

        // echo "<pre>" . var_export(count($commonCompanies), true) . "</pre><br>" . PHP_EOL;

        //Удаляем текущую привязку к компании на сервере партнера и добавляем новую
        $updatedId = [];

        foreach ($commonCompanies as $companyKey) {
            //Если уже обновляли компанию, то пропускаем
            // if ($filteredMyCompanyData[$companyKey]['ID']==0)

            if (in_array($filteredMyCompanyData[$companyKey]['ID'], $updatedId)) {
                $result['COMMON_COMPANY_FOUND']--; //Это дубли так как компания есть и по ОГРН, и по ИНН/КПП
                // echo "пропускаем {$companyKey} <br>" . PHP_EOL;
                continue;
            }

            $newLink = "https://{$server}/crm/company/details/{$filteredPartnerCompanyData[$companyKey]['ID']}/";
            $links = $filteredMyCompanyData[$companyKey]['UF_CRM_COMPANY_AGR_LINK'];
            if (in_array($newLink, $links)) {
                $result['COMPANY_LINK_NOUPDATE_REQUIRED']++;
                $updatedId[] = $filteredMyCompanyData[$companyKey]['ID'];
                continue; //Такая ссылка уже есть, пропускаем
            }

            //Удаляем из ссылок все ссылки на текущий сервер
            foreach ($links as $linkKey => $link) {
                if (str_contains($linkKey, $server)) {
                    unset($links[$linkKey]);
                }
            }
            //Если там один элемент, то он не массив, а строка
            if (!is_array($links))
                $links = [$links];

            //Формируем новый массив ссылок и сохраняем его
            $links[] = $newLink;
            sort($links);
            $links = array_unique(array_filter($links));
            $message = "Сопоставлено локальная компания {$filteredMyCompanyData[$companyKey]['ID']} с $newLink";
            $fieldsData = [
                'UF_CRM_COMPANY_AGR_LINK' => $links,
                // 'UF_CRM_COMPANY_AGR_UPDATED'        => date("d.m.Y H:i:s"), //Не обновляем
                'UF_CRM_COMPANY_AGR_DATE_MATCHED' => date("d.m.Y H:i:s"),
                'UF_CRM_COMPANY_AGR_MATCHED_BY' => \Bitrix\Main\Engine\CurrentUser::get() ? \Bitrix\Main\Engine\CurrentUser::get()->getFormattedName() : 'console',
                'UF_CRM_COMPANY_AGR_UPDATED_SITE' => \Bitrix\Main\Config\Option::get("main", "server_name", ""),
                'UF_CRM_COMPANY_AGR_UPDATED_BY_ID' => \Bitrix\Main\Engine\CurrentUser::get() ? \Bitrix\Main\Engine\CurrentUser::get()->getId() : 'console',
                'UF_CRM_COMPANY_AGR_UPDATED_BY' => \Bitrix\Main\Engine\CurrentUser::get() ? \Bitrix\Main\Engine\CurrentUser::get()->getFormattedName() : 'console',
            ];
            \Bitrix\Crm\CompanyTable::update($filteredMyCompanyData[$companyKey]['ID'], $fieldsData);
            $updatedId[] = $filteredMyCompanyData[$companyKey]['ID'];
            $result['COMPANY_LINK_UPDATED']++;
        }
        \Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Company::linkAutoByOGRN Сопоставлено " . count($updatedId) . " c {$server}");
        return $result;
    }

    //Разбирает значение поля UF_CRM_COMPANY_AGR_LINK и возвращает ID компании на удаленном сервере
    static public function getPartnerCompanyId($localCompanyId = 0, $server = '')
    {
        $myCompanyData = self::getCompatibleData($localCompanyId);
        $links = $myCompanyData["UF_CRM_COMPANY_AGR_LINK"];
        foreach ($links as $link) {
            if (str_starts_with($link, "https://" . $server))
                if (preg_match('/\/(\d+)\/?$/', $link, $matches))
                    return $matches[1];
        }
        return false;
    }

    //Возвращает список простых поле для синхронизации
    public static function getSimpleFieldsForSync()
    {
        $agrFields = [
            'UF_CRM_COMPANY_AGR_COMMENT' => 'Общий комментарий',
            // 'UF_CRM_COMPANY_AGR_AGROHOLDING' => false, // Справочник холдингов'Агрохолдинг', //!!
            'UF_CRM_COMPANY_AGR_GROUP_COMPANY' => 'Группа компаний',
            'UF_CRM_COMPANY_AGR_LPR' => 'ЛПР компании', //!!
            // 'UF_CRM_COMPANY_AGR_ACTIVITY_TYPE' => false,//'Вид деятельности', //!!
            // 'UF_CRM_COMPANY_AGR_REGION' => false,//'Регион', //!!
            // 'UF_CRM_COMPANY_AGR_A_CLIENT' => false,//'A-Клиент', //!!
            // 'UF_CRM_COMPANY_AGR_B_CLIENT' => false,//'B-Клиент', //!!
            // 'UF_CRM_COMPANY_AGR_C_CLIENT' => false,//'C-Клиент', //!!
            'UF_CRM_COMPANY_AGR_TOTAL_HEADS_ALL_KINDS' => 'Всего голов животных всех видов',
            'UF_CRM_COMPANY_AGR_TOTAL_HEADS_ALL_KINDS_DETAILS' => 'Всего голов животных всех видов детально',
            'UF_CRM_COMPANY_AGR_KRS_BULL' => 'КРС быков',
            'UF_CRM_COMPANY_AGR_POULTRY_POPULATION' => 'Поголовье птицы',
            'UF_CRM_COMPANY_AGR_PIG_POPULATION' => 'Поголовье свиней',
            'UF_CRM_COMPANY_AGR_KRS_TOTAL' => 'КРС всего',
            'UF_CRM_COMPANY_AGR_HEIFER' => 'КРС молочных коров',
            'UF_CRM_COMPANY_AGR_DAIRY_COWS' => 'КРС тёлок',
            'UF_CRM_COMPANY_AGR_FARMLAND_AREA' => 'Площадь сельхозугодий, га',
            'UF_CRM_COMPANY_AGR_DATE_CONFIRMATION' => 'Племенной статус: дата подтверждения',
            'UF_CRM_COMPANY_AGR_ANIMAL_TYPE' => 'Племенной статус: Вид животного',
            'UF_CRM_COMPANY_AGR_ID_VETIS_MERCURY' => 'ID в Ветис Меркурий',
            'UF_CRM_COMPANY_AGR_ID_DAIRY_COMP' => 'ID в DairyComp 305',
            // 'UF_CRM_COMPANY_AGR_INFORMATION_SYSTEMS' => false,//'Информационные системы',

        ];
        return $agrFields;
    }

    //Синхронизурет данные всех локальных компаний с $server
    static public function forceSynchronizeAllCompany($server = '')
    {
        if (empty($server))
            return false;
        $companiesData = \Bitrix\Crm\CompanyTable::getList([
            'select' => ['ID', 'UF_CRM_COMPANY_AGR_LINK'],
            'filter' => ['UF_CRM_COMPANY_AGR_LINK' => "%{$server}%"],
            'order' => ['REVENUE' => 'DESC'],
        ])->fetchAll();
        $message = "Запущен принудительный обмен с {$server}";
        \Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Company::forceSynchronizeAllCompany {$message}");
        foreach ($companiesData as $companyData) {
            self::synchronize($companyData["ID"], $server);
        }
    }


    //Синхронизурет локальную компанию $companyId c компанией на $server
    static public function synchronize($localCompanyId = 0, $server = '')
    {
        if (empty($server) || (empty($localCompanyId)))
            return [
                'result' => false,
                'message' => "Запущен метод \Tanais\ClientAGR\Company::synchronize() с пустым параметрами"
            ];
        $myCompanyData = \Tanais\ClientAGR\Company::getCompatibleData($localCompanyId);
        $partnerCompanyId = \Tanais\ClientAGR\Company::getPartnerCompanyId($localCompanyId, $server);
        if (empty($partnerCompanyId)) {
            $message = "Не нашли сопаставленную комапнию у {$server}";
            \Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Company::synchronize {$message}");
            return [
                'result' => false,
                'message' => $message
            ];
        }
        $partnerCompanyData = \Tanais\ClientAGR\Company::getCompatibleData($partnerCompanyId, $server);
        if (empty($partnerCompanyData)) {
            $message = "Не получили данные по компании id={$partnerCompanyId} у {$server}";
            \Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Company::synchronize {$message}");
            return [
                'result' => false,
                'message' => $message
            ];
        }
        if (empty($partnerCompanyData['UF_CRM_COMPANY_AGR_UPDATED'])) {
            $message = "Карточка AGR пуста у компания id={$partnerCompanyId} на {$server} ";
            \Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Company::synchronize {$message}");
            return [
                'result' => false,
                'message' => $message
            ];
        }
        if (!empty($myCompanyData['UF_CRM_COMPANY_AGR_UPDATED']))
            $localUpdateTime = new \DateTime($myCompanyData['UF_CRM_COMPANY_AGR_UPDATED']);
        else
            $localUpdateTime = new \DateTime('2020-01-01 00:00:01');

        if (!empty($partnerCompanyData['UF_CRM_COMPANY_AGR_UPDATED']))
            $partnerUpdateTime = new \DateTime($partnerCompanyData['UF_CRM_COMPANY_AGR_UPDATED']);
        else
            $partnerUpdateTime = new \DateTime('2020-01-01 00:00:00');

        // $partnerUpdateTime = new \DateTime($partnerCompanyData['UF_CRM_COMPANY_AGR_UPDATED']);
        //Если локально изменения были выполнены позже, то ничего не делаем
        $canUpdate = true;
        if ($localUpdateTime >= $partnerUpdateTime) {
            $message = "Карточка AGR не обновлялась у компания id={$partnerCompanyId} на {$server} ";
            \Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Company::synchronize {$message}");
            $canUpdate = false;
        }

        $dataForChange = [];
        if ($canUpdate) {
            //Простые поля, которые обрабатываются 1 в 1
            $agrSimpleFields = \Tanais\ClientAGR\Company::getSimpleFieldsForSync();
            foreach ($agrSimpleFields as $fieldCode => $fieldId) {
                if ($myCompanyData[$fieldCode] != $partnerCompanyData[$fieldCode])
                    $dataForChange[$fieldCode] = $partnerCompanyData[$fieldCode];
            }

            //Системные поля требуемые для интеграции
            $dataForChange['UF_CRM_COMPANY_AGR_UPDATED'] = $partnerCompanyData['UF_CRM_COMPANY_AGR_UPDATED'];
            $dataForChange['UF_CRM_COMPANY_AGR_UPDATED_SITE'] = $server;
            $dataForChange['UF_CRM_COMPANY_AGR_UPDATED_BY_ID'] = $partnerCompanyData['UF_CRM_COMPANY_AGR_UPDATED_BY_ID'];
            $dataForChange['UF_CRM_COMPANY_AGR_UPDATED_BY'] = $partnerCompanyData['UF_CRM_COMPANY_AGR_UPDATED_BY'];

            //Поля ссылки на спрвочники или сложно обрабатываемые поля
            foreach (self::REFERENCE_FIELD as $fieldCode => $optionName) {
                $currentValue = $myCompanyData[$fieldCode];
                $partnerValue = $partnerCompanyData[$fieldCode];
                //Если оба поля пусты, то ничего делать не нужно
                if (empty($partnerValue) && empty($currentValue))
                    continue;
                //Если поле партнера пустое, то очищаем наше
                if (empty($partnerValue) && !empty($currentValue)) {
                    $dataForChange[$fieldCode] = $partnerValue;
                    continue;
                }

                $entityTypeId = \Bitrix\Main\Config\Option::get('tanais.clientagr', $optionName);
                $factoryRef = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
                $ufPrefix = "UF_CRM_" . (\Bitrix\Crm\Model\Dynamic\TypeTable::getByEntityTypeId($entityTypeId)->fetch())["ID"] . "_";

                //Если в поле одно значение
                if (!is_array($partnerValue)) {
                    $refElements = $factoryRef->getItems([
                        'select' => ['ID'],
                        'filter' => ["{$ufPrefix}CODE" => $partnerValue]
                    ]);
                    if (empty($refElements) || !is_array($refElements))
                        continue;
                    $refElementId = current($refElements)->getId();
                    if ($refElementId && $myCompanyData[$fieldCode] != $refElementId)
                        $dataForChange[$fieldCode] = $refElementId;
                }

                //Если в поле множественное
                if (is_array($partnerValue)) {
                    // d($partnerValue);
                    foreach ($partnerValue as $referenceElementCode) {
                        d($referenceElementCode);
                        $refElements = $factoryRef->getItems([
                            'select' => ['ID'],
                            'filter' => ["{$ufPrefix}CODE" => $referenceElementCode]
                        ]);
                        if (empty($refElements) || !is_array($refElements))
                            continue;
                        $refElementId = current($refElements)->getId();
                        $newFieldValue[] = $refElementId;
                    }
                    if ($myCompanyData[$fieldCode] != $newFieldValue)
                        $dataForChange[$fieldCode] = $newFieldValue;
                }
            }
        }

        //Обработка поля ABC-анализ
        $abcCompanyId = \Tanais\ClientAGR\Reference::getIdByCode('Mycompany', $partnerCompanyData['ABC_CODE']);
        $abcCompanyValue = $partnerCompanyData['ABC_CLIENT'];
        // d($myCompanyData);
        $newValueA = (!empty($myCompanyData['AGR_A_CLIENT_VALUE']) && is_array($myCompanyData['AGR_A_CLIENT_VALUE'])) ? $myCompanyData['AGR_A_CLIENT_VALUE'] : [];
        $newValueB = (!empty($myCompanyData['AGR_B_CLIENT_VALUE']) && is_array($myCompanyData['AGR_B_CLIENT_VALUE'])) ? $myCompanyData['AGR_B_CLIENT_VALUE'] : [];
        $newValueC = (!empty($myCompanyData['AGR_C_CLIENT_VALUE']) && is_array($myCompanyData['AGR_C_CLIENT_VALUE'])) ? $myCompanyData['AGR_C_CLIENT_VALUE'] : [];
        // echo "<pre>" . var_export(['newValueA' => $newValueA, 'newValueB' => $newValueB, 'newValueC' => $newValueC], true) . "</pre>";
        if ($abcCompanyValue == 'A') {
            $newValueA[] = $abcCompanyId;
            $newValueB = array_diff($newValueB, [$thisServerId]);
            $newValueC = array_diff($newValueC, [$thisServerId]);
        }
        if ($abcCompanyValue == 'B') {
            $newValueA = array_diff($newValueA, [$thisServerId]);
            $newValueB[] = $abcCompanyId;
            $newValueC = array_diff($newValueC, [$thisServerId]);
        }
        if ($abcCompanyValue == 'C') {
            $newValueA = array_diff($newValueA, [$thisServerId]);
            $newValueB = array_diff($newValueB, [$thisServerId]);
            $newValueC[] = $abcCompanyId;
        }
        $newValueA = array_unique($newValueA);
        $newValueB = array_unique($newValueB);
        $newValueC = array_unique($newValueC);

        sort($newValueA);
        sort($newValueB);
        sort($newValueC);

        $needToUpdate = false;
        if ($companyData['UF_CRM_COMPANY_AGR_A_CLIENT'] != $newValueA)
            $needToUpdate = true;
        if ($companyData['UF_CRM_COMPANY_AGR_B_CLIENT'] != $newValueB)
            $needToUpdate = true;
        if ($companyData['UF_CRM_COMPANY_AGR_C_CLIENT'] != $newValueC)
            $needToUpdate = true;
        if ($needToUpdate) {
            $dataForChange['UF_CRM_COMPANY_AGR_A_CLIENT'] = $newValueA;
            $dataForChange['UF_CRM_COMPANY_AGR_B_CLIENT'] = $newValueB;
            $dataForChange['UF_CRM_COMPANY_AGR_C_CLIENT'] = $newValueC;
        }

        // d($dataForChange);
        \Bitrix\Crm\CompanyTable::update($localCompanyId, $dataForChange);
        if ($canUpdate)
            $message = "Данные локальной компании id=$localCompanyId обновлены по карточке внешней компания id={$partnerCompanyId} на {$server}";
        else
            $message = "Обновили только в части ABC анализа";
        \Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Company::synchronize {$message}");

        return [
            'result' => true,
            'message' => $message,
            // 'partnerCompanyData'  => $partnerCompanyData,
            'changedData' => $dataForChange,
        ];
        return true;
    }

    static public function getLinkedCompanyId($remoteCompanyId = 0, $server = ''): array
    {
        if (empty($server) || (empty($remoteCompanyId)))
            return false;

        $link = "https://{$server}/crm/company/details/{$remoteCompanyId}/";
        // d($link);
        $companiesData = \Bitrix\Crm\CompanyTable::getList([
            'select' => ['ID', 'UF_CRM_COMPANY_AGR_LINK'],
            'filter' => ['%UF_CRM_COMPANY_AGR_LINK' => $link],
        ])->fetchAll();
        $return = [];
        foreach ($companiesData as $companyData) {
            $return[] = $companyData["ID"];
        }
        $return = array_unique($return);
        sort($return);
        return $return;
    }

    //Синхронизирует все ABC поля cо всем партнерскими серверами
    static public function syncABCAllServers()
    {
        $servers = \Tanais\ClientAGR\Helper::getAllPartnerServer();
        foreach ($servers as $server) {
            self::syncABC($server);
        }
    }

    //Синхронизирует все ABC поля c партнерским сервером
    static public function syncABC($server)
    {
        if (empty($server))
            return false;

        // \Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Company::syncABC Запущен метод сопаставления компаний по ОГРН с сервером {$server}");
        //Получаем компании с партнёрского сервера
        $webhook = \Tanais\ClientAGR\Helper::getWebhook($server);

        //Получаем удаленные компании
        $partnerCompanyData = \Tanais\ClientAGR\Helper::callRestApi($webhook, 'tanais.clientagr.company.list.json', ['refId' => $referenceId]);
        if (empty($partnerCompanyData))
            return $result;

        //Получаем локальные компании
        $companyController = new  \Tanais\ClientAGR\Controller\Company();
        $myCompanyData = $companyController->listAction();
        if (empty($myCompanyData))
            return $result;

        //Делаем массив для конвертации Организаций в Коды
        $reference = new \Tanais\ClientAGR\Controller\Reference();
        $referenceCompany = $reference->getAction('Mycompany');

        foreach ($myCompanyData as $localCompanyId => $localCompany) {
            $links = $localCompany["UF_CRM_COMPANY_AGR_LINK"];
            foreach ($links as $link) {
                if (str_starts_with($link, "https://" . $server))
                    if (preg_match('/\/(\d+)\/?$/', $link, $matches)) {
                        $partnerCompanyId = $matches[1];
                        if (is_set($partnerCompanyData[$partnerCompanyId])) {
                            // if ($localCompanyId == 133)
                            //     d($partnerCompanyData[$partnerCompanyId]);
                            $abcPartnerCompanyValue = $partnerCompanyData[$partnerCompanyId]['ABC_CLIENT']; //
                            $partnerServerRefCode = $partnerCompanyData[$partnerCompanyId]['ABC_REF_CODE'];
                            $newABCValues = [
                                'UF_CRM_COMPANY_AGR_A_CLIENT' => $localCompany['UF_CRM_COMPANY_AGR_A_CLIENT'],
                                'UF_CRM_COMPANY_AGR_B_CLIENT' => $localCompany['UF_CRM_COMPANY_AGR_B_CLIENT'],
                                'UF_CRM_COMPANY_AGR_C_CLIENT' => $localCompany['UF_CRM_COMPANY_AGR_C_CLIENT'],
                            ];
                            if ($abcPartnerCompanyValue == "A") {
                                $newABCValues['UF_CRM_COMPANY_AGR_A_CLIENT'][] = $partnerServerRefCode;
                                $newABCValues['UF_CRM_COMPANY_AGR_B_CLIENT'] = array_diff($newABCValues['UF_CRM_COMPANY_AGR_B_CLIENT'], [$partnerServerRefCode]);
                                $newABCValues['UF_CRM_COMPANY_AGR_C_CLIENT'] = array_diff($newABCValues['UF_CRM_COMPANY_AGR_C_CLIENT'], [$partnerServerRefCode]);
                            }
                            if ($abcPartnerCompanyValue == "B") {
                                $newABCValues['UF_CRM_COMPANY_AGR_A_CLIENT'] = array_diff($newABCValues['UF_CRM_COMPANY_AGR_A_CLIENT'], [$partnerServerRefCode]);
                                $newABCValues['UF_CRM_COMPANY_AGR_B_CLIENT'][] = $partnerServerRefCode;
                                $newABCValues['UF_CRM_COMPANY_AGR_C_CLIENT'] = array_diff($newABCValues['UF_CRM_COMPANY_AGR_C_CLIENT'], [$partnerServerRefCode]);
                            }
                            if ($abcPartnerCompanyValue == "C") {
                                $newABCValues['UF_CRM_COMPANY_AGR_A_CLIENT'] = array_diff($newABCValues['UF_CRM_COMPANY_AGR_A_CLIENT'], [$partnerServerRefCode]);
                                $newABCValues['UF_CRM_COMPANY_AGR_B_CLIENT'] = array_diff($newABCValues['UF_CRM_COMPANY_AGR_B_CLIENT'], [$partnerServerRefCode]);
                                $newABCValues['UF_CRM_COMPANY_AGR_C_CLIENT'][] = $partnerServerRefCode;
                            }

                            $newABCValues['UF_CRM_COMPANY_AGR_A_CLIENT'] = array_unique($newABCValues['UF_CRM_COMPANY_AGR_A_CLIENT']);
                            $newABCValues['UF_CRM_COMPANY_AGR_B_CLIENT'] = array_unique($newABCValues['UF_CRM_COMPANY_AGR_B_CLIENT']);
                            $newABCValues['UF_CRM_COMPANY_AGR_C_CLIENT'] = array_unique($newABCValues['UF_CRM_COMPANY_AGR_C_CLIENT']);

                            sort($newABCValues['UF_CRM_COMPANY_AGR_A_CLIENT']);
                            sort($newABCValues['UF_CRM_COMPANY_AGR_B_CLIENT']);
                            sort($newABCValues['UF_CRM_COMPANY_AGR_C_CLIENT']);

                            foreach ($newABCValues['UF_CRM_COMPANY_AGR_A_CLIENT'] as &$value)
                                $value = $referenceCompany[$value]["ID"];
                            foreach ($newABCValues['UF_CRM_COMPANY_AGR_B_CLIENT'] as &$value)
                                $value = $referenceCompany[$value]["ID"];
                            foreach ($newABCValues['UF_CRM_COMPANY_AGR_C_CLIENT'] as &$value)
                                $value = $referenceCompany[$value]["ID"];
                            // foreach ($newABCValues as &$value)
                            //     if (is_array($value) && count($value) > 0)
                            //         $value = serialize($value);
                            //     else
                            //         $value = "";
                            // if ($localCompanyId == 133)
                            // d([$localCompanyId,$newABCValues]);
                            $updateResult = \Bitrix\Crm\CompanyTable::update($localCompanyId, $newABCValues);
                            if (!$updateResult->isSuccess()) {
                                $errors = $updateResult->getErrorMessages();
                                \Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Company::syncABC('{$server}');");
                                \Tanais\ClientAGR\Log::add($errors);
                            }
                        }
                    }
            }
        }

        return true;
    }


    public static function updateAGRRegionByRequisite($companyId, $rewrite = false): void
    {
        global $USER;

        $ufCodeRegion = 'UF_CRM_COMPANY_AGR_REGION';

        $companies = [];
        $filter = [];
        $filter['ID'] = $companyId;

        if (!$rewrite) {
            $filter[$ufCodeRegion] = null;
        }
        $entityTypeId = \Bitrix\Main\Config\Option::get('tanais.clientagr', "listIdRegion");
        $regionTable = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId)->getDataClass();
        $arRegions = $regionTable::getList([
            "select" => [
                'ID',
                'TITLE',
            ],
        ])->fetchAll();


        $rsCompanies = \Bitrix\Crm\CompanyTable::getList([
            'order' => ['ID' => 'DESC'],
            'filter' => $filter,
            //'filter' => ['ID' => 3790],
            'select' => ['ID', $ufCodeRegion,]
        ]);

        $req = new \Bitrix\Crm\EntityRequisite();

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

            $regionFromProvince = '';
            $regionFallback = '';

            // Извлекаем регион из адреса
            foreach ($addresses as $address) {
                // d($address);
                $province = trim($address['PROVINCE'] ?? '');
                $country = trim($address['COUNTRY'] ?? '');

                // Если стран не Россия ставим страну
                if (mb_strtolower($country) !== 'россия' && !empty($country)) {
                    $regionFromProvince = $country;
                    break;
                }

                // Если есть PROVINCE используем его
                if (!empty($province)) {
                    $regionFromProvince = $province;
                    break;
                }

                // Если нет PROVINCE пробуем ADDRESS_1
                if (empty($province) && !empty($address['ADDRESS_1'])) {
                    if (empty($regionFallback) || mb_strlen($address['ADDRESS_1']) > mb_strlen($regionFallback)) {
                        $regionFallback = $address['ADDRESS_1'];
                    }
                }
            }

            // если нормальный регион не найден, используем запасной
            $company['RQ_REGION'] = $regionFromProvince ?: $regionFallback;

            $region = self::normalizeRegionString($company['RQ_REGION']);
            // Если регион нормальный по длине — ищем элемент в массиве "Регионы"
            //  d($company['RQ_REGION']);
            if (mb_strlen($region) > 3) {
                foreach ($arRegions as $reg) {
                    $title = self::normalizeRegionString($reg['TITLE']);
                    if (mb_stripos($title, $region) !== false
                        || mb_stripos($region, $title) !== false) {
                        $company['NEW_VALUE'] = $reg['ID'];
                        $company['TITLE'] = $title;
                        break;
                    }
                }
            }

            $companies[$company['ID']] = $company;
        }

        ksort($companies);

        // Обновляем компании
        $container = \Bitrix\Crm\Service\Container::getInstance();
        $factory = $container->getFactory(\CCrmOwnerType::Company);


        foreach ($companies as $company) {
            if (!empty($company['NEW_VALUE'])) {
//                $debug[] = [
//                    'TITLE' => $company['TITLE'],
//                    'ID' => $company['ID'],
//                    'RQ_REGION' => $company['RQ_REGION'],
//                    'NEW_VALUE' => $company['NEW_VALUE'],
//                ];
                $item = $factory->getItem($company['ID']);
                if ($item) {
                    $item->set($ufCodeRegion, [$company['NEW_VALUE']]);
                    $operation = $factory->getUpdateOperation($item);
                    $operation->disableAllChecks();
                    $operation->disableBizProc();
                    $operation->launch();
                }
            }
        }
        // d($debug);
    }

    public static function updateAllCompanyAGRRegionByRequisite($rewrite = false): void
    {
        $rsCompanies = \Bitrix\Crm\CompanyTable::getList([
            'order' => ['ID' => 'ASC'],
            'select' => ['ID']
        ])->fetchAll();
        foreach ($rsCompanies as $company) {
            \Tanais\ClientAGR\Company::updateAGRRegionByRequisite($company['ID'], $rewrite);
        }

    }

    public static function normalizeRegionString(string $str): string
    {
        $str = mb_strtolower($str);
        // Список лишних слов, которые нужно убрать из названия региона
        $arrCut = [' - ', ')', '(', '.', 'Кузбасс', 'Чувашия', ')', '(', ' .', 'Область', 'обл', 'республика', 'респ', 'край', 'ублика', 'город', 'АО', 'г', ')', '(', ' . ', 'обл.'];
        // Очистка региона от мусора
        $str = str_replace(['.', '(', ')'], ' ', $str);
        $str = preg_replace('/(?<=\p{L})-(?=\p{L})/u', '<<<DASH>>>', $str);
        $str = mb_strtolower($str);
        $str = preg_replace('/\b(' . implode('|', array_map('preg_quote', $arrCut)) . ')\b/iu', ' ', $str);
        $str = str_replace('<<<dash>>>', '-', $str);
        $str = trim(preg_replace('/\s+/', ' ', $str));

        return $str;
    }

}
