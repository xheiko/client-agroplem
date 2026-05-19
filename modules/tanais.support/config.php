<?
global $MODULE_ID;
//global module options
$MODULE_ID='tanais.support';//Also change class name
$arModule=array(
    "ID"=>$MODULE_ID,
    "NAME_SPACE"=>$MODULE_ID,
    "NAME"=>'Поддержка',
    "DESC"=>'Tanais',
    "PATH"=>$_SERVER['DOCUMENT_ROOT']."/local/modules/".$MODULE_ID."/",
    "CHARSET"=>"UTF-8",
);

// // custom instalation folders (default component/module_name_space,php_interface/module_name_space,component/module_name_space, admin/module_name_space)
// $arInstallFolders=array(
//     'install/public/images/'.$MODULE_ID.'/'=>$_SERVER['DOCUMENT_ROOT'].'/images/'.$MODULE_ID.'/',
// );
// //add Items in to menu
// $arMenuItem=array(
//     // array(
//         // 'NAME'=>'left.menu',
//         // 'PATH' => '//',
//         // 'ITEMS'=>array(
//             // array(
//                 // 'NAME'=>'',
//                 // 'URL'=>'/',
//                 // 'PERMISSION'=>'',
//             // ),
//         // ),
//     // ),
// );