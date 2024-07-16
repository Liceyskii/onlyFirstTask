<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Event;
use CUser;
CModule::IncludeModule('iblock');

class EventHandler
{
    public static $iblockLogID = 1;

    // Обработчик создания элемента любого инфоблока кроме LOG
    public static function OnAfterIBlockElementAddHandler(&$arFields) : bool
    {
        if ($arFields['IBLOCK_ID'] != self::$iblockLogID) {
            $USER = new CUser;

            // Получаем данные инфоблока
            $iblock = CIBlock::GetByID($arFields['IBLOCK_ID'])->Fetch();

            // Находим или создаем раздел для лога
            $section = self::findOrCreateSection($iblock);
            
            // Добавляем запись в лог
            $newElement = array(
                'MODIFIED_BY' => $USER->GetID(),
                'IBLOCK_SECTION_ID' => $section['ID'],
                'IBLOCK_ID' => self::$iblockLogID,
                'NAME' => $arFields['ID'],
                "PREVIEW_TEXT" => self::buildElementPath($arFields, $iblock),
                'ACTIVE' => 'Y'
            );

            // Создаем экземпляр CIBlockElement
            $el = new CIBlockElement; 
            
            // Добавляем новый элемент в LOG
            $el->Add($newElement);
        }

        return true;
    }

    // Обработчик обновления элемента любого инфоблока кроме LOG
    public static function OnAfterIBlockElementUpdateHandler(&$arFields) : bool
    {
        if ($arFields['IBLOCK_ID'] != self::$iblockLogID) {
            $USER = new CUser;

            // Debug::dumpToFile($arFields['IBLOCK_ID'], 'ID инфоблока обновляемого элемента', 'DebugLog');

            // Получаем данные инфоблока
            $iblock = CIBlock::GetByID($arFields['IBLOCK_ID'])->Fetch();

            // Находим или создаем раздел для лога
            $section = self::findOrCreateSection($iblock);

            // Добавляем запись в лог
            $newElement = array(
                'MODIFIED_BY' => $USER->GetID(),
                'IBLOCK_SECTION_ID' => $section['ID'],
                'IBLOCK_ID' => self::$iblockLogID,
                'NAME' => $arFields['ID'], // ID обновляемого элемента
                "PREVIEW_TEXT" => self::buildElementPath($arFields, $iblock),
                'ACTIVE' => 'Y'
            );

            // Создаем экземпляр CIBlockElement
            $el = new CIBlockElement; 
            
            $arFilter = array(
                "IBLOCK_ID" => self::$iblockLogID, // ID инфоблока
                "NAME" => $arFields['ID'] // Имя элемента
            );
            
            $res = CIBlockElement::GetList(array(), $arFilter, false, false, ['ID']);
            $element = $res->GetNextElement();
            // Debug::dumpToFile($element, 'Получение ID элемента из LOG', 'DebugLog');
            $elementId = $element->GetFields();


            // Обновляем существующий элемент в LOG
            $el->Update($elementId['ID'], $newElement); // ID обновляемого элемента
        }

        return true;
    }

    // Функция для поиска или создания раздела
    private static function findOrCreateSection($iblock) {
        $sectionCode = $iblock['CODE'];
        $section = CIBlockSection::GetList(
            array(), 
            array(
                "IBLOCK_ID" => self::$iblockLogID, 
                "NAME" => $iblock['NAME'], 
            ), 
            false,
            array('ID')
        )->Fetch();

        if (!$section) {
            $section = new CIBlockSection;
            $section->Add(array(
                'IBLOCK_ID' => self::$iblockLogID,
                'NAME' => $iblock['NAME'],
                'CODE' => $sectionCode,
                'ACTIVE' => 'Y'
            ));
        }

        $section = CIBlockSection::GetList(
            array(), 
            array(
                "IBLOCK_ID" => self::$iblockLogID, 
                "NAME" => $iblock['NAME'], 
            ), 
            false,
            array('ID')
        )->Fetch();

        return $section;
    }

    // Функция для построения пути к элементу
    private static function buildElementPath($arFields, $iblock) {

        $iblockSections = $arFields['IBLOCK_SECTION'];
        $iblockSection = $iblockSections[0];

        $section = CIBlockSection::GetByID($iblockSection)->GetNext();
        $path = $section['NAME'] . ' -> ';

        if ($section['ID']) {
            while($section['IBLOCK_SECTION_ID']) {
                $section = CIBlockSection::GetByID($section['IBLOCK_SECTION_ID'])->GetNext();
                $path = $section['NAME'] . ' -> ' . $path;
            }
            $path = $iblock['NAME'] . ' -> ' . $path . $arFields['NAME'];
        } else $path = $iblock['NAME'] . ' -> ' . $arFields['NAME'];
        
        // Debug::dumpToFile($path, 'message', 'DebugLog');

        return $path;
    }
}

?>