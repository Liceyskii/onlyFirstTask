<?php

use Only\Site\Handlers\Iblock;

// Подключаем обработчик событий
require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/dev.site/lib/Handlers/Iblock.php");

AddEventHandler('iblock', 'OnAfterIBlockElementAdd', 'Only\Site\Handlers\Iblock::OnAfterIBlockElementAddHandler');
AddEventHandler('iblock', 'OnAfterIBlockElementUpdate', 'Only\Site\Handlers\Iblock::OnAfterIBlockElementUpdateHandler');


?>