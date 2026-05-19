<?php

namespace Tanais\Alter;

//В этом классе только то, что недостойно собственного класса

class Helper
{
    const  MODULE_ID = 'tanais.alter';

    public static function getSmartContractSupplierUfCodes(): array
    {
        $propsList = [
            [
                'ENTITY_TYPE_ID' => 1046,
                'NOTIFICATION_BP' => 19,
                'DEPARTMENT_ID' => "UF_CRM_5_DEPARTMENT",
                'admins' => "UF_CRM_5_1660542693",
                "economist" => "UF_CRM_5_1649764957",
                "finance_director" => "UF_CRM_5_1649765042",
                "general_director" => "UF_CRM_5_1649765060",
                "accountant" => "UF_CRM_5_1657874839",
                "accountant_original" => "UF_CRM_5_1650364954",
                'STAGES' => [
                    "DT1046_18:NEW",
                    "DT1046_18:PREPARATION",
                    "DT1046_18:CLIENT",
                    "DT1046_18:4",
                    "DT1046_18:1",
                    "DT1046_18:2",
                    "DT1046_18:3",
                ],
                'STAGE_PERMISSIONS_LAB' => [
                    "DT1046_18:CLIENT",
                    "DT1046_18:4",
                    "DT1046_18:1",
                    "DT1046_18:2",
                    "DT1046_18:3",
                ],
            ],
            [
                'ENTITY_TYPE_ID' => 1074,
                'NOTIFICATION_BP' => 211,
                'admins' => "UF_CRM_12_1660808455",
                "economist" => "UF_CRM_12_1645104426",
                "finance_director" => "UF_CRM_12_1645104453",
                "general_director" => "UF_CRM_12_1645104478",
                "accountant" => "UF_CRM_12_1645104530",
                "accountant_original" => "UF_CRM_12_1645104530",
                'STAGES' => [
                    "DT1074_32:PREPARATION",
                    "DT1074_32:CLIENT",
                    "DT1074_32:UC_IY1P0K",
                    "DT1074_32:UC_2IO5H6",
                    "DT1074_32:UC_T4ELXQ",
                    "DT1074_32:UC_E95SKO",
                    "DT1074_32:UC_E0LH3N",
                ],
                'STAGES_PERMS' => [
                    "DT1074_32:PREPARATION" => 'jurist',
                    "DT1074_32:CLIENT" => 'economist',
                    "DT1074_32:UC_IY1P0K" => 'finance_director',
                    "DT1074_32:UC_2IO5H6" => 'general_director',
                    "DT1074_32:UC_T4ELXQ" => 'accountant',
                    "DT1074_32:UC_E95SKO" => 'accountant_original',
                    "DT1074_32:UC_E0LH3N" => 'accountant_original',
                ],
                'STAGES_PERMS_SECOND' => [
                    "DT1074_32:CLIENT" => 'jurist',//jurist
                    "DT1074_32:UC_IY1P0K" => 'economist',//economist
                    "DT1074_32:UC_2IO5H6" => 'finance_director',//finance_director
                    "DT1074_32:UC_T4ELXQ" => 'general_director',//general_director
                    "DT1074_32:UC_E95SKO" => 'accountant',//accountant
                    "DT1074_32:UC_E0LH3N" => 'accountant_original',//accountant_original
                ],
            ],
        ];
        return $propsList;
    }

    public static function getInvoiceStageIndex($stageId, $currentStage)
    {
        $propsUfCodes = self::getSmartContractSupplierUfCodes()[0];
        switch ($stageId) {
            case $propsUfCodes["STAGES"][0]:
                $stageIndex = 1;
                $responsible = $propsUfCodes["jurist"];
                break;
            case $propsUfCodes["STAGES"][1]:
                $stageIndex = 2;
                $responsible = 'ASSIGNED_BY_ID';
                break;
            case $propsUfCodes["STAGES"][2]:
                $stageIndex = 3;
                $responsible = $propsUfCodes["economist"];
                break;
            case $propsUfCodes["STAGES"][3]:
                $stageIndex = 4;
                $responsible = $propsUfCodes["general_director"];
                break;
            case $propsUfCodes["STAGES"][4]:
                $stageIndex = 5;
                $responsible = $propsUfCodes["finance_director"];
                break;
            case $propsUfCodes["STAGES"][5]:
                $stageIndex = 6;
                $responsible = $propsUfCodes["accountant"];
                break;
            case $propsUfCodes["STAGES"][6]:
                $stageIndex = 7;
                $responsible = $propsUfCodes["accountant_original"];
                break;
        }

        switch ($currentStage) {
            case $propsUfCodes["STAGES"][0]:
                $currentStageIndex = 1;
                break;
            case $propsUfCodes["STAGES"][1]:
                $currentStageIndex = 2;
                break;
            case $propsUfCodes["STAGES"][2]:
                $currentStageIndex = 3;
                break;
            case $propsUfCodes["STAGES"][3]:
                $currentStageIndex = 4;
                break;
            case $propsUfCodes["STAGES"][4]:
                $currentStageIndex = 5;
                break;
            case $propsUfCodes["STAGES"][5]:
                $currentStageIndex = 6;
                break;
            case $propsUfCodes["STAGES"][6]:
                $currentStageIndex = 7;
                break;
        }

        return [
            'from_stage_index' => $stageIndex,
            'responsible' => $responsible,
            'to_stage_index' => $currentStageIndex
        ];
    }

    public static function getLink($elementTypeName, $elementId): bool|string
    {
        if (empty($elementTypeName)) {
            return false;
        }
        if ($elementTypeName == 'D') {
            $href = '/crm/deal/details/' . $elementId . '/';
        } elseif ($elementTypeName == 'L') {
            $href = '/crm/lead/details/' . $elementId . '/';
        } else {
            $href = '/crm/type/' . \CCrmOwnerType::ResolveID($elementTypeName) . '/details/' . $elementId . '/';
        }
        return $href;
    }

    public static function getCrmRegion(): array
    {
        $regions = [];
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\Tanais\Alter\Config::REGION_ENTITY_ID);
        $arRegions = $factory->getItems([
            'select' => [
                'ID',
                'TITLE',
            ],
            'order' => ['ID' => 'ASC'],
        ]);
        foreach ($arRegions as $arRegion) {
            $regions[$arRegion->get('ID')] = $arRegion->get('TITLE');
        }
        return $regions;
    }
}