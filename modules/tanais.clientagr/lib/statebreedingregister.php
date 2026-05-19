<?php

namespace Tanais\ClientAGR;

class StateBreedingRegister
{
    public static function updateBreedingRegisterFields(): void
    {
        $urlRegister = 'http://opendata.mcx.ru/opendata/7708075454-plemennoyregistr/meta.xml';
        $xmlContent = file_get_contents($urlRegister);
        if (!$xmlContent) {
            die(\Tanais\Alta\Log::save('StateBreedingRegister', "Не удалось загрузить XML по адресу: $urlRegister"));
        }
        $xml = new \SimpleXMLElement($xmlContent);
        $dataversions = $xml->xpath('//dataversion');
        if (!$dataversions) {
            die(\Tanais\Alta\Log::save('StateBreedingRegister', "В файле нет <dataversion>"));
        }
        $lastDataversion = end($dataversions);
        $lastLink = (string)$lastDataversion->source;

        $urlStateBreeding = $lastLink;
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/upload/tanais.alta/state_breeding_register/';
        $archivePath = $uploadDir . 'stateBreedingRegister.zip';
        $xmlPath = $uploadDir . 'stateBreedingRegister.xml';
        $csvPath = $uploadDir . 'stateBreedingRegister.csv';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        file_put_contents($archivePath, fopen($urlStateBreeding, 'r'));

        if (!file_exists($archivePath)) {
            die(\Tanais\Alta\Log::save('StateBreedingRegister', 'Не удалось скачать файл.'));
        }
        $zip = new \ZipArchive;
        if ($zip->open($archivePath) === TRUE) {
            $zip->extractTo($uploadDir);
            $zip->close();
        } else {
            die(\Tanais\Alta\Log::save('StateBreedingRegister', 'Не удалось разархивировать файл.'));
        }

        $extractedFiles = glob($uploadDir . '*.xml');
        if (!empty($extractedFiles)) {
            rename($extractedFiles[0], $xmlPath);
        } else {
            die(\Tanais\Alta\Log::save('StateBreedingRegister', 'Не удалось найти разархивированный XML файл.'));
        }
        unlink($archivePath);

        $requisite = self::getCompanyRequisite();
        $currentCompaniesBusinessTypes = self::getCompaniesBusinessTypes();
        $newBusinessTypeId = self::getBusinessType();
        $companiesOldValues = self::getCompaniesOldValues();

        $xmlfile = file_get_contents($xmlPath);
        $new = simplexml_load_string($xmlfile);
        $con = json_encode($new);
        $newArr = json_decode($con, true);

        $entityObject = new \CCrmCompany();
        $arCompanyUpdate = [];
        $arNoCompany[] = ['ID', 'Номер свидетельства', 'Приказ создания', 'Дата приказа создания', 'УИН', 'Регион', 'Отрасль', 'Регистрант', 'Вид организации', 'Вид животного', 'Порода', 'Адрес организации'];

        foreach ($newArr["plemennoy_registr"] as $value) {
            $ogrn = !empty($value['ogrn']) ? preg_replace('/\D/', '', $value['ogrn']) : '';
            $inn = !empty($value['inn']) ? preg_replace('/\D/', '', $value['inn']) : '';
            $kpp = !empty($value['kpp']) ? preg_replace('/\D/', '', $value['kpp']) : '';

            $key = null;

            if ($ogrn && isset($requisite[$ogrn])) {
                $key = $ogrn;
            } elseif ($inn && $kpp && isset($requisite[$inn . '_' . $kpp])) {
                $key = $inn . '_' . $kpp;
            }

            if ($requisite[$key]) {
                $companyId = $requisite[$key];
                $currentValues = $currentCompaniesBusinessTypes[$companyId] ?? [];
                if (!is_array($currentValues)) {
                    $currentValues = [];
                }
                if ($newBusinessTypeId && !in_array($newBusinessTypeId, $currentValues)) {
                    $currentValues[] = $newBusinessTypeId;
                }
                $animalType = !is_array($value['vid_zhivotnogo']) ? $value['vid_zhivotnogo'] : '';
                $dateConfirmation = (new \DateTime($value['data_prikaza_sozdaniya']))->format('d.m.Y');
                if ($animalType != $companiesOldValues[$companyId]['UF_CRM_COMPANY_AGR_ANIMAL_TYPE'] ||
                    $dateConfirmation != $companiesOldValues[$companyId]['UF_CRM_COMPANY_AGR_DATE_CONFIRMATION']) {
                    $entityFields = [
                        'UF_CRM_COMPANY_AGR_DATE_CONFIRMATION' => $dateConfirmation,
                        'UF_CRM_COMPANY_AGR_ANIMAL_TYPE' => $animalType,
                        'UF_CRM_COMPANY_AGR_ACTIVITY_TYPE' => $currentValues,
                    ];
                   // $ar[$companyId] = $entityFields;
                    $entityObject->Update($companyId, $entityFields);
                }
                $arCompanyUpdate[] = $companyId;
            } else {
                unset($value['rn']);
                unset($value['seriya_svidetelstva']);
                $arNoCompany[] = $value;
            }
        }

        $companyResult = \Bitrix\Crm\CompanyTable::getList([
            'select' => [
                'ID'
            ],
            'filter' => ['!UF_CRM_COMPANY_AGR_DATE_CONFIRMATION' => null, '!UF_CRM_COMPANY_AGR_ANIMAL_TYPE' => null],
        ]);
        foreach ($companyResult as $company) {
            if (!in_array($company['ID'], $arCompanyUpdate)) {
                $entityFields = [
                    'UF_CRM_COMPANY_AGR_DATE_CONFIRMATION' => '',
                    'UF_CRM_COMPANY_AGR_ANIMAL_TYPE' => '',
                ];
                $isUpdateSuccess = $entityObject->Update($company['ID'], $entityFields);
            }
        }

        if ($arNoCompany) {
            $file = fopen($csvPath, 'w');
            fwrite($file, "\xEF\xBB\xBF");
            if ($file === false) {
                die(\Tanais\Alta\Log::save('StateBreedingRegister', 'Не удалось открыть файл для записи'));
            }
            foreach ($arNoCompany as $row) {
                foreach ($row as $key => $value) {
                    if (is_array($value)) {
                        $row[$key] = '';
                    }
                }
                fputcsv($file, $row, ';');
            }
            fclose($file);
        }
    }

