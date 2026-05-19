<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("PUBLIC_AJAX_MODE", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once $_SERVER["DOCUMENT_ROOT"] . '/bitrix/vendor/autoload.php';

ob_end_clean();
header_remove();

try {
    $reportObject = new \Tanais\Alter\Report();

    if (empty($arResult['LIST_EXPORT'])) {
        die('Нет данных для отчета');
    }

    $templatePath = $_SERVER["DOCUMENT_ROOT"] . "/upload/reportBusinessTrip.docx";
    if (!file_exists($templatePath)) {
        die('Шаблон не найден');
    }

    $tempDir = sys_get_temp_dir() . '/trip_export_' . uniqid();
    mkdir($tempDir, 0777, true);

    $zip = new ZipArchive();
    $zipFilePath = $tempDir . '/Архив_отчетов_' . date('d.m.Y') . '.zip';
    $zip->open($zipFilePath, ZipArchive::CREATE);

    foreach ($arResult['LIST_EXPORT'] as $index => $item) {
        $data = $item['data'];
        $replace = [
            'BEGINDATE' => date('d.m.Y', strtotime($data['BEGINDATE'])),
            'ENDDATE' => date('d.m.Y', strtotime($data['ENDDATE'])),
            'DATE_NOW' => date('d.m.Y'),
            'FIO_EMPLOYEE' => $data['EMPLOYEE'],
            'POSITION' => $data['WORK_POSITION'],
            'HEAD_NAME' => $data['HEAD_NAME'],
            'FIO_DIRECTOR' => 'Марченков А.В.',
            'GOAL' => $data['GOAL'],
            'RESULT' => $data['RESULT'],
        ];


        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
        foreach ($replace as $key => $value) {
            $templateProcessor->setValue($key, htmlspecialchars_decode($value));
        }

        $filename = 'Отчет_о_командировке_' . ($replace['EMPLOYEE'] ?: 'Сотрудник') . '_' . ($replace['BEGINDATE'] ?: 'дата') . '_' . $index . '.docx';
        $filePath = $tempDir . '/' . $filename;
        $templateProcessor->saveAs($filePath);

        $zip->addFile($filePath, $filename);
    }
    $zip->close();

    header("Content-Type: application/zip");
    header('Content-Disposition: attachment; filename="Архив_отчетов_' . date('d.m.Y') . '.zip"');
    header("Content-Length: " . filesize($zipFilePath));
    readfile($zipFilePath);

    array_map('unlink', glob("$tempDir/*.docx"));
    unlink($zipFilePath);
    rmdir($tempDir);

    // exit;

} catch (\Throwable $e) {
    http_response_code(500);
    echo 'Ошибка: ' . $e->getMessage();
}