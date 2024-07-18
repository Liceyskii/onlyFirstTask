<?php

use Only\Site\Handlers\Iblock;
use Only\Site\Agents\IblockAgent;

// Подключаем обработчик событий и агент
require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/dev.site/lib/Handlers/Iblock.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/dev.site/lib/Agents/Iblock.php"); 


AddEventHandler('iblock', 'OnAfterIBlockElementAdd', 'Only\Site\Handlers\Iblock::OnAfterIBlockElementAddHandler');
AddEventHandler('iblock', 'OnAfterIBlockElementUpdate', 'Only\Site\Handlers\Iblock::OnAfterIBlockElementUpdateHandler');

?>