<?

namespace Tanais\ClientAGR;

use Bitrix\Main\EventResult;
use Bitrix\Main\Event;

class EventHandler
{

    const EVENT_HANDLERS = [
        ["main", "OnProlog",                "tanais.clientagr", "\Tanais\ClientAGR\EventHandler", "doOnProlog"], //в начале визуальной части пролога сайта
        ["main", "OnPageStart",             "tanais.clientagr", "\Tanais\ClientAGR\EventHandler", "doOnPageStart"], //в начале выполняемой части пролога сайта, после подключения всех библиотек и отработки агентов
        
        //Компании
        ["crm", "OnAfterCrmCompanyUpdate",  "tanais.clientagr", "\Tanais\ClientAGR\EventHandler", "doOnAfterCrmCompanyUpdate"],
        ["crm", "OnBeforeCrmCompanyUpdate", "tanais.clientagr", "\Tanais\ClientAGR\EventHandler", "doOnBeforeCrmCompanyUpdate"],
        
        //Смарт-процессы
        ["crm", "OnCrmDynamicItemUpdate",   "tanais.clientagr", "\Tanais\ClientAGR\EventHandler", "doOnCrmDynamicItemUpdate"],
        ["crm", "OnCrmDynamicItemAdd",      "tanais.clientagr", "\Tanais\ClientAGR\EventHandler", "doOnCrmDynamicItemUpdate"],
        ["crm", "OnCrmDynamicItemDelete",   "tanais.clientagr", "\Tanais\ClientAGR\EventHandler", "doOnCrmDynamicItemUpdate"],

        ["crm", "OnAfterCrmControlPanelBuild", "tanais.clientagr", "\Tanais\ClientAGR\EventHandler", "doOnAfterCrmControlPanelBuild"],

        //Генератор документов
        // ["crm", "onCrmDocumentGeneratorDocumentAdd",      "tanais.clientagr", "\Tanais\ClientAGR\EventHandler", "doOnCrmDocumentGeneratorDocument"],
        // ["crm", "onCrmDocumentGeneratorDocumentUpdate",   "tanais.clientagr", "\Tanais\ClientAGR\EventHandler", "doOnCrmDocumentGeneratorDocument"],
        // ["crm", "onCrmDocumentGeneratorDocumentDelete",   "tanais.clientagr", "\Tanais\ClientAGR\EventHandler", "doOnCrmDocumentGeneratorDocument"],
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
        //     if ((str_starts_with($scriptURL, '/workgroups/group/')) && (str_contains($scriptURL, '/edit/'))) {
        //         \Bitrix\Main\UI\Extension::load('tanais.alter.crm.project');
        //     }
        return true;
    }

    public static function doOnPageStart()
    {
        return true;
    }

