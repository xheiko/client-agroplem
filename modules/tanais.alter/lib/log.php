<?

namespace Tanais\Alter;

class Log
{
    const  MODULE_ID = 'tanais.alter';
    const  LOG_PREFIX = 'LOG_';

    //Логируем в настройки модуля
    static public function save($logTitle, $resultParameter, $debugParameter = '')
    {
        if (!$logTitle)
            return false;

        //Проверяем есть ли такой Title в списке, если нет, то добавляем
        $logTitles = json_decode(\COption::GetOptionString(self::MODULE_ID, "LOG_TITLES"), true);
        if (!is_array($logTitles))
            $logTitles = [];
        if (!in_array(self::LOG_PREFIX . $logTitle, $logTitles)) {
            $logTitles[] = self::LOG_PREFIX . $logTitle;
            \COption::SetOptionString(self::MODULE_ID, "LOG_TITLES", json_encode($logTitles));
        }
        if (!$debugParameter)
            $debugParameter = $_SERVER['REQUEST_URI'];

        if (\Bitrix\Main\Engine\CurrentUser::get()->getId())
            $userLog = \Bitrix\Main\Engine\CurrentUser::get()->getLogin();
        else
            $userLog = 'non-Authorized';

        //Подготовливаем данные для сохранения
        $logRow = [
            'timestamp' => microtime(true),
            'time' => FormatDate('d M в H:i:s', time()),
            'title' => $logTitle,
            'result' => $resultParameter,
            'user' => $userLog,
            'debug' => $debugParameter,
        ];

        \COption::SetOptionString(self::MODULE_ID, self::LOG_PREFIX . $logTitle, json_encode($logRow));
    }

    //Логируем что-то в файл с датой
    public static function saveToFile($fileName, $mixObject, $flags = 0)
    {
        if (!strval($fileName))
            return false;
        $fileName = preg_replace('/[^A-Za-z0-9]/', '', $fileName);

        if (\Bitrix\Main\Engine\CurrentUser::get()->getId())
            $userLog = \Bitrix\Main\Engine\CurrentUser::get()->getLogin();
        else
            $userLog = 'non-Authorized';

        $fileName = \Tanais\Alter\Config::LOG_PATH . '/' . strval($fileName) . ".log";
        file_put_contents($fileName, date('Y-m-d H:i:s') . "\r\n", $flags);
        file_put_contents($fileName, $userLog . "\r\n\r\n", FILE_APPEND);
        file_put_contents($fileName, var_export($mixObject, true) . "\r\n", FILE_APPEND);
    }


    static function sortFunctionByTitle($a, $b)
    {
        return strcmp($a["title"], $b["title"]);
    }

    static public function getAll($sort = '')
    {

        //Проверяем есть ли такой Title в списке, если нет, то добавляем
        $logTitles = json_decode(\COption::GetOptionString(self::MODULE_ID, "LOG_TITLES"), true);
        if (!is_array($logTitles))
            $logTitles = [];

        $logData = [];
        foreach ($logTitles as $title) {
            $logRow = json_decode(\COption::GetOptionString(self::MODULE_ID, $title), true);
            $logData[] = $logRow;
        }

        if ($sort == 'time')
            rsort($logData);

        if ($sort == 'title') {
            usort($logData, "\\" . self::class . "::sortFunctionByTitle");
        }

        return $logData;
    }

    static public function get($title)
    {
        if (!$title)
            return false;

        foreach ($logTitles as $title) {
            $logRow = json_decode(\COption::GetOptionString(self::MODULE_ID, $title), true);
        }

        return $logRow;
    }

    static public function print($sort = 'time')
    {
        echo '<table class="adm-log-table">';
        ?>
        <tr><?
        ?>
        <th class="th-time">Время</th><?
        ?>
        <th>Наименование</th><?
        ?>
        <th>Результат</th><?
        ?>
        <th>Пользователь</th><?
        ?>
        <th>Отладка</th><?
        ?>
        <tr><?

        $logData = self::getAll($sort);
        foreach ($logData as $row) {
            ?>
            <tr><?
            ?>
            <td><?= $row['time'] ?></td><?
            ?>
            <td><?= $row['title'] ?></td><?
            ?>
            <td><?= $row['result'] ?></td><?
            ?>
            <td><?= $row['user'] ?></td><?
            ?>
            <td><?= $row['debug'] ?></td><?
            ?>
            <tr><?
        }
        echo "</table>";

        return $logData;
    }

    static public function getTitles()
    {
        return json_decode(\COption::GetOptionString(self::MODULE_ID, "LOG_TITLES"), true);
    }

    static public function clear($title)
    {
        if (!$title)
            return false;
        $logRow = [
            'time' => '',
            'title' => $title,
            'result' => '',
            'debug' => 'cleared ' . date('d.m.Y H:i:s'),
        ];

        \COption::SetOptionString(self::MODULE_ID, self::LOG_PREFIX . $title, json_encode($logRow));
    }

    static public function removeTitle($title)
    {
        if (!$title)
            return false;

        //Получаем список
        $logTitles = json_decode(\COption::GetOptionString(self::MODULE_ID, "LOG_TITLES"), true);
        if (!is_array($logTitles))
            return true;


        //Если внутри, то удаляем
        if (in_array(self::LOG_PREFIX . $title, $logTitles)) {
            $newTitles = array_diff($logTitles, [self::LOG_PREFIX . $title]);
            \COption::SetOptionString(self::MODULE_ID, "LOG_TITLES", json_encode($newTitles));
        }
    }


}