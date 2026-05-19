<?php
//namespace Tanais\Alter\Service\SupplierAgreement\Operation\Action;
//
//use \Bitrix\Main,
//    \Bitrix\Crm\Item,
//    \Bitrix\Crm\Service,
//    \Bitrix\Main\Error,
//    \Bitrix\Crm\Service\Operation,
//    \Tanais\Alter\Helper;
//
//Main\Loader::requireModule('crm');
//
//class CheckPermissions extends Operation\Action
//{
//    public function process(Item $item): Main\Result
//    {
//        $result = new Main\Result();
//        $userId = $this->getContext()->getUserId();
//        $stageId = $item->getStageId();
//        $currentStage = $item->remindActual(Item::FIELD_NAME_STAGE_ID);
//        $propsUfCodes = Helper::getSmartContractSupplierUfCodes()[1];
//        \Bitrix\Main\Diag\Debug::dumpToFile($propsUfCodes["admins"], $varName = '', $fileName = '');
//        switch ($stageId) {
//            case $propsUfCodes["STAGES"][0]:
//                $stageIndex = 1;
//                $responsible = $item->get($propsUfCodes["jurist"]);
//                break;
//            case $propsUfCodes["STAGES"][1]:
//                $stageIndex = 2;
//                $responsible = $item->get($propsUfCodes["economist"]);
//                break;
//            case $propsUfCodes["STAGES"][2]:
//                $stageIndex = 3;
//                $responsible = $item->get($propsUfCodes["finance_director"]);
//                break;
//            case $propsUfCodes["STAGES"][3]:
//                $stageIndex = 4;
//                $responsible = $item->get($propsUfCodes["general_director"]);
//                break;
//            case $propsUfCodes["STAGES"][4]:
//                $stageIndex = 5;
//                $responsible = $item->get($propsUfCodes["accountant"]);
//                break;
//            case $propsUfCodes["STAGES"][5]:
//                $stageIndex = 6;
//                $responsible = $item->get($propsUfCodes["accountant_original"]);
//                break;
//        }
//
//        switch ($currentStage) {
//            case $propsUfCodes["STAGES"][0]:
//                $currentStageIndex = 1;
//                break;
//            case $propsUfCodes["STAGES"][1]:
//                $currentStageIndex = 2;
//                break;
//            case $propsUfCodes["STAGES"][2]:
//                $currentStageIndex = 3;
//                break;
//            case $propsUfCodes["STAGES"][3]:
//                $currentStageIndex = 4;
//                break;
//            case $propsUfCodes["STAGES"][4]:
//                $currentStageIndex = 5;
//                break;
//            case $propsUfCodes["STAGES"][5]:
//                $currentStageIndex = 6;
//                break;
//        }
//
//        if ($stageIndex < $currentStageIndex) {
//            \CModule::IncludeModule('bizproc');
//
//            $res = \CBPDocument::StartWorkflow(
//                $propsUfCodes['NOTIFICATION_BP'],
//                ['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic', 'DYNAMIC_' . ENTITY_TYPE_ID . '_' . $item->getId() . ''],
//                ['to' => 'user_' . $responsible . ''],
//                $arErrorsTmp
//            );
//        }
//        //todo: попробовать улучшить код
//        if ($stageIndex === $currentStageIndex) {
//            $requiredGroup = $propsUfCodes["STAGES_PERMS"][$stageId];
//        } else {
//            $requiredGroup = $propsUfCodes["STAGES_PERMS_SECOND"][$stageId];
//        }
//
//        if ($requiredGroup && !in_array(1, \CUser::GetUserGroup($userId))) {
//            $ufValues = $item->get($propsUfCodes[$requiredGroup]);
//
//            if (!is_array($ufValues)) {
//                $ufValues = [$ufValues];
//            }
//
//            if (
//                !in_array($userId, $ufValues)
//                &&
//                !in_array($userId, $item->get($propsUfCodes["admins"]))
//            ) {
//                $result->addError(
//                    new Error('No rights! required: ' . $requiredGroup)
//                );
//            }
//        }
//
//        return $result;
//    }
//}


namespace Tanais\Alter\Service\SupplyContract\Operation\Action;

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
        $errorLogFilename = \Bitrix\Main\Application::getDocumentRoot() . "/local/log/tanais.alter/supplyContract.error.log";

        $result = new Main\Result();