    public static function doOnAfterCrmCompanyUpdate(&$arFields)
    {
        // var_export($arFields);
        $companyId = $arFields['ID'];
        // \Tanais\ClientAGR\Log::add("doOnAfterCrmCompanyUpdate companyId={$companyId}");

        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook1');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook2');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook3');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook4');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook5');
        $webhooks = array_unique($webhooks);
        $params = [
            'companyId' => $companyId,     // пример параметра
            'server' => \Bitrix\Main\Config\Option::get("main", "server_name", ""),
        ];
        foreach ($webhooks as &$webhook) {
            if (empty($webhook)) continue;
            if (defined("TANAIS_CLIENTAGR_STOP_SEND_WEBHOOK")) continue; //Если мы получили вебхук и что меняем, то никого не уведомляем
            \Tanais\ClientAGR\Helper::callRestApi($webhook, 'tanais.clientagr.company.webhook.json',  $params, false);
        }
        return true;
    }

    public static function doOnBeforeCrmCompanyUpdate(&$arFields)    {
        //Получаем текущие данные компании
        $companyId = $arFields['ID'];
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Company);
        $company = $factory->getItem($companyId)->getCompatibleData();
        //Поля могут быть выставлены только автоматически        
        unset($arFields['UF_CRM_COMPANY_AGR_DATE_MATCHED']);
        unset($arFields['UF_CRM_COMPANY_AGR_MATCHED_BY']);

        //Формируем массив измененных полей UF_CRM_COMPANY_AGR_
        $changedField = [];
        $agrFieldsChanged = false;
        foreach ($arFields as $fieldKey => $fieldvalue) {
            //Отслеживаем только измененные пользовательские поля, которые начинаются с UF_CRM_COMPANY_AGR_ 

            if (
                str_starts_with($fieldKey, 'UF_CRM_COMPANY_AGR_')
                && $fieldKey != "UF_CRM_COMPANY_AGR_A_CLIENT"
                && $fieldKey != "UF_CRM_COMPANY_AGR_B_CLIENT"
                && $fieldKey != "UF_CRM_COMPANY_AGR_C_CLIENT"
                && $company[$fieldKey] != $fieldvalue
            ) {
                $agrFieldsChanged = true;
                $changedField[$fieldKey]['CURRENT_VALUE'] = $company[$fieldKey];
                $changedField[$fieldKey]['NEW_VALUE'] = $fieldvalue;
            }
        }

        //Если меняется что-то из карточки AGR
        if ($agrFieldsChanged) {

            //Шаг 1. Если меняется поле UF_CRM_COMPANY_AGR_LINK, то заполняем поля с датой и кем выполнено сопоставление
            if ($changedField['UF_CRM_COMPANY_AGR_LINK']) {
                $arFields['UF_CRM_COMPANY_AGR_DATE_MATCHED']       = new \Bitrix\Main\Type\DateTime();
                $arFields['UF_CRM_COMPANY_AGR_MATCHED_BY']       = \Bitrix\Main\Engine\CurrentUser::get()->getFormattedName();
            }
            //Шаг 2. Заполняем поля с датой и кем выполнено обновление
            else if (!isset($changedField['UF_CRM_COMPANY_AGR_UPDATED'])) {
                $arFields['UF_CRM_COMPANY_AGR_UPDATED']       = new \Bitrix\Main\Type\DateTime();
                $arFields['UF_CRM_COMPANY_AGR_UPDATED_BY']    = \Bitrix\Main\Engine\CurrentUser::get()->getFormattedName();
                $arFields['UF_CRM_COMPANY_AGR_UPDATED_BY_ID'] = \Bitrix\Main\Engine\CurrentUser::get()->getId();
                $arFields['UF_CRM_COMPANY_AGR_UPDATED_SITE']  = \Bitrix\Main\Config\Option::get("main", "server_name", "");
            }
            //Шаг 3. Если новое значение поля UF_CRM_COMPANY_AGR_UPDATED "не обновлять", то не обновляем поля с датой и кем обновлено
            //Требует тестирования
            if ($changedField['UF_CRM_COMPANY_AGR_UPDATED']['NEW_VALUE'] == 'не обновлять') {
                unset($arFields['UF_CRM_COMPANY_AGR_UPDATED']);
                unset($arFields['UF_CRM_COMPANY_AGR_UPDATED_BY']);
                unset($arFields['UF_CRM_COMPANY_AGR_UPDATED_BY_ID']);
                unset($arFields['UF_CRM_COMPANY_AGR_UPDATED_SITE']);
            }
            //Записываем в историю компании события изменения полей UF_CRM_COMPANY_AGR_
            foreach ($changedField as $fieldKey => $fieldvalue) {
                //Получаем название поля по его символьному коду 
                $ufInstance = \Bitrix\Crm\UserField\UserFieldManager::getUserFieldEntity(\CCrmOwnerType::Company);
                $ufData = $ufInstance->GetFieldById($fieldKey);
                $fieldTitle = $ufData['EDIT_FORM_LABEL'];

                if (is_array($fieldvalue['CURRENT_VALUE']))
                    $fieldvalue['CURRENT_VALUE'] = implode(', ', $fieldvalue['CURRENT_VALUE']);
                if (is_array($fieldvalue['NEW_VALUE']))
                    $fieldvalue['NEW_VALUE'] = implode(', ', $fieldvalue['NEW_VALUE']);

                //Записываем событие изменения поля в историю компании
                $addEventResult = \Bitrix\Crm\EventTable::add([
                    'ENTITY_TYPE' => 'COMPANY',
                    'ENTITY_ID'   => $companyId,
                    'EVENT_ID'  => "",
                    'EVENT_TYPE'  => \CCrmEvent::TYPE_CHANGE,
                    'EVENT_NAME'  => "Значение поля \"{$fieldTitle}\" было изменено",
                    'EVENT_TEXT_1' => $fieldvalue['CURRENT_VALUE'] ? $fieldvalue['CURRENT_VALUE'] : '(пусто)',
                    'EVENT_TEXT_2' => $fieldvalue['NEW_VALUE'] ? $fieldvalue['NEW_VALUE'] : '(пусто)',
                    'CREATED_BY_ID'     => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
                    'DATE_CREATE' => new \Bitrix\Main\Type\DateTime(),
                ]);
                $eventId = $addEventResult->getId();
                if ($eventId) {
                    $addEventRelationResult = \Bitrix\Crm\EventRelationsTable::add([
                        'EVENT_ID' => $eventId,
                        'ENTITY_TYPE' => 'COMPANY',
                        'ASSIGNED_BY_ID' => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
                        'ENTITY_ID' => $companyId,
                        'ENTITY_FIELD' => $fieldKey,
                    ]);
                    $eventRelationId = $addEventRelationResult->getId();
                }
            }
        }

        // if ($changedField) {
        //     \Tanais\ClientAGR\Log::add(["doOnBeforeCrmCompanyUpdate", $companyId, $changedField]);
        // }
        // \Tanais\ClientAGR\Log::add("doOnBeforeCrmCompanyUpdate companyId={$companyId}");
        return true;
    }

    public static function doOnCrmDynamicItemUpdate(\Bitrix\Main\Event $event)
    {
        $parameters = $event->getParameters();
        $item = $parameters["item"];
        $itemId = $parameters["id"];
        $entityTypeId = $item->getEntityTypeId();
        $reference = [];
        foreach (\Tanais\ClientAGR\Reference::REFERENCE_OPTIONS as $referenceName => $optionName) {
            $referenceEntityTypeId = \Bitrix\Main\Config\Option::get('tanais.clientagr', $optionName);
            if (!empty($referenceEntityTypeId))
                $reference[$referenceEntityTypeId] = $referenceName;
        }
        //Уведомление об изменении справочника
        if (!empty($reference[$entityTypeId])) {
            $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook1');
            $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook2');
            $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook3');
            $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook4');
            $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook5');
            $server = \Bitrix\Main\Config\Option::get("main", "server_name", "");
            $referenceName = $reference[$entityTypeId];
            $itemCode = \Tanais\ClientAGR\Reference::getCode($reference[$entityTypeId], $itemId);
            \Tanais\ClientAGR\Log::add("doOnCrmDynamicItemUpdate Выявили изменение справочника [{$itemId}]{$itemCode}@{$reference[$entityTypeId]}");

            $webhooks = array_unique($webhooks);
            $params = [
                'server' => $server,
                'reference' =>  $referenceName,
                'code' => $itemCode,     // пример параметра
            ];
            foreach ($webhooks as &$webhook) {
                if (empty($webhook)) continue;
                if (defined("TANAIS_CLIENTAGR_STOP_SEND_WEBHOOK")) continue; //Если мы получили вебхук и что меняем, то никого не уведомляем
                \Tanais\ClientAGR\Helper::callRestApi($webhook, 'tanais.clientagr.reference.webhook.json',  $params, false);
                \Tanais\ClientAGR\Log::add("doOnCrmDynamicItemUpdate Call {$webhook}tanais.clientagr.reference.webhook.json by CURL {$itemCode}@{$referenceName}");
            }
        }
    }


    public static function doOnAfterCrmControlPanelBuild(&$menuItems)
    {
        $accessCompaniesAGR = false;
        $accessUsersStr = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'accessUsers');
        $accessUsers = array_filter(array_map(fn($v) => (int)$v, explode(',', $accessUsersStr)), fn($id) => $id > 0);
        if (
            \Bitrix\Main\Engine\CurrentUser::get()->isAdmin() ||
            (is_array($accessUsers) && in_array(\Bitrix\Main\Engine\CurrentUser::get()->getId(), $accessUsers, true))
        ) {
            $accessCompaniesAGR = true;
        }

        foreach ($menuItems as &$item) {
            if ($item['ID'] == 'crm_clients') {
                if ($accessCompaniesAGR) {
                    $item['ITEMS'][] =
                        [
                            'ID' => 'AGR_COMPANIES',
                            // 'MENU_ID' => 'menu_crm_custom_reports',
                            'MENU_ID' => 'menu_crm_company_agr',
                            'NAME' => 'Компании AGR',
                            'TITLE' => 'Компании AGR',
                            'URL' => '/clientagr/report/',
                        ];
                }
            }
        }
    }
}
