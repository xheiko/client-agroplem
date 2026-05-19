<?php

namespace Tanais\Alter;

use \Bitrix\Main\Error;
use \Bitrix\Main\UserTable;
use \Bitrix\Main\Engine\ActionFilter\Authentication;

\Bitrix\Main\Loader::includeModule("crm");

class AjaxController extends \Bitrix\Main\Engine\Controller
{
    public function bindAction(int $dealId, int $contractId): array
    {
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
        $item = $factory->getItem($dealId);
        $item->set("UF_CRM_CLIENT_CONTRACT", $contractId);
        $operation = $factory->getUpdateOperation($item);
        $operation
            ->disableAllChecks()
            ->disableBeforeSaveActions();
        $result = $operation->launch();
        return [
            'success' => true,
            'dealId' => $dealId,
            'contractId' => $contractId,
        ];

    }
}
