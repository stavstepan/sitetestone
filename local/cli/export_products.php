<?php
/**
 * Консольный скрипт для выгрузки товаров в Excel
 *
 * Использование:
 * php local/cli/export_products.php email@example.com
 *
 * Аргументы:
 * - email (обязательный) - email для отправки файла
 */

// Определяем корневую директорию
$_SERVER['DOCUMENT_ROOT'] = dirname(dirname(__DIR__));
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

// Подключаем ядро Bitrix
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Custom\ShopApi\Service\ExportService;

// Проверяем аргументы командной строки
if ($argc < 2) {
    echo "Ошибка: не указан email получателя\n";
    echo "Использование: php " . $argv[0] . " email@example.com\n";
    exit(1);
}

$email = $argv[1];

// Валидация email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Ошибка: некорректный email адрес\n";
    exit(1);
}

try {
    // Подключаем модуль
    if (!Loader::includeModule('custom.shopapi')) {
        throw new \Exception('Модуль custom.shopapi не установлен');
    }

    echo "Начало выгрузки товаров...\n";

    // Создаем сервис экспорта
    $exportService = new ExportService();

    // Создаем Excel файл
    echo "Создание Excel файла...\n";
    $filePath = $exportService->createExcelFile();
    echo "Файл создан: " . $filePath . "\n";

    // Отправляем на email
    echo "Отправка файла на email: " . $email . "...\n";
    $result = $exportService->sendEmail($filePath, $email);

    if ($result) {
        echo "Файл успешно отправлен на email: " . $email . "\n";
    } else {
        echo "Ошибка при отправке файла на email\n";
        echo "Файл сохранен локально: " . $filePath . "\n";
        exit(1);
    }

    // Удаляем временный файл (опционально)
    // unlink($filePath);

    echo "Выгрузка завершена успешно!\n";
    exit(0);

} catch (\Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
    echo "Трассировка:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
