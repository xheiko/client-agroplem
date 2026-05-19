<?

namespace Tanais\Alter\Crm;

class StateBreedingRegister
{
    public static function updateCompanyBreedingRegister(): void
    {
        $urlRegister = 'http://opendata.mcx.ru/opendata/7708075454-plemennoyregistr/meta.xml';
        $xmlContent = file_get_contents($urlRegister);
        if (!$xmlContent) {
            die(\Tanais\Alter\Log::save('StateBreedingRegister', "Не удалось загрузить XML по адресу: $urlRegister"));
        }
        $xml = new \SimpleXMLElement($xmlContent);
        $dataversions = $xml->xpath('//dataversion');
        if (!$dataversions) {
            die(\Tanais\Alter\Log::save('StateBreedingRegister', "В файле нет <dataversion>"));
        }
        $lastDataversion = end($dataversions);
        $lastLink = (string)$lastDataversion->source;

        $urlStateBreeding = $lastLink;
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/upload/tanais.alter/state_breeding_register/';
        $archivePath = $uploadDir . 'stateBreedingRegister.zip';
        $xmlPath = $uploadDir . 'stateBreedingRegister.xml';
        $csvPath = $uploadDir . 'stateBreedingRegister.csv';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        file_put_contents($archivePath, fopen($urlStateBreeding, 'r'));

        if (!file_exists($archivePath)) {
            die(\Tanais\Alter\Log::save('StateBreedingRegister', 'Не удалось скачать файл.'));
        }
        $zip = new \ZipArchive;
        if ($zip->open($archivePath) === TRUE) {
            $zip->extractTo($uploadDir);
            $zip->close();
        } else {
            die(\Tanais\Alter\Log::save('StateBreedingRegister', 'Не удалось разархивировать файл.'));
        }

        $extractedFiles = glob($uploadDir . '*.xml');
        if (!empty($extractedFiles)) {
            rename($extractedFiles[0], $xmlPath);
        } else {
            die(\Tanais\Alter\Log::save('StateBreedingRegister', 'Не удалось найти разархивированный XML файл.'));
        }
        unlink($archivePath);

        $requisite = \Tanais\Alter\Crm\Company::getCompanyRequisite();

        $xmlfile = file_get_contents($xmlPath);
        $new = simplexml_load_string($xmlfile);
        $con = json_encode($new);
        $newArr = json_decode($con, true);
        $dateNow = date('d.m.Y');


        $entityObject = new \CCrmCompany();
        $arCompanyUpdate = [];
        $arNoCompany[] = ['ID', 'Номер свидетельства', 'Приказ создания', 'Дата приказа создания', 'УИН', 'Регион', 'Отрасль', 'Регистрант', 'ОГРН', 'ИНН', 'КПП', 'Вид организации', 'Вид животного', 'Порода', 'Адрес организации'];

        foreach ($newArr["plemennoy_registr"] as $value) {

            if ($requisite[$value['ogrn']]) {
                $companyId = $requisite[$value['ogrn']];

                $entityFields = [
                    'UF_CRM_NUMBER_TRIBAL_REGISTER' => $value['nomer_svidetelstva'],
                    'UF_CRM_REGISTRANTNUMBER_TRIBAL_REGISTER' => $value['registrant'],
                    'UF_CRM_REGION_TRIBAL_REGISTER' => $value['region'],
                    'UF_CRM_SECTOR_TRIBAL_REGISTER' => $value['otrasl'],
                    'UF_CRM_ORGANIZATION_TYPE_TRIBAL_REGISTER' => $value['vid_organizatsii'],
                    'UF_CRM_ANIMAL_TYPE_TRIBAL_REGISTER' => $value['vid_zhivotnogo'],
                    'UF_CRM_BREED_TRIBAL_REGISTER' => $value['poroda'],
                    'UF_CRM_ADDRESS_TRIBAL_REGISTER' => $value['adres_organizatsii'],
                    'UF_CRM_PLEMREGISTER_UPDATE' => $dateNow,
                ];
                $isUpdateSuccess = $entityObject->Update($companyId, $entityFields);
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
            'filter' => ['!UF_CRM_REGION_TRIBAL_REGISTER' => null, '!UF_CRM_REGISTRANTNUMBER_TRIBAL_REGISTER' => null],
        ]);
        foreach ($companyResult as $company) {
            if (!in_array($company['ID'], $arCompanyUpdate)) {
                $entityFields = [
                    'UF_CRM_NUMBER_TRIBAL_REGISTER' => '',
                    'UF_CRM_REGISTRANTNUMBER_TRIBAL_REGISTER' => '',
                    'UF_CRM_REGION_TRIBAL_REGISTER' => '',
                    'UF_CRM_SECTOR_TRIBAL_REGISTER' => '',
                    'UF_CRM_ORGANIZATION_TYPE_TRIBAL_REGISTER' => '',
                    'UF_CRM_ANIMAL_TYPE_TRIBAL_REGISTER' => '',
                    'UF_CRM_BREED_TRIBAL_REGISTER' => '',
                    'UF_CRM_ADDRESS_TRIBAL_REGISTER' => '',
                    'UF_CRM_PLEMREGISTER_UPDATE' => $dateNow,
                ];
                $isUpdateSuccess = $entityObject->Update($company['ID'], $entityFields);
            }
        }

        if ($arNoCompany) {
            $file = fopen($csvPath, 'w');
            fwrite($file, "\xEF\xBB\xBF");
            if ($file === false) {
                die(\Tanais\Alter\Log::save('StateBreedingRegister', 'Не удалось открыть файл для записи'));
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
}