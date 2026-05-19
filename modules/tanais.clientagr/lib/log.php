<?

namespace Tanais\ClientAGR;

class log
{

    //Добавляет запись в лог файл \Tanais\ClientAGR\Log::add('текст записи или массив или объект');
    static public function add($text)
    {
        $logFileName = \Bitrix\Main\Config\Option::get('tanais.clientagr', 'logfilename');
        if (empty($logFileName))
            $logFileName = $_SERVER['DOCUMENT_ROOT'] . '/local/log/clientAgr.log';
        if (!is_string($text))
            $text = var_export($text, true);
        if (!file_exists($logFileName)) {
            file_put_contents($logFileName, '');
            chmod($logFileName, 0666);
        }
        $date = date('Y-m-d H:i:s');
        $currentUser = \Bitrix\Main\Engine\CurrentUser::get();

        if ($currentUser && $currentUser->getId()) {
            $login = $currentUser->getLogin(); // может вернуть null, если поле пустое
            $login = $login ?: 'Unauthorized';
        } else {
            $login = 'Unauthorized';
        }

        file_put_contents($logFileName, "[$date] $login $text\n", FILE_APPEND);

        return true;
    }
}