//        if (!$item->isChangedStageId()) {
//            return $result;
//        }
//
//        $userId = $this->getContext()->getUserId();
//        $newStageId = $item->getStageId();
//        $currentStageId = $item->remindActual(Item::FIELD_NAME_STAGE_ID);
//        $categoryId = $item->get('CATEGORY_ID');
//        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($item->getEntityTypeId());
//
//        $stageList = $factory->getStages($categoryId)->getAll();
//        $stages = [];
//        foreach ($stageList as $stage) {
//            $stages[] = $stage->get('STATUS_ID');
//            $stagesName[$stage->get('STATUS_ID')] = $stage->get('NAME');
//        }
//        $currentStageIndex = array_search($currentStageId, $stages);
//        $newStageIndex = array_search($newStageId, $stages);
//
//        $constants = \Tanais\Alter\Crm\Constant::get($item->getEntityTypeId());
//
//        //Получаем Администраторов процесса. В описании константы должно быть слово ADMINSTRATOR
//        $admins = [];
//        foreach ($constants as $constant)
//            if (str_contains($constant['DESCRIPTION'], 'ADMINSTRATOR'))
//                $admins = $constant['PROPERTY_VALUE'];
//        if (!is_array($admins)) $admins = [$admins];
//
//        //Администраторы работают без ограничений
//        if (in_array($userId, $admins))
//            return $result;
//
//        //Cтадии можно двигать назад без ограничений
//        if ($newStageIndex <= $currentStageIndex)
//            return $result;
//
//        //Запрещаем пропуск стадии
//        if ((!str_contains(':FAIL', $newStageId)) && ($newStageIndex - $currentStageIndex) >= 2) {
//            $errorMessage = 'Запрещено пропускать стадии.';
//            $result->addError(new Error($errorMessage));
//        }
//
//        //Перерабатываем данные констант в список пользователей, которым разрешен выход. Если разрешен всем, то массив пустой
//        $allowedUser = [];
//        foreach ($constants as $constant) {
//            if (str_contains($constant['DESCRIPTION'], $currentStageId)) {
//                if (($constant['PROPERTY_TYPE'] == 'string') && ($constant['IS_MULTIPLE'] == 'N')) {
//                    if ($item->hasField($constant['PROPERTY_VALUE']))
//                        $allowedUser = $item->get($constant['PROPERTY_VALUE']);
//                    else {
//                        $errorMessage = 'Ошибка конфигурации. Указанное в константе "' . $constant['NAME'] . '" поле не существует';
//                        $result->addError(new Error($errorMessage));
//                    }
//                }
//                if (($constant['PROPERTY_TYPE'] == 'user'))
//                    $allowedUser = $constant['PROPERTY_VALUE'];
//                if (!is_array($allowedUser))
//                    $allowedUser = [$allowedUser];
//            }
//        }
//
//        //Проверяем есть ли текущего пользователя разрешение на выход из стадии
//        if ($allowedUser)
//            if (!in_array($userId, $allowedUser)) {
//                $errorMessage = 'Изменение стадии с "' . $stagesName[$currentStageId] . '" доступно только: ' . \Tanais\Alter\User::getUserNames($allowedUser) . ".";
//                $result->addError(new Error($errorMessage));
//            }
//
//        //Логгирование запретов
//        if ($result->getErrorMessages()) {
//            file_put_contents($errorLogFilename, date("d.m.Y H:i:s") . '  [' . \Bitrix\Main\Engine\CurrentUser::get()->getId() . ']' . \Bitrix\Main\Engine\CurrentUser::get()->getFormattedName() . "\r\n", FILE_APPEND);
//            file_put_contents($errorLogFilename, $item->getId() . ' ' . $item->getTitle() . "\r\n", FILE_APPEND);
//            file_put_contents($errorLogFilename, '[' . $currentStageId . '] ' . $stagesName[$currentStageId] . ' -> [' . $newStageId . '] ' . $stagesName[$newStageId] . "\r\n", FILE_APPEND);
//            file_put_contents($errorLogFilename, var_export($result->getErrorMessages(), true) . "\r\n\r\n", FILE_APPEND);
//        }

        return $result;
    }
}