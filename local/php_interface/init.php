<?php

// Подключаем обработчик событий
require_once($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/log_handler.php");

AddEventHandler('iblock', 'OnAfterIBlockElementAdd', 'EventHandler::OnAfterIBlockElementAddHandler');
AddEventHandler('iblock', 'OnAfterIBlockElementUpdate', 'EventHandler::OnAfterIBlockElementUpdateHandler');


?>