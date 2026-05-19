<?

namespace Tanais\Alter\Controller\Crm;

use \Bitrix\Main\Error;
use \Bitrix\Main\UserTable;

\Bitrix\Main\Loader::includeModule('crm');

class VisitCalendar extends \Bitrix\Main\Engine\Controller
{
    public function configureActions()
    {
        return [
            'updateVisitDate' => [
                'prefilters' => []
            ]
        ];
    }

    public function getDataAction($companyId)
    {
    }

    public function updateVisitDateAction($visitId, $propCodeBegin, $dateStart)
    {
        if (\Bitrix\Main\Loader::includeModule('crm')) {
            $userId = (int)$this->getCurrentUser()->getId();

            $container = \Bitrix\Crm\Service\Container::getInstance();
            $factory = $container->getFactory(\Tanais\Alter\Config::VISITS_ENTITY_ID);

            if (!$factory) {
                $this->addError(new Error('Factory not found'));
                return null;
            }

            $item = $factory->getItem((int)$visitId);
            if (!$item) {
                $this->addError(new Error('Item not found'));
                return null;
            }

            $context = new \Bitrix\Crm\Service\Context();
            $context->setUserId($userId);

            // Проверка прав на обновление
            $check = $factory->getUpdateOperation($item, $context)->checkAccess();
            if (!$check->isSuccess()) {
                $this->addError(new Error('Access denied'));
                return null;
            }

            $dateStart = new \DateTime($dateStart);
            $dateStart = $dateStart->format('d.m.Y');
            $item->set($propCodeBegin, $dateStart);

            $save = $factory->getUpdateOperation($item, $context)->launch();
            if (!$save->isSuccess()) {
                foreach ($save->getErrors() as $e) {
                    $this->addError(new Error($e->getMessage()));
                }
                return null;
            }

            \CModule::IncludeModule('bizproc');

//            $res = \CBPDocument::StartWorkflow(
//                101,//id бп
//                ['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic', 'DYNAMIC_1040_' . $elementId . ''],// id сущности
//                [],
//                $arErrorsTmp
//            );

            return [
                'success' => true,
                'id' => $item->getId(),
                'dateStart' => $dateStart,
            ];
        }
        return [
            'success' => false,
        ];
    }
}
