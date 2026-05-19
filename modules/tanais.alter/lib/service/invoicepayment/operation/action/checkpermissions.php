<?php
//
//namespace Tanais\Alter\Service\InvoicePayment\Operation\Action;
//
//use \Bitrix\Main,
//    \Bitrix\Crm\Item,
//    \Bitrix\Crm\Service,
//    \Bitrix\Main\Error,
//    \Bitrix\Crm\Service\Operation,
//    \Tanais\Alter\Helper;
//
//Main\Loader::requireModule('crm');
//define('ENTITY_TYPE_ID', 1046);
//
//class CheckPermissions extends Operation\Action
//{
//    public function process(Item $item): Main\Result
//    {
//        $result = new Main\Result();
//
//        if (!$item->isChangedStageId()) {
//            return $result;
//        }
//
//        $userId = $this->getContext()->getUserId();
//        $stageId = $item->getStageId();
//        $currentStage = $item->remindActual(Item::FIELD_NAME_STAGE_ID);
//        $propsUfCodes =  Helper::getSmartContractSupplierUfCodes()[0];
//        $departmentId = $item->get($propsUfCodes['DEPARTMENT_ID']);
//        $departmentName = current(\CIntranetUtils::GetDepartmentsData([$departmentId]));
//        $departmentNameFirstWord = explode(' ', $departmentName);
//        $departmentInfo = \CIntranetUtils::GetDepartmentManager([$departmentId], false, true);
//        $headPeople = (int)current($departmentInfo)['ID'];
//        $adminDepartment = \CIntranetUtils::GetIBlockTopSection($departmentId);
//        $adminDepartmentInfo = \CIntranetUtils::GetDepartmentManager([$adminDepartment], false, true);
//        $adminHeadPeople = (int)current($adminDepartmentInfo)['ID'];
//
//        $invoiceIndex =  Helper::getInvoiceStageIndex($stageId, $currentStage);
//        $responsible = !empty($invoiceIndex['responsible']) ? $item->get($invoiceIndex['responsible']) : false;
//        $stageFourPermission = [$item->get($propsUfCodes["general_director"]), $item->get($propsUfCodes["economist"])];
//
//        if ($stageId == $propsUfCodes["STAGES"][0] && $userId) {
//            $item->set('ASSIGNED_BY_ID', $item->get('CREATED_BY'));
//        } elseif ($stageId == $propsUfCodes["STAGES"][1] && $departmentNameFirstWord[0] === "Лаборатория") {
//            $item->set('ASSIGNED_BY_ID', $headPeople);
//            $observers = $item->getObservers();
//            array_push($observers, $headPeople);
//            array_push($observers, $adminHeadPeople);
//            $item->setObservers($observers);
//        } elseif ($stageId == $propsUfCodes["STAGES"][1] && $departmentNameFirstWord[0] !== "Лаборатория") {
//            $result->addError(new Error('Переводите сразу на стадию Экономиста'));
//        } elseif ($currentStage == $propsUfCodes["STAGES"][0] && $departmentNameFirstWord[0] === "Лаборатория" && in_array($stageId, $propsUfCodes["STAGE_PERMISSIONS_LAB"])) {
//            $result->addError(new Error('Необходимо перевести на Руководителя Лаборатории'));
//        }
//        // $userId = 4;
//
//        if ($invoiceIndex['from_stage_index'] > $invoiceIndex['to_stage_index']) {
//            if ($stageId == $propsUfCodes["STAGES"][2] && $departmentNameFirstWord[0] === "Лаборатория" && $userId !== $adminHeadPeople && $userId !== $item->get('ASSIGNED_BY_ID') && !in_array($userId, $item->get($propsUfCodes["admins"]))) {
//                $result->addError(new Error('Нет прав ' . var_export([$this->getContext()->getUserId(), $userId, '!', $item->get($propsUfCodes["admins"]),], true)));
//            } elseif ($stageId == $propsUfCodes["STAGES"][3] && $userId !== $item->get($propsUfCodes["economist"]) && !in_array($userId, $item->get($propsUfCodes["admins"]))) {
//                $result->addError(new Error('Доступно только Экономисту'));
//            } elseif ($stageId == $propsUfCodes["STAGES"][4] && !in_array($userId, $stageFourPermission) && !in_array($userId, $item->get($propsUfCodes["admins"]))) {
//                $result->addError(new Error('Доступно только Ген. директору или Экономисту'));
//            } elseif ($stageId == $propsUfCodes["STAGES"][5] && $userId !== $item->get($propsUfCodes["accountant"]) && !in_array($userId, $item->get($propsUfCodes["admins"]))) {
//                $result->addError(new Error('Доступно только Бухгалтеру'));
//            } elseif ($stageId == $propsUfCodes["STAGES"][6] && $userId !== $item->get($propsUfCodes["accountant"]) && !in_array($userId, $item->get($propsUfCodes["admins"]))) {
//                $result->addError(new Error('Доступно только Бухгалтеру 2'));
//            }
//        }
//
//        if ($invoiceIndex['from_stage_index'] < $invoiceIndex['to_stage_index'] && !empty($responsible) && $result->isSuccess()) {
//            \CModule::IncludeModule('bizproc');
//
//            $res = \CBPDocument::StartWorkflow(
//                $propsUfCodes['NOTIFICATION_BP'],
//                ['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic', 'DYNAMIC_' . ENTITY_TYPE_ID . '_' . $item->getId() . ''],
//                ['to' => 'user_' . $responsible . ''],
//                $arErrorsTmp
//            );
//        }
//        return $result;
//    }
//}


