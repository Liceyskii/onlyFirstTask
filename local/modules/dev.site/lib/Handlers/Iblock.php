<?php

namespace Only\Site\Handlers;

use CUser;
\Bitrix\Main\Loader::includeModule('iblock');

class Iblock
{
    // ID Инфоблока LOG
    private static $iblockLogID = 1;

    // Обработчик создания элемента любого инфоблока кроме LOG
    public static function OnAfterIBlockElementAddHandler(&$arFields) : bool
    {
        if ($arFields['IBLOCK_ID'] != self::$iblockLogID) {
            $USER = new CUser;

            // Получаем данные инфоблока
            $iblock = \CIBlock::GetByID($arFields['IBLOCK_ID'])->Fetch();

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
            $el = new \CIBlockElement; 
            
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
            $iblock = \CIBlock::GetByID($arFields['IBLOCK_ID'])->Fetch();

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
            $el = new \CIBlockElement; 
            
            $arFilter = array(
                "IBLOCK_ID" => self::$iblockLogID, // ID инфоблока
                "NAME" => $arFields['ID'] // Имя элемента
            );
            
            $res = \CIBlockElement::GetList(array(), $arFilter, false, false, ['ID']);
            $element = $res->GetNextElement();
            // Debug::dumpToFile($element, 'Получение ID элемента из LOG', 'DebugLog');
            $elementId = $element->GetFields();


            // Обновляем существующий элемент в LOG
            $el->Update($elementId['ID'], $newElement); // ID обновляемого элемента
        }

        return true;
    }

    function OnBeforeIBlockElementAddHandler(&$arFields)
    {
        $iQuality = 95;
        $iWidth = 1000;
        $iHeight = 1000;
        /*
         * Получаем пользовательские свойства
         */
        $dbIblockProps = \Bitrix\Iblock\PropertyTable::getList(array(
            'select' => array('*'),
            'filter' => array('IBLOCK_ID' => $arFields['IBLOCK_ID'])
        ));
        /*
         * Выбираем только свойства типа ФАЙЛ (F)
         */
        $arUserFields = [];
        while ($arIblockProps = $dbIblockProps->Fetch()) {
            if ($arIblockProps['PROPERTY_TYPE'] == 'F') {
                $arUserFields[] = $arIblockProps['ID'];
            }
        }
        /*
         * Перебираем и масштабируем изображения
         */
        foreach ($arUserFields as $iFieldId) {
            foreach ($arFields['PROPERTY_VALUES'][$iFieldId] as &$file) {
                if (!empty($file['VALUE']['tmp_name'])) {
                    $sTempName = $file['VALUE']['tmp_name'] . '_temp';
                    $res = \CAllFile::ResizeImageFile(
                        $file['VALUE']['tmp_name'],
                        $sTempName,
                        array("width" => $iWidth, "height" => $iHeight),
                        BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
                        false,
                        $iQuality);
                    if ($res) {
                        rename($sTempName, $file['VALUE']['tmp_name']);
                    }
                }
            }
        }

        if ($arFields['CODE'] == 'brochures') {
            $RU_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_RU');
            $EN_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_EN');
            if ($arFields['IBLOCK_ID'] == $RU_IBLOCK_ID || $arFields['IBLOCK_ID'] == $EN_IBLOCK_ID) {
                \CModule::IncludeModule('iblock');
                $arFiles = [];
                foreach ($arFields['PROPERTY_VALUES'] as $id => &$arValues) {
                    $arProp = \CIBlockProperty::GetByID($id, $arFields['IBLOCK_ID'])->Fetch();
                    if ($arProp['PROPERTY_TYPE'] == 'F' && $arProp['CODE'] == 'FILE') {
                        $key_index = 0;
                        while (isset($arValues['n' . $key_index])) {
                            $arFiles[] = $arValues['n' . $key_index++];
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'L' && $arProp['CODE'] == 'OTHER_LANG' && $arValues[0]['VALUE']) {
                        $arValues[0]['VALUE'] = null;
                        if (!empty($arFiles)) {
                            $OTHER_IBLOCK_ID = $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? $EN_IBLOCK_ID : $RU_IBLOCK_ID;
                            $arOtherElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => $OTHER_IBLOCK_ID,
                                    'CODE' => $arFields['CODE']
                                ], false, false, ['ID'])
                                ->Fetch();
                            if ($arOtherElement) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arOtherElement['ID'], $OTHER_IBLOCK_ID, $arFiles, 'FILE');
                            }
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'E') {
                        $elementIds = [];
                        foreach ($arValues as &$arValue) {
                            if ($arValue['VALUE']) {
                                $elementIds[] = $arValue['VALUE'];
                                $arValue['VALUE'] = null;
                            }
                        }
                        if (!empty($arFiles && !empty($elementIds))) {
                            $rsElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => \Only\Site\Helpers\IBlock::getIblockID('PRODUCTS', 'CATALOG_' . $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? '_RU' : '_EN'),
                                    'ID' => $elementIds
                                ], false, false, ['ID', 'IBLOCK_ID', 'NAME']);
                            while ($arElement = $rsElement->Fetch()) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arElement['ID'], $arElement['IBLOCK_ID'], $arFiles, 'FILE');
                            }
                        }
                    }
                }
            }
        }
    }

    // Функция для поиска или создания раздела
    private static function findOrCreateSection($iblock) {
        $sectionCode = $iblock['CODE'];
        $section = \CIBlockSection::GetList(
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

        $section = \CIBlockSection::GetList(
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

        $section = \CIBlockSection::GetByID($iblockSection)->GetNext();
        $path = $section['NAME'] . ' -> ';

        if ($section['ID']) {
            while($section['IBLOCK_SECTION_ID']) {
                $section = \CIBlockSection::GetByID($section['IBLOCK_SECTION_ID'])->GetNext();
                $path = $section['NAME'] . ' -> ' . $path;
            }
            $path = $iblock['NAME'] . ' -> ' . $path . $arFields['NAME'];
        } else $path = $iblock['NAME'] . ' -> ' . $arFields['NAME'];
        
        // Debug::dumpToFile($path, 'message', 'DebugLog');

        return $path;
    }

}
