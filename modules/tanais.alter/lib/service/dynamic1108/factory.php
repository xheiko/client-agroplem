<?php

namespace Tanais\Alter\Service\Dynamic1108;

use \Bitrix\Main,
    \Bitrix\Crm,
    \Bitrix\Crm\Service\Factory\Dynamic;

Main\Loader::requireModule('crm');

class Factory extends Dynamic
{
    public function getUpdateOperation(Crm\Item $item, Crm\Service\Context $context = null): Crm\Service\Operation\Update
    {
        $operation = parent::getUpdateOperation($item, $context);

        return $operation->addAction(
            Crm\Service\Operation::ACTION_BEFORE_SAVE,
            new Operation\Action\CheckField
        );

    }

    public function getAddOperation(Crm\Item $item, Crm\Service\Context $context = null): Crm\Service\Operation\Add
    {
        $operation = parent::getAddOperation($item, $context);

        return $operation
            ->addAction(
                Crm\Service\Operation::ACTION_BEFORE_SAVE,
                new Operation\Action\CheckField
            );

    }
}