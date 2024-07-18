<?php

namespace Only\Site\Agents;

use Only\Site\Handlers\Iblock;

class IblockAgent
{
    public static function clearOldLogs()
    {
        global $DB;
        
        if (\Bitrix\Main\Loader::includeModule('iblock')) {
            // Получаем ID инфоблока LOG
            $iblockLogID = Iblock::getIblockIdByCode('LOG'); // Замените на ваш ID инфоблока LOG
            
            // Получаем список элементов, отсортированный по дате создания 
            $rsLogs = \CIBlockElement::GetList(
                ['TIMESTAMP_X' => 'DESC'], // Сортируем по возрастанию даты создания
                [
                    'IBLOCK_ID' => $iblockLogID
                ],
                false,
                false,
                ['ID', 'IBLOCK_ID', 'TIMESTAMP_X']
            );
            
            for ($i = 0; $i < 10; $i++) {
                if ($rsLogs->Fetch()) {
                    // Пропускаем первые 10 элементов
                }
            }

            while ($arLog = $rsLogs->Fetch()) {
                if ($arLog) {
                    \CIBlockElement::Delete($arLog['ID']);
                }
            }

        }
        
        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }

    public static function example()
    {
        global $DB;
        if (\Bitrix\Main\Loader::includeModule('iblock')) {
            $iblockId = \Only\Site\Helpers\IBlock::getIblockID('QUARRIES_SEARCH', 'SYSTEM');
            $format = $DB->DateFormatToPHP(\CLang::GetDateFormat('SHORT'));
            $rsLogs = \CIBlockElement::GetList(['TIMESTAMP_X' => 'ASC'], [
                'IBLOCK_ID' => $iblockId,
                '<TIMESTAMP_X' => date($format, strtotime('-1 months')),
            ], false, false, ['ID', 'IBLOCK_ID']);
            while ($arLog = $rsLogs->Fetch()) {
                \CIBlockElement::Delete($arLog['ID']);
            }
        }
        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }
}
