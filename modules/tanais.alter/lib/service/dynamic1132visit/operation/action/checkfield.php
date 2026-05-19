<?php

namespace Tanais\Alter\Service\Dynamic1132visit\Operation\Action;

use \Bitrix\Main,
    \Bitrix\Crm\Item,
    \Bitrix\Crm\Service,
    \Bitrix\Main\Error,
    \Tanais\Alta\Helper,
    \Bitrix\Crm\Service\Operation;

Main\Loader::requireModule('crm');

class CheckField extends Operation\Action
{
    public function process(Item $item): Main\Result
    {
        $result = new Main\Result();

        $categoryId = $item->get('CATEGORY_ID');
        if ($categoryId == 47) {

            $companyId = $item->get('COMPANY_ID');
            $contactId = $item->get('CONTACT_ID');
            if (!empty($companyId)) {
                $filter['COMPANY_ID'] = $companyId;
            } elseif (!$companyId && !empty($contactId)) {
                $filter['CONTACT_ID'] = $contactId;
            } else {
                return $result;
            }

            $filter['!ID'] = $item->getId();
            $filter['<ID'] = $item->getId();

            $container = Service\Container::getInstance();
            $factory = $container->getFactory(\Tanais\Alter\Config::VISITS_ENTITY_ID);

            $elements = $factory->getItems([
                'order' => ['ID' => 'DESC'],
                'select' => ['ID'],
                'filter' => $filter,
                'limit' => 1,
            ]);

            foreach ($elements as $element) {
                $previousElementId = $element->getId();
                if (!empty($previousElementId)) {
                    $item->set('UF_CRM_25_PREVIOUS_SESSION', $previousElementId);
                }
            }
        }

        return $result;
    }
}
