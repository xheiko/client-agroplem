<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class CBPGetDeputyByDepartment extends \Bitrix\Bizproc\Activity\BaseActivity
{
    protected static $requiredModules = ["humanresources"];

    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'DepartmentId' => null,
            'DeputyUser' => null,
        ];
    }

    protected static function getFileName(): string
    {
        return __FILE__;
    }

    protected function internalExecute(): \Bitrix\Main\ErrorCollection
    {
        $errors = parent::internalExecute();

        try {
            $departmentId = (int)$this->DepartmentId;

            if ($departmentId <= 0) {
                $this->writeToTrackingService(
                    'Не указан ID подразделения',
                    0,
                    \CBPTrackingType::Error
                );

                $this->DeputyUser = null;

                return $errors;
            }

            $nodeId = $this->getNodeIdByDepartmentId($departmentId);

            if (!$nodeId) {
                $this->writeToTrackingService(
                    'Не найден HR nodeId для подразделения D' . $departmentId,
                    0,
                    \CBPTrackingType::Error
                );

                $this->DeputyUser = null;

                return $errors;
            }

            $deputyUserId = $this->getDeputyUserIdByNodeId($nodeId);

            if (!$deputyUserId) {
                $this->writeToTrackingService(
                    'Заместитель для подразделения D' . $departmentId . ' не найден. HR nodeId: ' . $nodeId,
                    0,
                    \CBPTrackingType::Report
                );

                $this->DeputyUser = null;

                return $errors;
            }

            $this->DeputyUser = 'user_' . $deputyUserId;

            $this->writeToTrackingService(
                'Найден заместитель подразделения D' . $departmentId . ': user_' . $deputyUserId . '. HR nodeId: ' . $nodeId,
                0,
                \CBPTrackingType::Report
            );

            return $errors;

        } catch (\Throwable $e) {
            $this->writeToTrackingService(
                sprintf(
                    'Error %s on line %s in file %s',
                    $e->getMessage(),
                    $e->getLine(),
                    $e->getFile()
                ),
                0,
                \CBPTrackingType::Error
            );
        }

        return $errors;
    }

    private function getNodeIdByDepartmentId(int $departmentId): ?int
    {
        $nodeRepository = new \Bitrix\HumanResources\Repository\NodeRepository();

        $rootNode = $nodeRepository->getById(1);

        if (!$rootNode) {
            return null;
        }

        $nodes = $nodeRepository
            ->getChildOf(
                $rootNode,
                \Bitrix\HumanResources\Enum\DepthLevel::FULL
            )
            ->getItemMap();

        $accessCode = 'D' . $departmentId;

        foreach ($nodes as $node) {
            if ($node->accessCode === $accessCode) {
                return (int)$node->id;
            }
        }

        return null;
    }

    private function getDeputyUserIdByNodeId(int $nodeId): ?int
    {
        $nodeMemberService = new \Bitrix\HumanResources\Service\NodeMemberService();

        $members = $nodeMemberService
            ->getAllEmployees($nodeId, false, true)
            ->getItemMap();

        foreach ($members as $member) {
            if (in_array(3, (array)$member->roles, true)) {
                return (int)$member->entityId;
            }
        }

        return null;
    }

    public static function getPropertiesDialogMap(?\Bitrix\Bizproc\Activity\PropertiesDialog $dialog = null): array
    {
        return [
            'DepartmentId' => [
                'Name' => 'ID подразделения',
                'FieldName' => 'DepartmentId',
                'Type' => \Bitrix\Bizproc\FieldType::INT,
                'Required' => true,
            ],
        ];
    }

    public static function getPropertiesMap(array $documentType, array $context = []): array
    {
        return [
            'DeputyUser' => [
                'Name' => 'Заместитель подразделения',
                'Type' => \Bitrix\Bizproc\FieldType::USER,
                'Multiple' => false,
            ],
        ];
    }
}