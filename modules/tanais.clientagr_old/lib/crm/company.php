<?php

namespace Tanais\ClientAGR\Crm;

\Bitrix\Main\Loader::includeModule('crm');

class Company
{
    public static function updateABCInfo(): void
    {
        $companyData = \Bitrix\Crm\CompanyTable::getList([
            'select' => ['ID', 'REVENUE'],
            'filter' => ['!REVENUE' => 0]
        ]);
        $arCompany = [];
        while ($company = $companyData->fetch()) {
            $arCompany[$company['ID']] = $company['REVENUE'];
        }
        if (empty($arCompany)) {
            return;
        }
        $abcGroups = self::abcAnalyze($arCompany);

        foreach ($abcGroups as $companyId => $group) {
            $fieldValue = ['A' => 'UF_CRM_COMPANY_AGR_A_CLIENT', 'B' => 'UF_CRM_COMPANY_AGR_B_CLIENT', 'C' => 'UF_CRM_COMPANY_AGR_C_CLIENT'][$group];
            $entityFields = [$fieldValue => 1];
            $entityObject = new \CCrmCompany(false);
            $isUpdateSuccess = $entityObject->Update($companyId, $entityFields, true, true, $arOptions = []);

        }
    }

    public static function abcAnalyze(array $items): array
    {
        // Сортируем по убыванию значений
        arsort($items);

        $total = array_sum($items);
        $currentPercent = 0;
        $result = [];

        foreach ($items as $id => $value) {
            $percent = ($value / $total) * 100;
            $currentPercent += $percent;

            if ($currentPercent <= 80.0) {
                $result[$id] = 'A';
            } elseif ($currentPercent <= 95.0) {
                $result[$id] = 'B';
            } else {
                $result[$id] = 'C';
            }
        }

        return $result;
    }

}

