<?

namespace Tanais\ClientAGR;

class Helper
{

    //ВОзвращает URL вебхука для сервера $server или false, если не найден
    //Пример \Tanais\ClientAGR\Helper::getWebHook("bitrix.agroplem.ru");
    static public function getWebhook($server = "")
    {
        if (empty($server))
            return false;

        //Ищем в настройках модуля, где запрашивать данные    
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook1');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook2');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook3');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook4');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook5');

        $webhooks = array_filter($webhooks, function ($url) use ($server) {
            return strpos($url, $server) !== false;
        });
        $webhooks = array_values(array_unique($webhooks));
        if (empty($webhooks))
            return false;
        return current($webhooks);
    }

    static public function getAllPartnerServer()
    {

        //Ищем в настройках модуля, где запрашивать данные    
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook1');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook2');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook3');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook4');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook5');
        
        foreach ( $webhooks as $url) {
            $parts = parse_url($url);
            if (!$parts || !isset($parts['host'])) continue;
            $hosts[] = $parts['host'];
        }
        $hosts = array_unique($hosts);

        if (empty($webhooks))
            return false;
        return $hosts;
    }


    static public function getAllPartnerWebhook()
    {
        //Ищем в настройках модуля, где запрашивать данные    
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook1');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook2');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook3');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook4');
        $webhooks[] = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'webhook5');
        $webhooks = array_filter($webhooks);
        return $webhooks;
    }
    

    //Делает запрос к REST API Битрикс24 по вебхуку $webhook, методу $method с параметрами $params
    //Пример \Tanais\ClientAGR\Helper::b24Re stApiCall("https://bitrix.agroplem.ru/rest/1/abcdefg/", "crm.company.list", ["ID" => 123]);
    // static public function b24Rest ApiCall($webhook = "", $method = "", $params = []): array
    // {
    //     if (empty($webhook) || !filter_var($webhook, FILTER_VALIDATE_URL) || empty($method))
    //         return false;

    //     //Делаем запрос на удалённый сервер через CURL
    //     $ch = curl_init($webhook . $method);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_POST, true);

    //     if (!empty($params) && is_array($params)) {
    //         curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    //     }
    //     curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    //     curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    //     $response = curl_exec($ch);
    //     if ($response === false) {
    //         $err = curl_error($ch);
    //         curl_close($ch);
    //         echo "cURL error: $err\n";
    //         return false;
    //     }
    //     curl_close($ch);

    //     //Обрабатываем полученные данные 
    //     $data = json_decode($response, true);
    //     return $data['result'];
    // }

    //Делает запрос к REST API Битрикс24 по вебхуку $webhook, методу $method с параметрами $params
    //Пример \Tanais\ClientAGR\Helper::callRestApi("https://bitrix.agroplem.ru/rest/1/abcdefg/", "crm.company.list", ["ID" => 123]);   

    static public function callRestApi($webhook = "", $method = "", $params = [])
    {
        if (empty($webhook) || !filter_var($webhook, FILTER_VALIDATE_URL) || empty($method))
            return false;

        //Делаем запрос на удалённый сервер через CURL
        $ch = curl_init($webhook . $method);


        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if (!empty($params) && is_array($params))
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $response = curl_exec($ch);
        //Логирование
        // $paramStr = [];
        // foreach ($params as $key => $param) $paramStr[] = "{$key}=>{$param}";
        // $paramStr = implode(',', $paramStr);
        // \Tanais\ClientAGR\Log::add("Tanais\ClientAGR\Helper::callRestApi() {$webhook}{$method} $paramStr");

        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return "cURL error: $err\n";
        }
        //Обрабатываем полученные данные 
        if ($response) {
            $data = json_decode($response, true);
            return $data['result'];
        }

        return true;
    }
}
