<?php

namespace Tanais\Alter\Service\Dynamic1112\Operation\Action;

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

        $container = Service\Container::getInstance();

        $factory = $container->getFactory(\Tanais\Alter\Service\Container::HOLDING_ENTITY_ID);

        $code = $item->get('UF_CRM_20_CODE');

        $elements = $factory->getItems([
            'order' => ['TITLE' => 'ASC'],
            'select' => ['ID', 'UF_CRM_20_CODE'],
            'filter' => [
                '!ID' => $item->getId(),
                'UF_CRM_20_CODE' => $code,
            ],
        ]);

        foreach ($elements as $element) {
            $duplicateId = $element->getId();
            $errorMessage = 'У элемента ' . $duplicateId . ' уже есть такой код ';
            $result->addError(new Error($errorMessage));
        }

        return $result;
    }
}
