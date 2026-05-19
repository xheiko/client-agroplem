<?

namespace Tanais\Alter\Crm;

class Laboratory
{
    const  MODULE_ID = 'tanais.alter';
    const  IBLOCK_ID = 17;


    public static function getCompatibleData($categoryId)
    {
        if ((empty($categoryId)) or (intval($categoryId) == 0))
            return ['1'];

        // $arSelect = ["ID", "NAME", "PROPERTY_CODE"];
        $select = ["*", "PROPERTY_*", "IBLOCK_ID"];
        $filter = [
            'IBLOCK_ID' => self::IBLOCK_ID,
            "ACTIVE" => "Y",
            "PROPERTY_CATEGORY_ID" => $categoryId
        ];
        $sort = ["SORT" => "ASC"];

        $laboratories = \CIBlockElement::GetList($sort, $filter, false, false, $select);


        if ($laboratory = $laboratories->GetNextElement()) {
            $fields = $laboratory->GetFields();
            $properties = $laboratory->GetProperties();
            return array_merge($fields, $properties);
        }
        return [];
    }

    public static function getLaboratoryList(): array
    {
        $arLaboratory = [];
        $select = ["ID", "NAME", "IBLOCK_ID"];
        $filter = [
            'IBLOCK_ID' => self::IBLOCK_ID,
            "ACTIVE" => "Y",
        ];
        $sort = ["SORT" => "ASC"];

        $laboratories = \CIBlockElement::GetList($sort, $filter, false, false, $select);

        while ($laboratory = $laboratories->fetch()) {
            $arLaboratory[$laboratory["ID"]] = $laboratory["NAME"];
        }
        return $arLaboratory;
    }
}
