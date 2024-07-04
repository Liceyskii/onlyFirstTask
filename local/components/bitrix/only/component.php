<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

// Константы для кодов инфоблоков
define('IBLOCK_CARS_CODE', 'cars');
define('IBLOCK_TRIPS_CODE', 'trips');

// Функция для поиска ID инфоблока по коду
function getIBlockIdByIBlockCode($code) {
    global $APPLICATION; 

    if (!CModule::IncludeModule('iblock')) {
        $APPLICATION->AuthForm("");
        die();
    }

    $iblockId = false;
    $arIBlock = CIBlock::GetList(array(), array('CODE' => $code));
    while ($arIBlock = $arIBlock->GetNext()) {
        $iblockId = $arIBlock['ID'];
        break; // Выходим из цикла, как только найдем нужный инфоблок
    }

    if (!$iblockId) {
        echo "Инфоблок '$code' не найден";
        die();
    }

    return $iblockId;
}

// Функция для получения информации о водителе
function getDriverInfo($driverId) {
    $driver = CIBlockElement::GetByID($driverId)->Fetch();
    $driverName = CIBlockElement::GetProperty($driver['IBLOCK_ID'], $driverId, ['sort' => 'asc'], ['CODE' => 'NAME'])->Fetch();
    $driverLastName = CIBlockElement::GetProperty($driver['IBLOCK_ID'], $driverId, ['sort' => 'asc'], ['CODE' => 'LAST_NAME'])->Fetch();
    $driverContactNumber = CIBlockElement::GetProperty($driver['IBLOCK_ID'], $driverId, ['sort' => 'asc'], ['CODE' => 'CONTACT_NUMBER'])->Fetch();

    return [
        'NAME' => $driverName['VALUE'],
        'LAST_NAME' => $driverLastName['VALUE'],
        'CONTACT_NUMBER' => $driverContactNumber['VALUE'],
    ];
}

// Функция для получения информации о категории комфорта
function getComfortCategoryInfo($comfortCategoryId) {
    $comfortCategory = CIBlockElement::GetByID($comfortCategoryId)->Fetch();
    $comfortCategoryName = CIBlockElement::GetProperty($comfortCategory['IBLOCK_ID'], $comfortCategoryId, ['sort' => 'asc'], ['CODE' => 'NAME'])->Fetch();
    return $comfortCategoryName['VALUE'];
}

// Функция для получения информации о модели автомобиля
function getModelInfo($carId) {
    $car = CIBlockElement::GetByID($carId)->Fetch(); 
    $model = CIBlockElement::GetProperty($car['IBLOCK_ID'], $carId, ['sort' => 'asc'], ['CODE' => 'MODEL'])->Fetch();
    return $model['VALUE'];
}

// Функция для проверки доступности автомобиля
function isCarAvailable($arElement, $arResult) {
    $isCarUsed = false;
    foreach ($arResult['TRIPS'] as $arTrip) {
        if ($arTrip['PROPERTY_CAR_MODEL'] === $arElement['MODEL'] &&
            (strtotime($arResult['TRIP_START']) <= strtotime($arTrip['PROPERTY_END_TIME_VALUE']) && strtotime($arResult['TRIP_END']) >= strtotime($arTrip['PROPERTY_START_TIME_VALUE']))
        ) {
            $isCarUsed = true;
            break;
        }
    }
    return !$isCarUsed;
}

// Функция для обхода всех выбранных элементов и формирования массива данных
function processSelectedElements($rsIBlockElements, $userComfortCategoryId, $arResult) {
    $elements = [];

    while ($arElement = $rsIBlockElements->NavNext(false)) {
        $arElement = htmlspecialcharsex($arElement);

        $comfortCategoryId = $arElement['PROPERTY_CATEGORY_COMFORT_VALUE'];

        if ($comfortCategoryId == $userComfortCategoryId) {
            $comfortCategory = CIBlockElement::GetByID($comfortCategoryId)->Fetch();
            $car = CIBlockElement::GetByID($arElement['ID'])->Fetch();

            $driverId = $arElement['PROPERTY_DRIVER_VALUE'];
            $driver = CIBlockElement::GetByID($driverId)->Fetch();

            $driverInfo = getDriverInfo($driverId);
            $comfortCategoryName = getComfortCategoryInfo($comfortCategoryId);
            $model = getModelInfo($arElement['ID']);

            $elements[] = [
                'MODEL' => $model,
                'CATEGORY_COMFORT' => $comfortCategoryName,
                'DRIVER_NAME' => $driverInfo['NAME'],
                'DRIVER_LAST_NAME' => $driverInfo['LAST_NAME'],
                'DRIVER_CONTACT_NUMBER' => $driverInfo['CONTACT_NUMBER'],
            ];
            
        }
    }

    return $elements;
}

