<?php

namespace Tanais\ClientAGR;

\Bitrix\Main\Loader::includeModule('crm');

class Reference

{
    const REFERENCE_OPTIONS = [
        'Region' => 'listIdRegion',
        'Holding' => 'listIdHolding',
        'Software' => 'listIdSoftware',
        'Business' => 'listIdBusiness',
        'Mycompany' => 'listIdMycompany',
    ];

    //Получить список полей справочника. $referenceId - ID справочника одно из 4=х Region,  Holding,  Software, Business,  Mycompany
    public static function getFields($referenceId)
    {
        $container =  \Bitrix\Crm\Service\Container::getInstance();
        $entityTypeId = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'listId' . ucfirst(strtolower($referenceId)));
        $factory = $container->getFactory($entityTypeId);
        $fieldsCollection = $factory->getFieldsCollection();
        $fields = [];
        foreach ($fieldsCollection as $field) {
            if (str_starts_with($field->getName(), 'UF_CRM_')) {
                $code = explode('_', $field->getName());
                $code = array_slice($code, 3);
                $code = implode('_', $code);
                $fields[$code] = $field->getName();
            }
        }
        //Справочник без доп полей
        if (empty($fields) && $factory) {
            $fields['TITLE'] = 'TITLE';
        }
        return $fields;
    }

    public static function getItems($referenceId)
    {
        $container =  \Bitrix\Crm\Service\Container::getInstance();
        $entityTypeId = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'listId' . ucfirst(strtolower($referenceId)));
        if (empty($entityTypeId))
            return false;
        $factory = $container->getFactory($entityTypeId);
        // d($factory);
        $fields = self::getFields($referenceId);
        if (empty($fields))
            return [];
        $select = [];
        foreach ($fields as $code => $uf) {
            $select[] = $uf;
        }
        // var_export($select);
        $select[] = 'UPDATED_TIME';
        $select[] = 'TITLE';
        $select[] = 'ID';
        $refItems = $factory->getItems([
            'filter' => [],
            'select' => $select,
        ]);
        // d($refItems);
        $rawData = [];
        foreach ($refItems as $refItem) {
            // var_export($refItem->getCompatibleData());
            // die;
            $rawData[$refItem->getId()]['ID'] = $refItem->get('ID');
            $rawData[$refItem->getId()]['TITLE'] = $refItem->get('TITLE');
            foreach ($fields as $code => $uf) {
                $rawData[$refItem->getId()][$code] = $refItem->get($uf);
            }
            $rawData[$refItem->getId()]['UPDATED_TIME'] = $refItem->get('UPDATED_TIME');
            $rawData[$refItem->getId()]['SERVER'] = \Bitrix\Main\Config\Option::get("main", "server_name", "");
            // $rawData[$refItem->getId()]['SERVER'] = \Bitrix\Main\Config\Option::get("main", "server_name", "");
        }
        foreach ($rawData as $data) {
            $return[$data['CODE']] = $data;
        }
        return $return;
    }

    //Синхронизурет локальный справочник $referenceId со справочником на сервером $server
    static public function synchronize($server, $referenceId)
    {
        define("TANAIS_CLIENTAGR_STOP_SEND_WEBHOOK", true);
        \Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Reference::synchronize({$server}, {$referenceId})");
        if (empty($server) or empty($referenceId))
            return false;
        //Ищем в настройках модуля, где запрашивать данные    
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook1');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook2');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook3');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook4');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook5');

        $webhooks = array_filter($webhooks, function ($url) use ($server) {
            return strpos($url, $server) !== false;
        });
        $webhooks = array_values(array_unique($webhooks));
        if (empty(current($webhooks)))
            return false;
        $webhook = current($webhooks) . 'tanais.clientagr.Reference.get.json';

        //Делаем запрос на удалённый сервер через CURL
        $ch = curl_init($webhook);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        $params = [
            'refId' => $referenceId,     // пример параметра
        ];
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            echo "cURL error: $err\n";
            return false;
        }
        curl_close($ch);

        //Обрабатываем полученные данные 
        $getData = json_decode($response, true);
        $getData = $getData['result'];
        if (empty($getData) || !is_array(current($getData)))
            return false;
        var_export($getData);

        //Берем локальный справочник
        $currentData = \Tanais\ClientAGR\Reference::getItems($referenceId);

        //Ищем различающиеся данные складываем в $changedRow
        foreach ($getData as $id => &$data) {
            $code = $data["CODE"];
            foreach ($data as $field => $value) {
                if ($field != 'ID' && $field != 'UPDATED_TIME' && $field != 'SERVER' && trim($currentData[$code][$field]) != trim($value)) {
                    $changedRow[$code]['ID'] = $currentData[$code]['ID'];
                    $changedRow[$code]['LOCAL_UPDATED_TIME'] = $currentData[$code]['UPDATED_TIME'];
                    $changedRow[$code]['UPDATED_TIME'] = $data['UPDATED_TIME'];
                    $changedRow[$code][$field] = trim($value);
                }
            }
        }
        // var_export($changedRow);  
        //Если есть изменения, пытаемся их обработать и записать
        if (!empty($changedRow)) {
            $container =  \Bitrix\Crm\Service\Container::getInstance();
            $entityTypeId = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'listId' . ucfirst(strtolower($referenceId)));
            $factory = $container->getFactory($entityTypeId);
            $dataClass = $factory->getDataClass();
            $tableName = $dataClass::getTableName();
            $connection = \Bitrix\Main\Application::getConnection();
            $sqlHelper  = $connection->getSqlHelper();

            //Находим правильные коды полей CODE=>UF_CRM_39_CODE и формируем массив  $arFields
            $fields = \Tanais\ClientAGR\Reference::getFields($referenceId);
            $data = [];
            foreach ($changedRow as $elementCode => $elementData) {
                $data[$elementCode]['ID'] = $elementData['ID'];
                $data[$elementCode]['TITLE'] = $elementData['TITLE'];
                $data[$elementCode]['UPDATED_TIME'] = $elementData['UPDATED_TIME'];
                $data[$elementCode]['LOCAL_UPDATED_TIME'] = $elementData['LOCAL_UPDATED_TIME'];
                foreach ($fields as $shortCode => $ufCode) {
                    if (isset($elementData[$shortCode])) {
                        $data[$elementCode][$ufCode] = $elementData[$shortCode];
                    }
                }
            }
            // var_export($currentData);  
            foreach ($data as $elementCode => $elementData) {
                //Сохраняем дату обновления в перменные Datetime    
                $updateTime = new \DateTime($elementData["UPDATED_TIME"]);
                $localUpdateTime = new \DateTime($elementData["LOCAL_UPDATED_TIME"]);
                if ($elementData['TITLE'] != $currentData[$elementCode]['TITLE']) {
                    $localUpdateTime = new \DateTime("2000-01-01");
                }

                //Создаем элемент справочника
                if (empty($elementData["ID"])) {
                    unset($elementData["ID"], $elementData["LOCAL_UPDATED_TIME"], $elementData["UPDATED_TIME"],);
                    $newItem = $factory->createItem();
                    $newItem->setFromCompatibleData($elementData);
                    $operation = $factory->getAddOperation($newItem);
                    $operation->disableAllChecks();
                    $operation->disableBizProc();
                    $operationResult = $operation->launch();

                    if ($operationResult->isSuccess()) {
                        $dataClass::update($newItem->getId(), ['UPDATED_TIME' => $updateTime->format("d.m.Y H:i:s")]);
                    }
                } else {
                    $updateItem = $factory->getItem($elementData["ID"]);
                    unset($elementData["ID"], $elementData["LOCAL_UPDATED_TIME"], $elementData["UPDATED_TIME"],);
                    //Не обновляем элементы, который были изменены локально $updateTime > $localUpdateTime
                    if ($updateItem && ($updateTime > $localUpdateTime)) {
                        $updateItem->setFromCompatibleData($elementData);
                        if (!empty($elementData["TITLE"]))
                            $updateItem->setTitle($elementData["TITLE"]);
                        else
                            $updateItem->setTitle($currentData[$elementCode]['TITLE']);
                        $operation = $factory->getUpdateOperation($updateItem);
                        $operation->disableAllChecks();
                        $operation->disableBizProc();
                        $operationResult = $operation->launch();
                        //Ставим дату обновления такую же, как на удаленном сервере
                        if ($operationResult->isSuccess()) {
                            $dataClass::update($updateItem->getId(), ['UPDATED_TIME' => $updateTime->format("d.m.Y H:i:s")]);
                        }
                    }
                }
            }
        }

        return $updateResult;
    }

    //Возвращает код текущего сервера по справочнику компании AGR
    public static function getThisServerRef()
    {
        $container =  \Bitrix\Crm\Service\Container::getInstance();
        $entityTypeId = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'listIdMycompany');
        $factoryMycompanyRef = $container->getFactory($entityTypeId);
        $ufPrefix = "UF_CRM_" . (\Bitrix\Crm\Model\Dynamic\TypeTable::getByEntityTypeId($entityTypeId)->fetch())["ID"] . "_";
        $serverUrl = \Bitrix\Main\Config\Option::get('main', 'server_name');
        $refElement = $factoryMycompanyRef->getItems([
            'select' => ['*'],
            'filter' => ["{$ufPrefix}URL" => $serverUrl]
        ]);
        $return = [];
        if (is_array($refElement) && current($refElement)) {
            return [
                'ID' => current($refElement)->getId(),
                'CODE' => current($refElement)->get("{$ufPrefix}CODE"),
                'TITLE' => current($refElement)->get("TITLE")
            ];
        }
        return [];
    }
    public static function getIdByCode($reference = "", $code = "")
    {
        if (empty($reference) || empty($code))
            return false;
        $container =  \Bitrix\Crm\Service\Container::getInstance();
        $entityTypeId = \Bitrix\Main\Config\Option::get('tanais.clientagr', "listId{$reference}");
        if (empty($entityTypeId))
            return false;
        $factoryMycompanyRef = $container->getFactory($entityTypeId);
        $ufPrefix = "UF_CRM_" . (\Bitrix\Crm\Model\Dynamic\TypeTable::getByEntityTypeId($entityTypeId)->fetch())["ID"] . "_";
        $refElement = $factoryMycompanyRef->getItems([
            'select' => ['*'],
            'filter' => ["{$ufPrefix}CODE" => $code]
        ]);
        if (is_array($refElement) && current($refElement))
            return current($refElement)->getId();
        return false;
    }

    public static function getCode($reference = "", $itemId)
    {
        if (empty($reference) || empty($itemId))
            return false;
        $container =  \Bitrix\Crm\Service\Container::getInstance();
        $entityTypeId = \Bitrix\Main\Config\Option::get('tanais.clientagr', "listId{$reference}");

        if (empty($entityTypeId))
            return false;
        $factoryRef = $container->getFactory($entityTypeId);
        $ufPrefix = "UF_CRM_" . (\Bitrix\Crm\Model\Dynamic\TypeTable::getByEntityTypeId($entityTypeId)->fetch())["ID"] . "_";

        $refElement = $factoryRef->getItem($itemId);
        if (empty($refElement))
            return false;
        $code = $refElement->get("{$ufPrefix}CODE");
        return $code;
    }
}