namespace Tanais\Alter\Service\InvoicePayment\Operation\Action;

use \Bitrix\Main,
    \Bitrix\Crm\Item,
    \Bitrix\Crm\Service,
    \Bitrix\Main\Error,
    \Tanais\Alter\Helper,
    \Bitrix\Crm\Service\Operation;

Main\Loader::requireModule('crm');

class CheckPermissions extends Operation\Action
{

    public function process(Item $item): Main\Result
    {
        $errorLogFilename = \Bitrix\Main\Application::getDocumentRoot() . "/local/log/tanais.alter/invoice.error.log";

        $result = new Main\Result();

        if (!$item->isChangedStageId()) {
            return $result;
        }

        $constants = \Tanais\Alter\Crm\Constant::get($item->getEntityTypeId());

        $departmentId = $item->get('UF_CRM_5_DEPARTMENT');
        $departmentName = current(\CIntranetUtils::GetDepartmentsData([$departmentId]));
        $departmentNameFirstWord = explode(' ', $departmentName);
        $departmentInfo = \CIntranetUtils::GetDepartmentManager([$departmentId], false, true);
        $headPeople = (int)current($departmentInfo)['ID'];

        if (empty($item->get('UF_CRM_5_1657874814')) && $departmentNameFirstWord[0] === "Лаборатория") {
            $item->set('UF_CRM_5_1657874814', $headPeople);
            $observers = $item->getObservers();
            array_push($observers, $headPeople);
            $item->setObservers($observers);
        }

        $userId = $this->getContext()->getUserId();
        $newStageId = $item->getStageId();
        $currentStageId = $item->remindActual(Item::FIELD_NAME_STAGE_ID);
        $categoryId = $item->get('CATEGORY_ID');
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($item->getEntityTypeId());

        $stageList = $factory->getStages($categoryId)->getAll();
        $stages = [];
        foreach ($stageList as $stage) {
            $stages[] = $stage->get('STATUS_ID');
            $stagesName[$stage->get('STATUS_ID')] = $stage->get('NAME');
        }
        $currentStageIndex = array_search($currentStageId, $stages);
        $newStageIndex = array_search($newStageId, $stages);

        //Получаем Администраторов процесса. В описании константы должно быть слово ADMINSTRATOR
        $admins = [];
        foreach ($constants as $constant)
            if (str_contains($constant['DESCRIPTION'], 'ADMINSTRATOR'))
                $admins = $constant['PROPERTY_VALUE'];
        if (!is_array($admins)) $admins = [$admins];

        //Администраторы работают без ограничений
        if (in_array($userId, $admins))
            return $result;

        //Cтадии назад можно двигать без ограничений
        if ($newStageIndex <= $currentStageIndex)
            return $result;

        //Запрещаем пропуск стадии
        if ((!str_contains(':FAIL', $newStageId)) && ($newStageIndex - $currentStageIndex) >= 2) {
            $errorMessage = 'Запрещено пропускать стадии.';
            $result->addError(new Error($errorMessage));
        }

        //Перерабатываем данные констант в список пользователей, которым разрешен выход. Если разрешен всем, то массив пустой
        $allowedUser = [];
        foreach ($constants as $constant) {
            if (str_contains($constant['DESCRIPTION'], $currentStageId)) {
                if (($constant['PROPERTY_TYPE'] == 'string') && ($constant['IS_MULTIPLE'] == 'N')) {
                    if ($item->hasField($constant['PROPERTY_VALUE']))
                        $allowedUser = $item->get($constant['PROPERTY_VALUE']);
                    else {
                        $errorMessage = 'Ошибка конфигурации. Указанное в константе "' . $constant['NAME'] . '" поле не существует';
                        $result->addError(new Error($errorMessage));
                    }
                }
                if (($constant['PROPERTY_TYPE'] == 'user'))
                    $allowedUser = $constant['PROPERTY_VALUE'];
                if (!is_array($allowedUser))
                    $allowedUser = [$allowedUser];
            }
        }

        //Проверяем есть ли текущего пользователя разрешение на выход из стадии
        if ($allowedUser)
            if (!in_array($userId, $allowedUser)) {
                $errorMessage = 'Изменение стадии с "' . $stagesName[$currentStageId] . '" доступно только: ' . \Tanais\Alter\User::getUserNames($allowedUser) . ".";
                $result->addError(new Error($errorMessage));
            }

        //Логгирование запретов
        if ($result->getErrorMessages()) {
            file_put_contents($errorLogFilename, date("d.m.Y H:i:s") . '  [' . \Bitrix\Main\Engine\CurrentUser::get()->getId() . ']' . \Bitrix\Main\Engine\CurrentUser::get()->getFormattedName() . "\r\n", FILE_APPEND);
            file_put_contents($errorLogFilename, $item->getId() . ' ' . $item->getTitle() . "\r\n", FILE_APPEND);
            file_put_contents($errorLogFilename, '[' . $currentStageId . '] ' . $stagesName[$currentStageId] . ' -> [' . $newStageId . '] ' . $stagesName[$newStageId] . "\r\n", FILE_APPEND);
            file_put_contents($errorLogFilename, var_export($result->getErrorMessages(), true) . "\r\n\r\n", FILE_APPEND);
        }

        return $result;
    }
}

