<?

//Выводит дебаг переменных/классов в читаемом виде
// td($array);
// td($object,false,"Мой Объект"); выводит дебаг свёрнутым, с заголовком Мой объект.
function td($debug = null, $open = true, $headerText = null, $onlyAdmin = true)
{
    $isAdmin = \Bitrix\Main\Engine\CurrentUser::get()->isAdmin();
    if (($onlyAdmin) && (!$isAdmin))
        return;

    define("TANAIS_DEBUG_VERSION", "v.17022025");
    global $tanaisDebugStartTime, $tanaisDebugLastTime;

    if (empty($tanaisDebugStartTime)) {
        $tanaisDebugStartTime = microtime(true);
        $tanaisDebugLastTime = microtime(true);
    }

    $timeFromStart = round(microtime(true) - $tanaisDebugStartTime, 3);
    $timeFromStart = number_format($timeFromStart, 3) . 's';
    $timeFromLast = round(microtime(true) - $tanaisDebugLastTime, 3);
    $timeFromLast = number_format($timeFromLast, 3) . 's';

    $memUsage = memory_get_peak_usage(true);
    $memUsage = number_format($memUsage / 1024 / 1024, 1) . 'Mb';

    $type = gettype($debug,);
    // $debugStr = var_export($debug, true);
    $debugStr = json_encode($debug, JSON_ERROR_NONE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_OBJECT_AS_ARRAY, 16);


    if ($type == 'object') {
        $type = get_class($debug);
        if ($debugStr == '{}') {
            $objectMethods = get_class_methods($debug);
            $debugStr = var_export(json_encode($objectMethods, JSON_ERROR_NONE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_OBJECT_AS_ARRAY, 16), true) . "\r\n";
        }
        $debugStr = str_replace('{', '[', $debugStr);
        $debugStr = str_replace('}', ']', $debugStr);
        $debugStr = str_replace('": ', '" => ', $debugStr);
        $debugStr = 'Class ' . $type . "\r\n" . $debugStr;
    }
    if ($type == 'array') {
        $type = 'Array(' . count($debug) . ')';
        $debugStr = str_replace('{', '[', $debugStr);
        $debugStr = str_replace('}', ']', $debugStr);
        $debugStr = str_replace('": ', '" => ', $debugStr);
    }


    $userName = \Bitrix\Main\Engine\CurrentUser::get()->getFormattedName();
    if ($open == true)
        $open = 'open';
    else
        $open = '';

    $trace = debug_backtrace();
    $caller = " line " . $trace[0]['line'] . ', ' . str_replace(\Bitrix\Main\Application::getDocumentRoot(), '', $trace[0]['file']);

    if (empty($headerText)) {
        $headerText = $type . " " . $caller;
    }
?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@300..700&family=Ubuntu+Condensed&family=Ubuntu+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap');
    </style>

    <style>
        .td-pre {
            background-color: #444;
            padding: 7px;
            color: #FFAF00;
            margin: 0;
            font-family: Fira Code, serif;
            border: thin solid black;
        }

        .td-container {
            width: 100%;
            position: relative;
            margin-bottom: 10px;
            display: inline-block;
        }

        .td-top-badge {
            position: absolute;
            top: -5px;
            right: 10px;
            padding: 7px;
            background-color: #eee;
            border: 0px solid black;
            border-radius: 5px;
            background-color: #555;
            color: #eeeeee;
            font-family: Fira Code, serif;
            font-size: 8pt;
        }

        .td-bottom-badge {
            position: absolute;
            right: 10px;
            bottom: -5px;
            padding: 7px;
            background-color: #ddd;
            border: 0px solid black;
            border-radius: 10px;
            background-color: #555;
            color: lightgreen;
            font-family: Fira Code, serif;
            font-size: 8pt;
        }

        .td-summary {
            padding: 15px;
            cursor: pointer;
            color: #ddd;
        }

        .td-details[open] summary {
            font-family: Fira Code, serif;
            background-color: #444;
            color: #ddd;
        }

        .td-details summary {
            font-family: Fira Code, serif;
            background-color: unset;
            color: #444;
        }
    </style>
    <div class="td-container">
        <details class="td-details" <?= $open ?>>
            <summary class="td-summary"><?= $headerText ?></summary>
            <!-- <span class="td-top-badge"><?= $caller ?></span> -->
            <span class="td-bottom-badge">С прошлого запуска: <?= $timeFromLast ?> С первого запуска: <?= $timeFromStart ?> <?= $memUsage ?> <?= TANAIS_DEBUG_VERSION ?></span>
            <pre class="td-pre"><?= $debugStr ?></pre>
        </details>
    </div>

<?
    $tanaisDebugLastTime = microtime(true);
}
