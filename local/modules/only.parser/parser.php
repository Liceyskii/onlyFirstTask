<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

// Проверка авторизации
if (!$USER->IsAdmin()) {
    LocalRedirect('/');
}

// Включение модуля Iblock
\Bitrix\Main\Loader::includeModule('iblock');

// ID информационного блока
const IBLOCK_ID = 1; 

// Создание объекта для работы с элементами Iblock
$el = new \CIBlockElement;

// Получение значений свойств типа "Список"
$arProps = [];
$rsProp = \CIBlockPropertyEnum::GetList(
    ["SORT" => "ASC", "VALUE" => "ASC"],
    ['IBLOCK_ID' => IBLOCK_ID]
);
while ($arProp = $rsProp->Fetch()) {
    $arProps[$arProp['PROPERTY_CODE']][trim($arProp['VALUE'])] = $arProp['ID'];
}

// Удаление существующих элементов инфоблока
$rsElements = \CIBlockElement::GetList([], ['IBLOCK_ID' => IBLOCK_ID], false, false, ['ID']);
while ($element = $rsElements->GetNext()) {
    \CIBlockElement::Delete($element['ID']);
}

// Открытие CSV-файла
$handle = fopen("vacancy.csv", "r"); 

// Пропускаем первую строку с заголовками
fgetcsv($handle, 1000, ",");

// Цикл по строкам CSV-файла
while (($data = fgetcsv($handle, 1000, ",")) !== false) {
    // Подготовка массива свойств для элемента Iblock
    $prop = [
        'ACTIVITY' => trim($data[9]),
        'FIELD' => trim($data[11]),
        'OFFICE' => trim($data[1]),
        'LOCATION' => trim($data[2]),
        'REQUIRE' => trim($data[4]),
        'DUTY' => trim($data[5]),
        'CONDITIONS' => trim($data[6]),
        'EMAIL' => trim($data[12]),
        'DATE' => date('d.m.Y'),
        'TYPE' => trim($data[8]),
        'SALARY_TYPE' => '',
        'SALARY_VALUE' => trim($data[7]),
        'SCHEDULE' => trim($data[10]),
    ];

    // Обработка поля "Офис"
    // $officeName = strtolower($prop['OFFICE']);
    // if ($officeName == 'центральный офис') {
    //     $prop['OFFICE'] = 'СВЕЗА ' . $prop['LOCATION'];
    // } elseif ($officeName == 'лесозаготовка') {
    //     $prop['OFFICE'] = 'Свеза Ресурс';
    // } elseif ($officeName == 'свеза тюмень') {
    //     $prop['OFFICE'] = 'СВЕЗА Тюмень';
    // }

    // Обработка поля "Заработная плата"
    if ($prop['SALARY_VALUE'] == '-' || $prop['SALARY_VALUE'] == '') {
        $prop['SALARY_VALUE'] = '';
    } elseif ($prop['SALARY_VALUE'] == 'по договоренности') {
        $prop['SALARY_VALUE'] = '';
        $prop['SALARY_TYPE'] = 56;
    } else {
        $arSalary = explode(' ', $prop['SALARY_VALUE']);
        if ($arSalary[0] == 'от') {
            $prop['SALARY_TYPE'] = 53;
            array_splice($arSalary, 0, 1);
            $prop['SALARY_VALUE'] = implode(' ', $arSalary);
        } elseif ($arSalary[0] == 'до') {
            $prop['SALARY_TYPE'] = 54;
            array_splice($arSalary, 0, 1);
            $prop['SALARY_VALUE'] = implode(' ', $arSalary);
        } else {
            $prop['SALARY_TYPE'] = 55;
        }
    }

    // Обработка множественных полей: "Требования", "Обязанности", "Условия работы"
    $properties = ['REQUIRE', 'DUTY', 'CONDITIONS'];
    foreach ($properties as $propertyName) {
        if (stripos($prop[$propertyName], '•') !== false) {
            $prop[$propertyName] = explode('•', $prop[$propertyName]);
            array_splice($prop[$propertyName], 0, 1);
            foreach ($prop[$propertyName] as &$str) {
                $str = trim($str);
            }
        } else {
            $prop[$propertyName] = [$prop[$propertyName]]; // Преобразуем в массив, если одно значение
        }
    }

    // Автоматическое заполнение значений свойств типа "Список"
    $properties = [
        'TYPE',
        'ACTIVITY',
        'FIELD',
        'LOCATION',
        'SCHEDULE',
    ];
    foreach ($properties as $propertyName) {
        if (isset($arProps[$propertyName]) && isset($prop[$propertyName])) {
            $prop[$propertyName] = $arProps[$propertyName][$prop[$propertyName]];
        }
    }

    // Подготовка данных для добавления элемента
    $arLoadProductArray = [
        "MODIFIED_BY" => $USER->GetID(),
        "IBLOCK_SECTION_ID" => false,
        "IBLOCK_ID" => IBLOCK_ID,
        "PROPERTY_VALUES" => $prop,
        "NAME" => trim($data[3]),
        "ACTIVE" => end($data) ? 'Y' : 'N',
    ];

    // Добавление элемента в Iblock
    if ($PRODUCT_ID = $el->Add($arLoadProductArray)) {
        echo "Добавлен элемент с ID: " . $PRODUCT_ID . "<br>";
    } else {
        echo "Error: " . $el->LAST_ERROR . '<br>';
    }
}

// Закрытие CSV-файла
fclose($handle);

?>