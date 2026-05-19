<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;

class CustomExport extends CBitrixComponent implements Controllerable
{
    /**
     * @return array
     */
    public function configureActions()
    {
        return [
            'exportTable' => [
                'prefilters' => []
            ],
            'updateLeadUfDate' => [
                'prefilters' => []
            ]
        ];
    }

    /**
     * @param string $param2
     * @param string $param1
     * @return array
     */
    public function exportTableAction($columns, $rows)
    {
        $html .= '<table id="export-table">';
        $html .= '<tbody>';
        $html .= '<tr>';
        foreach ($columns as $column) {
            $html .= '<th>' . $column['name'] . '</th>';
        }
        $html .= '</tr>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row['data'] as $rowValue) {
                $html .= '<td>' . $rowValue . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table">';
        return [
            'html' => $html,
        ];
    }

    public function updateDealUfDateAction($propCode, $leadId, $dateTo, $index)
    {
        if (\Bitrix\Main\Loader::includeModule('crm')) {
            $leadResult = \CCrmDeal::GetListEx(
                [
                    'SOURCE_ID' => 'DESC'
                ],
                ['CHECK_PERMISSIONS' => 'N', "ID" => $leadId],
                false,
                false,
                [
                    'ID',
                    $propCode
                ]
            );

            if ($lead = $leadResult->fetch()) {
                if ($index !== "undefined") {
                    $lead[$propCode][$index] = date('d.m.Y', strtotime($dateTo));
                    $dateValue = $lead[$propCode];
                } else {
                    $dateValue = date('d.m.Y', strtotime($dateTo));
                }
                $entity = new \CCrmDeal();

                $fields = [
                    $propCode => $dateValue,
                ];
                $entity->update($leadId, $fields);
            }
        }
    }

} ?>