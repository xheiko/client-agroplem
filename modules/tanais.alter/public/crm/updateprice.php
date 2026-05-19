<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

\Bitrix\Main\UI\Extension::load('tanais.alter.crm.copyprice');

$APPLICATION->SetTitle('Копирование Розничной цены в Цену для прайса');

?>

<!-- .ui-alert.ui-alert-danger-->
<div class="ui-alert ui-alert-danger ui-alert-icon-info">
	<span class="ui-alert-message"><strong>Внимание!</strong><br>
	На данной странице выполняется копирование Розничной цены товаров в дополнительное пользовательское поле «Цена для прайса».<br>
Функция предназначена для быстрого обновления цен, используемых при формировании прайс-листов и внешних выгрузок.
При нажатии кнопки текущие розничные цены товаров будут автоматически перенесены в соответствующее поле без изменения основной цены.
	</span>
</div>
<p></p>
<br>
<button id="btn-copy-price" class="ui-btn ui-btn-primary-dark" onclick="copyprice()">Копировать цену</button>
<br>
<br>

<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
?>
