# Shop API Module для 1C-Bitrix

REST API модуль для интернет-магазина на базе 1C-Bitrix: Управление сайтом (Бизнес).

## Описание

Модуль предоставляет REST API для работы с каталогом товаров "Одежда" и консольный скрипт для выгрузки товаров в Excel с отправкой на email.

## Возможности

### REST API
- Получение списка категорий каталога с дочерними категориями
- Получение списка товаров в категории
- Получение детальной информации о товаре с характеристиками и торговыми предложениями

### Консольный скрипт
- Выгрузка всех активных товаров в Excel
- Стилизованная таблица с рамками и гиперссылками
- Отправка файла на email через почтовые события Bitrix

## Требования

- 1C-Bitrix: Управление сайтом (Бизнес) версия 20.0+
- PHP 7.4+
- Composer (для установки PhpSpreadsheet)

## Установка

### 1. Копирование файлов

Скопируйте содержимое репозитория в корень вашего сайта Bitrix:

```bash
# Структура файлов после копирования:
/local/
  /modules/
    /custom.shopapi/
  /routes/
    api.php
  /php_interface/
    init.php
  /cli/
    export_products.php
/openapi.yaml
/nginx.conf.example
/README.md
```

### 2. Установка зависимостей

Установите PhpSpreadsheet через Composer:

```bash
cd /path/to/your/site
composer require phpoffice/phpspreadsheet
```

### 3. Установка модуля

Перейдите в административную панель Bitrix:
- Marketplace -> Установленные решения
- Найдите модуль "Shop API Module"
- Нажмите "Установить"

Альтернативный способ - выполните в консоли:

```bash
php -r "require_once '/path/to/bitrix/modules/main/include/prolog_before.php'; \$module = new custom_shopapi(); \$module->DoInstall();"
```

### 4. Проверка установки

Убедитесь, что файл `local/php_interface/init.php` подключен и содержит код инициализации модуля.

## API Endpoints

### Базовый URL

```
http://your-site.com/api/v1
```

### Endpoints

#### 1. Получить список категорий

```
GET /api/v1/categories
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "Мужская одежда",
      "detailPageUrl": "/catalog/mens-clothing/",
      "picture": "/upload/iblock/abc/category.jpg",
      "children": [
        {
          "id": 124,
          "name": "Рубашки",
          "detailPageUrl": "/catalog/mens-clothing/shirts/",
          "picture": "/upload/iblock/def/category.jpg"
        }
      ]
    }
  ]
}
```

#### 2. Получить список товаров в категории

```
GET /api/v1/products/category/{categoryId}
```

**Параметры:**
- `categoryId` (integer) - ID категории

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 456,
      "name": "Рубашка мужская",
      "detailPageUrl": "/catalog/product/shirt-mens/",
      "picture": "/upload/iblock/ghi/product.jpg",
      "priceFrom": 2990.00
    }
  ]
}
```

#### 3. Получить детальную информацию о товаре

```
GET /api/v1/products/{id}
```

**Параметры:**
- `id` (integer) - ID товара

**Ответ:**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "name": "Рубашка мужская",
    "detailPageUrl": "/catalog/product/shirt-mens/",
    "gallery": [
      "/upload/iblock/ghi/product1.jpg",
      "/upload/iblock/ghi/product2.jpg"
    ],
    "properties": {
      "brand": "Nike",
      "manufacturer": "Nike Inc.",
      "material": "Хлопок 100%"
    },
    "offers": [
      {
        "id": 789,
        "name": "Рубашка мужская, синий, L",
        "article": "SHIRT-BLUE-L",
        "color": "Синий",
        "size": "L"
      }
    ]
  }
}
```

## Консольный скрипт выгрузки

### Настройка почтового шаблона

Перед использованием скрипта необходимо настроить почтовое событие в Bitrix:

1. Перейдите в **Настройки -> Настройки продукта -> Почтовые события**

2. Создайте новый **тип почтового события**:
   - **Код:** `PRODUCTS_EXPORT`
   - **Название:** Выгрузка товаров в Excel
   - **Описание:** Отправка файла с выгрузкой товаров

3. Создайте **почтовый шаблон**:
   - **Тема письма:** `Выгрузка товаров #DATE#`
   - **Тело письма (HTML):**
     ```html
     Добрый день!

     Во вложении файл с выгрузкой товаров.
     Дата создания: #DATE#
     Количество товаров: #COUNT#

     С уважением,
     Администрация сайта
     ```
   - **Вложения:** Файл будет прикреплен автоматически

