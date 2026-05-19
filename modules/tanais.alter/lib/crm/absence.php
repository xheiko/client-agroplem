<?php

namespace Tanais\Alter\Crm;
class Absence
{
    public static function createAbsence($elementId = null, $absenceType)
    {

        if ((empty($elementId)) or (intval($elementId) == 0)) {
            return false;
        }
        $container = \Bitrix\Crm\Service\Container::getInstance();
        $factory = $container->getFactory(\Tanais\Alter\Config::VISITS_ENTITY_ID);
        $elements = $factory->getItems([
            'select' => ['ID', 'TITLE', 'ASSIGNED_BY_ID', 'UF_CRM_25_VISIT_DATE'],
            'filter' => ['ID' => $elementId],
            'order' => ['ID' => 'ASC'],
        ]);

        foreach ($elements as $element) {
            $el = new \CIBlockElement;

            $fields = [
                "IBLOCK_ID" => 1,
                "NAME" => $element->getTitle(),
                "ACTIVE" => "Y",
                "ACTIVE_FROM" => $element->get('UF_CRM_25_VISIT_DATE'),
                "ACTIVE_TO" => $element->get('UF_CRM_25_VISIT_DATE'),
                "PROPERTY_VALUES" => [
                    4 => $absenceType,
                    1 => $element->get('ASSIGNED_BY_ID'),
                ],
            ];
        }

        if ($id = $el->Add($fields)) {
            return $id;
        }
        return false;
    }

    public
    static function updateAbsence($elementId = null, $absenceType)
    {

        if ((empty($elementId)) or (intval($elementId) == 0)) {
            return false;
        }

        $container = \Bitrix\Crm\Service\Container::getInstance();
        $factory = $container->getFactory(\Tanais\Alter\Config::VISITS_ENTITY_ID);
        $elements = $factory->getItems([
            'select' => ['ID', 'TITLE', 'ASSIGNED_BY_ID', 'UF_CRM_25_VISIT_DATE', 'UF_CRM_25_CALENDAR_ELEMENT_ID'],
            'filter' => ['ID' => $elementId],
            'order' => ['ID' => 'ASC'],
        ]);

        foreach ($elements as $element) {
            $el = new \CIBlockElement;

            $fields = [
                "NAME" => $element->getTitle(),
                "ACTIVE_FROM" => $element->get('UF_CRM_25_VISIT_DATE'),
                "ACTIVE_TO" => $element->get('UF_CRM_25_VISIT_DATE'),
                "PROPERTY_VALUES" => [
                    4 => $absenceType,
                    1 => $element->get('ASSIGNED_BY_ID'),
                ],
            ];

            if ($res = $el->Update($element->get('UF_CRM_25_CALENDAR_ELEMENT_ID'), $fields)) {
                return true;
            }
        }
        return false;
    }
}