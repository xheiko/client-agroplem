<?

namespace Tanais\Support;

// Абрамов В.А.
// Класс функций используемых при установки.
// Выделен в отдельный класс, чтобы дергать функции без переустановки модуля

class Install
{
    const MODULE_ID = 'tanais.support';
    //Создает символическую ссылку на файлы модуля в публичке
    public static function installPublicFiles()
    {
        $modulePath = $_SERVER["DOCUMENT_ROOT"] . '/local/modules/tanais.support';

        //Папка Логов
        // $logPath = $_SERVER["DOCUMENT_ROOT"] . "/local/log/tanais.support";
        // if (!is_dir($logPath)) {
        //     mkdir($logPath, 0755, true);
        // }

        //Линки на публичные файлы, js расширения и компоненты
        $linksToCreate = [
            // [
            //     'PATH' => $_SERVER["DOCUMENT_ROOT"] . '/support',
            //     'TARGET' => $modulePath . "/public"
            // ],
            [
                'PATH' => $_SERVER["DOCUMENT_ROOT"] . '/local/js/tanais/support',
                'TARGET' => $modulePath . "/js",
            ],
            // [
            //     'PATH' => $_SERVER["DOCUMENT_ROOT"] . '/local/components/tanais.support',
            //     'TARGET' => $modulePath . "/components"
            // ],
        ];

        //Перебираем массив
        foreach ($linksToCreate as $link) {
            if (!is_link($link['PATH']) || readlink($link['PATH']) !== $link['TARGET']) {
                if (file_exists($link['PATH']) || is_link($link['PATH'])) {
                    unlink($link['PATH']);
                }
                symlink($link['TARGET'], $link['PATH']);
            }
        }
        return true;
    }

    //Удаляет файлы публичной части модуля
    static public function uninstallPublicFiles()
    {
        unlink($_SERVER["DOCUMENT_ROOT"] . '/local/modules/tanais.support');
        unlink($_SERVER["DOCUMENT_ROOT"] . '/local/js/tanais/support');
        // unlink($_SERVER["DOCUMENT_ROOT"] . '/local/components/tanais.support');
    }

    //Создает файлы админки
    public static function installAdminFiles()
    {
        // $modulePath  = \COption::GetOptionString(self::MODULE_ID, "module_path");
        // $files = array_diff(scandir($modulePath . "/admin"), array('..', '.'));
        // $adminPagesPath = $_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/admin/";
        // $filePrefix = str_replace(".", "_", self::MODULE_ID . "_"); // partner.module -> partner_module
        // foreach ($files as $file) {
        /*            $phpContent= '<?require_once("'.$modulePath.'/admin/'.$file.'");?>';*/
        //     file_put_contents($adminPagesPath . "/" . $filePrefix . $file, $phpContent);
        // }
        // return true;
    }

    //Удаляем файлы админки
    public static function unInstallAdminFiles()
    {
        // $modulePath  = \COption::GetOptionString(self::MODULE_ID, "module_path");
        // $adminPagesPath = $_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/admin/";
        // $files = array_diff(scandir($adminPagesPath), array('..', '.'));
        // $filePrefix = str_replace(".", "_", self::MODULE_ID . "_"); // partner.module -> partner_module
        // foreach ($files as $file) {
        //     if (str_starts_with($file, $filePrefix))
        //         unlink($file);
        // }
        // return true;
    }

    //Установка Опций по умолчанию
    public static function setOptions()
    {
        return true;
    }
    //Установка SQL
    public static function installSQL()
    {
        return true;
    }
}
