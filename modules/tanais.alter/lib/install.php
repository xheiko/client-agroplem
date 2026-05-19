<?

namespace Tanais\Alter;

// Абрамов В.А.
// Класс функций используемых при установки.
// Выделен в отдельный класс, чтобы дергать функции без переустановки модуля

class Install
{
    const MODULE_ID = 'tanais.alter';

    //Создает символическую ссылку на файлы модуля в публичке
    public static function installPublicFiles()
    {
        $modulePath  = \COption::GetOptionString(self::MODULE_ID,"module_path");
        $publicPath  = \COption::GetOptionString(self::MODULE_ID,"public_path");
        $jsPath 	 = \COption::GetOptionString(self::MODULE_ID,"js_path");
        $logPath 	 = \COption::GetOptionString(self::MODULE_ID,"log_path");
        $componentsPath = $_SERVER["DOCUMENT_ROOT"].'/local/components/'.self::MODULE_ID;
        echo $js_path;

        //Создание симлинка на публичные файлы
        mkdir($publicPath, 0755, true);
        rmdir($publicPath);
        $symlinkResult=symlink($modulePath."/public",$publicPath);


        //Создание симлинка на JS файлы
        mkdir($jsPath, 0755, true);
        rmdir($jsPath);
        symlink($modulePath."/js",$jsPath);

        //Создание симлинка на компоненты
        mkdir($componentsPath, 0755, true);
        rmdir($componentsPath);
        symlink($modulePath."/components",$componentsPath);

        //Создаем папку для логов
        mkdir($logPath, 0755, true);

        return true;
    }

    //Удаляет файлы публичной части модуля
    static public function uninstallPublicFiles()
    {
        $public_path = \COption::GetOptionString(self::MODULE_ID,"public_path");
        $js_path 	 = \COption::GetOptionString(self::MODULE_ID,"js_path");

        //Удаляем симлинки, если там директории то они не будут удалены
        unlink($public_path);
        unlink($js_path );
    }

    //Создает файлы админки
    public static function installAdminFiles()
    {
//        $modulePath  = \COption::GetOptionString(self::MODULE_ID,"module_path");
//        $files = array_diff(scandir($modulePath."/admin"), array('..', '.'));
//        $adminPagesPath=$_SERVER["DOCUMENT_ROOT"].BX_ROOT."/admin/";
//        $filePrefix=str_replace(".", "_", self::MODULE_ID."_"); // partner.module -> partner_module
//        foreach ($files as $file) {
        /*            $phpContent= '<?require_once("'.$modulePath.'/admin/'.$file.'");?>';*/
//            file_put_contents($adminPagesPath."/".$filePrefix.$file,$phpContent);
//        }
        return true;
    }

    //Удаляем файлы админки
    public static function unInstallAdminFiles()
    {
//        $modulePath  = \COption::GetOptionString(self::MODULE_ID,"module_path");
//        $adminPagesPath=$_SERVER["DOCUMENT_ROOT"].BX_ROOT."/admin/";
//        $files = array_diff(scandir($adminPagesPath), array('..', '.'));
//        $filePrefix=str_replace(".", "_", self::MODULE_ID."_"); // partner.module -> partner_module
//        foreach ($files as $file) {
//            if (str_starts_with($file, $filePrefix) )
//                unlink($file);
//        }
        return true;
    }

}