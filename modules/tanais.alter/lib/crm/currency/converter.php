<?php

namespace Tanais\Alter\Crm\Currency;

class Converter
{
    public static function getCurrencyForDate($date)
    {
        $wsdl = 'http://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx?WSDL';
        try {
            $usd_to = 0;
            $cbr = new \SoapClient($wsdl, ['soap_version' => SOAP_1_2, 'exceptions' => true]);
            $date = new \DateTime($date);
            $date = $date->getTimestamp();
            $result = $cbr->GetCursOnDateXML(['On_date' => $date]);
            if ($result->GetCursOnDateXMLResult->any) {
                $xml = new \SimpleXMLElement($result->GetCursOnDateXMLResult->any);
                foreach ($xml->ValuteCursOnDate as $currency) {
                    if ($currency->VchCode == 'USD') {
                        $usd_to = floatval($currency->Vcurs);
                        $usd_from = $currency->Vnom;
                    }
                }
                if ($usd_to != 0) {
                    return($usd_to);
                }
            } else echo 'Error!';
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
}