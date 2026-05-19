<?//При переносе портала слетают симлинки. Нужно восстановить. 
//Срабатывает при подключении модуля
//Время выполнения  0.000984 с.
// \Tanais\Support\Install::installPublicFiles();
\Tanais\Support\Install::installPublicFiles();

\Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    'Tanais\\Extensions\\Handler' => '/local/classes/tanais/extensions/handler.php',
]);
?>