4. Доступные макросы:
   - `#EMAIL#` - Email получателя
   - `#DATE#` - Дата создания файла
   - `#COUNT#` - Количество товаров

### Использование скрипта

```bash
cd /path/to/your/site
php local/cli/export_products.php email@example.com
```

**Параметры:**
- `email@example.com` - email адрес для отправки файла

**Пример вывода:**
```
Начало выгрузки товаров...
Создание Excel файла...
Файл создан: /path/to/upload/tmp/products_export_2024-12-24_14-30-00.xlsx
Отправка файла на email: email@example.com...
Файл успешно отправлен на email: email@example.com
Выгрузка завершена успешно!
```

### Структура Excel файла

Файл содержит следующие колонки:

| ID  | Наименование | Наименование категории | Ссылка на детальную страницу | Количество ТП | Минимальная цена |
|-----|--------------|------------------------|------------------------------|---------------|------------------|
| 123 | Рубашка      | Одежда / Рубашки      | /catalog/product/shirt/      | 5             | 2990.00          |

**Особенности:**
- Все ячейки имеют рамки
- Заголовки выделены жирным шрифтом
- Ссылки в колонке "Ссылка на детальную страницу" кликабельны
- Товары отсортированы по категории и названию

### Автоматизация выгрузки

Для автоматической выгрузки можно настроить cron:

```bash
# Ежедневная выгрузка в 9:00
0 9 * * * php /path/to/site/local/cli/export_products.php manager@example.com
```

## Настройка nginx (опционально)

Если используете nginx, можно добавить специальную конфигурацию для API endpoints.
Пример конфигурации находится в файле `nginx.conf.example`.

Добавьте содержимое файла в секцию `server {}` вашего конфига nginx:

```nginx
include /path/to/site/nginx.conf.example;
```

## Структура модуля

```
local/modules/custom.shopapi/
├── install/
│   └── index.php                 # Установщик модуля
├── lib/
│   ├── Controller/
│   │   ├── CategoryController.php    # Контроллер категорий
│   │   └── ProductController.php     # Контроллер товаров
│   └── Service/
│       ├── CategoryService.php       # Сервис работы с категориями
│       ├── ProductService.php        # Сервис работы с товарами
│       └── ExportService.php         # Сервис выгрузки в Excel
├── include.php                   # Подключение автозагрузки
└── .description.php             # Описание модуля
```

## OpenAPI спецификация

Полная спецификация API доступна в файле `openapi.yaml` в корне репозитория.

Вы можете использовать его с инструментами:
- [Swagger UI](https://swagger.io/tools/swagger-ui/)
- [Swagger Editor](https://editor.swagger.io/)
- [Postman](https://www.postman.com/)

## Важные замечания

### Код инфоблока

По умолчанию модуль работает с инфоблоком с кодом `clothes` (Одежда).

Если ваш инфоблок имеет другой код, измените константу `IBLOCK_CODE` в следующих файлах:
- `local/modules/custom.shopapi/lib/Service/CategoryService.php`
- `local/modules/custom.shopapi/lib/Service/ProductService.php`
- `local/modules/custom.shopapi/lib/Service/ExportService.php`

### Коды свойств

Модуль ожидает следующие коды свойств товаров:
- `BRAND` - бренд
- `MANUFACTURER` - производитель
- `MATERIAL` - материал
- `MORE_PHOTO` - дополнительные фотографии

Для торговых предложений:
- `CML2_LINK` - связь с товаром
- `ARTNUMBER` - артикул
- `COLOR` - цвет
- `SIZE` - размер

Если коды отличаются, измените их в соответствующих сервисах.

### URL детальных страниц

URL формируются по шаблонам:
- Категории: `/catalog/{CODE}/`
- Товары: `/catalog/product/{CODE}/`

Если ваши URL отличаются, измените методы:
- `CategoryService::getDetailPageUrl()`
- `ProductService::getDetailPageUrl()`

## Тестирование

### Примеры запросов с curl

```bash
# Получить список категорий
curl -X GET http://your-site.com/api/v1/categories

# Получить товары категории с ID 10
curl -X GET http://your-site.com/api/v1/products/category/10

# Получить товар с ID 100
curl -X GET http://your-site.com/api/v1/products/100
```

## Поддержка

При возникновении проблем проверьте:
1. Установлен ли модуль в Bitrix
2. Подключен ли файл `local/php_interface/init.php`
3. Установлена ли библиотека PhpSpreadsheet
4. Правильно ли настроены коды инфоблока и свойств
5. Настроено ли почтовое событие `PRODUCTS_EXPORT`

## Лицензия

Тестовое задание

## Автор

Тестовое задание