    public static function getCompanyRequisite(): array
    {
        $entityRequisite = new \Bitrix\Crm\EntityRequisite;
        $arRequisite = [];
        $rsRequisite = $entityRequisite->getList([
            "select" => ["RQ_OGRN", "RQ_OGRNIP", "ENTITY_ID", "RQ_INN", "RQ_KPP"],
            "filter" => ["ENTITY_TYPE_ID" => 4],
            "order" => ["SORT" => "desc", "ID" => "desc"]
        ]);
        while ($requisite = $rsRequisite->Fetch()) {
            if (!empty($requisite['RQ_OGRNIP'])) {
                $ogrnip = preg_replace('/\D/', '', $requisite['RQ_OGRNIP']);
                $arRequisite[$ogrnip] = $requisite['ENTITY_ID'];
            } elseif (!empty($requisite['RQ_OGRN'])) {
                $ogrn = preg_replace('/\D/', '', $requisite['RQ_OGRN']);
                $arRequisite[$ogrn] = $requisite['ENTITY_ID'];
            } elseif (!empty($requisite['RQ_INN']) && !empty($requisite['RQ_KPP'])) {
                $inn = preg_replace('/\D/', '', $requisite['RQ_INN']);
                $kpp = preg_replace('/\D/', '', $requisite['RQ_KPP']);
                $arRequisite[$inn . '_' . $kpp] = $requisite['ENTITY_ID'];
            }
        }
        return $arRequisite;
    }

    public static function getBusinessType()
    {
        $entityTypeId = \Bitrix\Main\Config\Option::get('tanais.clientagr', "listIdBusiness");
        $ufPrefix = "UF_CRM_" . (\Bitrix\Crm\Model\Dynamic\TypeTable::getByEntityTypeId($entityTypeId)->fetch())["ID"] . "_";
        $typeId = '';

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
        $businessTypes = $factory->getItems([
            'select' => ['ID'],
            'filter' => [
                "{$ufPrefix}CODE" => '01.42',
            ],
        ]);
        foreach ($businessTypes as $businessType) {
            $typeId = $businessType->get('ID');
        }
        return $typeId;
    }

    public static function getCompaniesOldValues(): array
    {
        $currentValues = [];
        $companyResult = \Bitrix\Crm\CompanyTable::getList([
            'select' => [
                'ID',
                'UF_CRM_COMPANY_AGR_DATE_CONFIRMATION',
                'UF_CRM_COMPANY_AGR_ANIMAL_TYPE',
            ],
        ]);
        foreach ($companyResult as $company) {
            $currentValues[$company['ID']]['UF_CRM_COMPANY_AGR_DATE_CONFIRMATION'] = $company['UF_CRM_COMPANY_AGR_DATE_CONFIRMATION'];
            $currentValues[$company['ID']]['UF_CRM_COMPANY_AGR_ANIMAL_TYPE'] = $company['UF_CRM_COMPANY_AGR_ANIMAL_TYPE'];
            if ($company['UF_CRM_COMPANY_AGR_DATE_CONFIRMATION'] instanceof \Bitrix\Main\Type\Date) {
                $currentValues[$company['ID']]['UF_CRM_COMPANY_AGR_DATE_CONFIRMATION'] = $company['UF_CRM_COMPANY_AGR_DATE_CONFIRMATION']->format('d.m.Y');
            }
        }

        return $currentValues;
    }

    public static function getCompaniesBusinessTypes(): array
    {
        $currentValues = [];
        $companyResult = \Bitrix\Crm\CompanyTable::getList([
            'select' => [
                'ID',
                'UF_CRM_COMPANY_AGR_ACTIVITY_TYPE'
            ],
        ]);
        foreach ($companyResult as $company) {
            $currentValues[$company['ID']] = $company['UF_CRM_COMPANY_AGR_ACTIVITY_TYPE'];
        }

        return $currentValues;
    }
}