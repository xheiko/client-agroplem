<?

namespace Tanais\Alter\Crm;

class Constant
{
    public static function get($entityId = null)
    {
        $filter = [];
        if ($entityId)
            $filter['VISIBILITY'] = 'CRM_DYNAMIC_' . $entityId;

        $constsData = \Bitrix\Bizproc\Workflow\Type\Entity\GlobalConstTable::getList([
            'filter' => $filter,
        ])->fetchAll();

        foreach ($constsData as &$constData) {
            if ($entityId)
                $constData['ENTITY_TYPE_ID'] = $entityId;
            if ($constData['PROPERTY_TYPE'] == 'user')
                if (is_array($constData['PROPERTY_VALUE']))
                    foreach ($constData['PROPERTY_VALUE'] as &$userId)
                        $userId = intval(str_replace('user_', '', $userId));
                else
                    $constData['PROPERTY_VALUE'] = intval(str_replace('user_', '', $constData['PROPERTY_VALUE']));
        }

        return $constsData;
    }
}
