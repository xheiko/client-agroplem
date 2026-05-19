<?php

namespace Tanais\Alter;

use Bitrix\Main\Loader;

Loader::includeModule('crm');


class TabManager
{

    public function __construct() {}

    public function getActualEntityTab(int $elementId, int $entityTypeID, array $tabs = []): array
    {
        if ($entityTypeID == \CCrmOwnerType::Company) {
            $tabs = $this->getActualCompanyTabs($elementId, $elementId, $tabs);
        }
        if ($entityTypeID == \CCrmOwnerType::Deal) {
            $tabs = $this->getActualDealTabs($elementId, $elementId, $tabs);
        }

        return $tabs;
    }

    private function getActualCompanyTabs(int $elementId, int $entityTypeID, array $tabs = []): array
    {
        $tabs[] = [
            'id' => 'companyLeads',
            'name' => 'Лиды',
            'enabled' => !empty($elementId),
            'loader' => [
                'serviceUrl' => '/local/modules/tanais.alter/public/crm_tabs/company/lead.php?companyId=' . $elementId,
                'componentData' => [
                    'template' => '',
                    'params' => [
                        'ENTITY_ID' => $entityID,
                        'ENTITY_TYPE' => $entityTypeID,
                    ]
                ]
            ]
        ];

        return $tabs;
    }

    private function getActualDealTabs(int $elementId, int $entityTypeID, array $tabs = []): array
    {
        $tabs[] = [
            'id' => 'dealChoosingContract',
            'name' => 'Выбор договора',
            'enabled' => !empty($elementId),
            'loader' => [
                'serviceUrl' => '/local/modules/tanais.alter/public/crm_tabs/deal/choosingcontract.php?dealId=' . $elementId,
                'componentData' => [
                    'template' => '',
                    'params' => [
                        'ENTITY_ID' => $entityID,
                        'ENTITY_TYPE' => $entityTypeID,
                    ]
                ]
            ]
        ];

        return $tabs;
    }

}
