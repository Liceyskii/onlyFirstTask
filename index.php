<?php

use Laminas\Diactoros\Request;

require_once __DIR__.'/vendor/autoload.php';

$token = 'y0_AgAAAAAoIbGBAAw28QAAAAEMscN-AAD7Bgni5khKGJyKcSYcIhOoJKIB_g';

$disk = new Arhitector\Yandex\Disk($token);

// Инициализируем основные функции CRUD интерфейса
function previewFile($path, $disk) {
    $files = glob(__DIR__ . '/download/*');
    foreach ($files as $f) {
        if (is_file($f)) {
            unlink($f);
        }
    }
    $selectedFile = $disk->getResource($path);
    $selectedFile->download(__DIR__ . '/download/' . $selectedFile['name']);
    header('Location: /download/' . $selectedFile['name']);
    exit;
}

function deleteFile($path, $disk) {
    $selectedFile = $disk->getResource($path);
    $selectedFile->delete();
    header('Location: /');
    exit;
}

function uploadFile($file, $disk) {
    $resource = $disk->getResource($file['name']);
    $resource->upload($file['tmp_name'], true);
    $_FILES = array();
    $_POST = array();
    echo 'Файл успешно загружен!';
}

// Вызываем функции
if (isset($_GET['preview'])) {
    previewFile($_GET['preview'], $disk);
} elseif (isset($_GET['delete'])) {
    deleteFile($_GET['delete'], $disk);
} elseif (isset($_FILES['upload'])) {
    uploadFile($_FILES['upload'], $disk);
}

// Получаем 5 файлов с отступом в соответствии с настройками пагинации
if (isset($_GET['page'])) {
    $offset = ($_GET['page'] - 1) * 5;
    $response = $disk->getResources()->setLimit(5, $offset)->setSort('modified', true);
} else {
    $response = $disk->getResources()->setLimit(5)->setSort('modified', true);
}

$count = $disk->getResources()->setLimit(500);
$count = $count->count();

// Форматируем полученные данные, отрисовываем шаблон
$resources = array();

foreach ($response as $r) {
    $file = $r->toArray(['name', 'size', 'path']);
    $file['size'] = round($file['size'] / 1048576, 2) . ' МБ';
    $resources[] = $file;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CRUD</title>
</head>
<body style="text-align: center">
    <h1>Яндекс Диск</h1><br><hr>

    <h2>Загрузка файла</h2>
    <form method="POST" action="index.php" enctype="multipart/form-data">
        <input type="file" name="upload">
        <button type="submit">Загрузить</button>
    </form><br><br><hr>

    <h2>Список файлов</h2>
<table border="1" style="width: 100%">
  <thead>
    <tr>
      <th>Название</th>
      <th>Размер</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($resources as $resource) { ?>
        <tr>
            <td><a href="?preview=<?=$resource['path']?>"><?=$resource['name']?></a></td>
            <td><?=$resource['size']?></td>
            <td><a href="?delete=<?=$resource['path']?>">Удалить</a></td>
        </tr>
    <?php } ?>
  </tbody>
</table><br><br>

<div class="pagination">
    <a href="/" class="page-link">Начало</a>
    <?php for ($page = 1; $page - 1 <= $count / 5; $page++) { ?>
        <a href="/?page=<?=$page?>" class="page-link"><?=$page?></a>
    <?php } ?>
    <?php if (isset($_GET['page']) && $_GET['page'] <= $count / 5) { ?>
        <a href="/?page=<?=$_GET['page'] + 1?>" class="page-link">Следующая</a>
    <?php } ?>
</div>
</body>
</html>