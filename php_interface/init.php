<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/custom_mail.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/tanais_debug.php");

// определим константу LOG_FILENAME, в которой зададим путь к лог-файлу
// define("LOG_FILENAME", "/home/bitrix/custom_mail.log");
//дебаг почты
function custom_mail($to, $subject, $message, $additional_headers = '', $additional_parameters = '')
{
    $additional_headers .=  PHP_EOL . 'List-Unsubscribe: <mailto:unsubscribe@agroplem.ru?subject=Unsubscribe>';
    $logPath = '/home/bitrix/www/local/log/mail/';
    if (!is_dir($logPath))
        mkdir($logPath, 0755, true);
    file_put_contents($logPath . "/" . date("d.m.Y H-i-s") . ".log", var_export(
        'To: ' . $to . PHP_EOL .
            'Subject: ' . $subject . PHP_EOL .
            'Headers: ' . PHP_EOL . $additional_headers . PHP_EOL .
            'Params: ' . PHP_EOL . $additional_parameters . PHP_EOL,
        'Message: ' . PHP_EOL . $message . PHP_EOL .
            true
    ));
    if ($additional_parameters != '') {
        return @mail($to, $subject, $message, $additional_headers, $additional_parameters);
    } else {
        return @mail($to, $subject, $message, $additional_headers);
    }
}