// Проверяем, подключен ли модуль iblock
if (CModule::IncludeModule('iblock')) {
    // Получаем ID инфоблоков с помощью функции
    $iblockCarsId = getIBlockIdByIBlockCode(IBLOCK_CARS_CODE);
    $iblockTripsId = getIBlockIdByIBlockCode(IBLOCK_TRIPS_CODE);

    // Выбираем элементы инфоблока "Автомобили" с указанием свойств
    $rsIBlockElements = CIBlockElement::GetList(
        ['SORT' => 'ASC'], // Сортировка по полю SORT в порядке возрастания
        [
            'IBLOCK_TYPE' => 'cars', // Тип инфоблока
            'IBLOCK_ID' => $iblockCarsId, // ID инфоблока
            'SHOW_NEW' => 'Y' // Показывать новые элементы
        ],
        false,
        false,
        ['ID', 'NAME', 'PROPERTY_CATEGORY_COMFORT', 'PROPERTY_DRIVER', 'PROPERTY_MODEL'] // Выбираемые поля
    );

    // Инициализируем массив $arResult["ELEMENTS"]
    $arResult['ELEMENTS'] = [];

    // Получаем ID пользователя
    $userId = $USER->GetID();
    
    // Получаем информацию о пользователе
    $user = CUser::GetByID($userId)->Fetch();
    
    // Получаем значение поля "Должность"
    $userPositionId = $user['UF_POSITION'];

    // Получаем информацию о должности по ID
    $position = CIBlockElement::GetByID($userPositionId)->Fetch();
    
    // Получаем значение свойства "NAME" должности
    $positionName = CIBlockElement::GetProperty($position['IBLOCK_ID'], $userPositionId, ['sort' => 'asc'], ['CODE' => 'NAME'])->Fetch();
    $positionComfortCategoriesId = CIBlockElement::GetProperty($position['IBLOCK_ID'], $userPositionId, ['sort' => 'asc'], ['CODE' => 'AVAILABLE_COMFORT_CATEGORIES'])->Fetch();
    $positionComfortCategories = CIBlockElement::GetByID($positionComfortCategoriesId)->Fetch();
    
    // Получаем ID категории комфорта для пользователя
    $userComfortCategoryId = $positionComfortCategories['ID'];
    
    // Получаем актуальные поездки
    $rsTrips = CIBlockElement::GetList(
        ['START_TIME' => 'ASC'], // Сортировка по полю START_TIME в порядке возрастания
        [
            'IBLOCK_TYPE' => 'trips', // Тип инфоблока "trips"
            'IBLOCK_ID' => $iblockTripsId, // ID инфоблока "trips"
            '!PROPERTY_END_TIME' => false, // Поездки не должны быть завершены
            '>PROPERTY_START_TIME' => date('Y-m-d H:i:s') // Поездки должны быть в будущем
        ],
        false,
        false,
        ['ID', 'PROPERTY_START_TIME', 'PROPERTY_END_TIME', 'PROPERTY_CAR'] // Выбираемые поля
    );
    
    $arResult['TRIPS'] = [];

    // Проходим по поездкам
    while ($arTrip = $rsTrips->NavNext(false)) {
        // Получаем информацию о автомобиле для поездки
        $carId = $arTrip['PROPERTY_CAR_VALUE'];
        $car = CIBlockElement::GetByID($carId)->Fetch();
        
        // Получаем значение свойства "MODEL"
        $model = CIBlockElement::GetProperty($car['IBLOCK_ID'], $carId, ['sort' => 'asc'], ['CODE' => 'MODEL'])->Fetch();
        
        // Добавляем модель автомобиля в данные о поездке
        $arTrip['PROPERTY_CAR_MODEL'] = $model['VALUE'];
        $arResult['TRIPS'][] = $arTrip;
    }
    
    // Проходим по всем выбранным элементам
    $arResult['ELEMENTS'] = processSelectedElements($rsIBlockElements, $userComfortCategoryId, $arResult);

    // Сохраняем информацию о должности
    $arResult['POSITION'] = $positionName['VALUE'];
    $arResult['AVAILABLE_COMFORT_CATEGORIES'] = $positionComfortCategories['NAME'];

    // Получаем значения TRIP_START и TRIP_END из GET-запроса
    $tripStart = $_GET['TRIP_START'];
    $tripEnd = $_GET['TRIP_END'];

    if (isset($tripStart) && isset($tripEnd)) {
        $arResult['TRIP_START'] = $tripStart;
        $arResult['TRIP_END'] = $tripEnd;
    }

    // Проверка доступности автомобилей
    $arResult['AVAILABLE_CARS'] = [];
    foreach ($arResult['ELEMENTS'] as $arElement) {
        if (isCarAvailable($arElement, $arResult)) {
            $arResult['AVAILABLE_CARS'][] = $arElement;
        }
    }
    ?>

    <!-- Рендерим результат -->
    <h2>Список автомобилей</h2>
    <ul>
        <?php foreach ($arResult['AVAILABLE_CARS'] as $arElement): ?>
            <li>
                <b>Модель:</b> <?= $arElement['MODEL'] ?>
                <br>
                <b>Категория комфорта:</b> <?= $arElement['CATEGORY_COMFORT'] ?>
                <br>
                <b>Водитель:</b> <?= $arElement['DRIVER_LAST_NAME'] ?> <?= $arElement['DRIVER_NAME'] ?>
                (<?= $arElement['DRIVER_CONTACT_NUMBER'] ?>)
            </li>
        <?php endforeach; ?>
    </ul>

    <hr>

    <?php if (!empty($arResult["TRIPS"])): ?>
        <h2>Актуальные поездки</h2>
        <ul>
            <?php foreach ($arResult["TRIPS"] as $arTrip): ?>
                <li>
                    <b>Автомобиль:</b> <?= $arTrip['PROPERTY_CAR_MODEL'] ?>
                    <br>
                    <b>Начало:</b> <?= $arTrip['PROPERTY_START_TIME_VALUE'] ?>
                    <br>
                    <b>Конец:</b> <?= $arTrip['PROPERTY_END_TIME_VALUE'] ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Актуальных поездок нет.</p>
    <?php endif; ?>

<?php
} else {
    $APPLICATION->AuthForm("");
}
?>