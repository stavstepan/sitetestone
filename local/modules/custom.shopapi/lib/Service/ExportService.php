<?php

namespace Custom\ShopApi\Service;

use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Catalog\PriceTable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Сервис для выгрузки товаров в Excel
 */
class ExportService
{
    private const IBLOCK_CODE = 'clothes';
    private int $iblockId;
    private int $offerIblockId = 0;

    public function __construct()
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');
        $this->iblockId = $this->getIblockId();
        $this->offerIblockId = $this->getOfferIblockId();
    }

    /**
     * Получить ID инфоблока по коду
     */
    private function getIblockId(): int
    {
        $result = \Bitrix\Iblock\IblockTable::getList([
            'filter' => ['CODE' => self::IBLOCK_CODE],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        return $result ? (int)$result['ID'] : 0;
    }

    /**
     * Получить ID инфоблока торговых предложений
     */
    private function getOfferIblockId(): int
    {
        if (!$this->iblockId) {
            return 0;
        }

        $info = \CCatalogSku::GetInfoByProductIBlock($this->iblockId);
        return $info['IBLOCK_ID'] ?? 0;
    }

    /**
     * Создать Excel файл с выгрузкой товаров
     *
     * @return string Путь к созданному файлу
     * @throws \Exception
     */
    public function createExcelFile(): string
    {
        if (!$this->iblockId) {
            throw new \Exception('Инфоблок не найден');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки
        $headers = [
            'ID',
            'Наименование',
            'Наименование категории',
            'Ссылка на детальную страницу товара',
            'Количество торговых предложений',
            'Минимальная цена',
        ];

        // Устанавливаем заголовки
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '1', $header);
            $column++;
        }

        // Стилизация заголовков
        $headerStyle = [
            'font' => [
                'bold' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        // Получаем товары и заполняем таблицу
        $products = $this->getProducts();
        $row = 2;

        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product['id']);
            $sheet->setCellValue('B' . $row, $product['name']);
            $sheet->setCellValue('C' . $row, $product['categoryName']);

            // Добавляем гиперссылку
            $url = 'https://example.com' . $product['detailPageUrl'];
            $sheet->setCellValue('D' . $row, $product['detailPageUrl']);
            $sheet->getCell('D' . $row)->getHyperlink()->setUrl($url);
            $sheet->getStyle('D' . $row)->getFont()->setUnderline(true)->getColor()->setRGB('0000FF');

            $sheet->setCellValue('E' . $row, $product['offersCount']);
            $sheet->setCellValue('F' . $row, $product['minPrice']);

            // Добавляем рамки
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);

            $row++;
        }

        // Автоподбор ширины колонок
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Сохраняем файл
        $fileName = 'products_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/upload/tmp/' . $fileName;

        // Создаем директорию если не существует
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }

    /**
     * Получить список товаров для выгрузки
     */
    private function getProducts(): array
    {
        $products = [];

        $result = ElementTable::getList([
            'filter' => [
                'IBLOCK_ID' => $this->iblockId,
                'ACTIVE' => 'Y',
            ],
            'select' => ['ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID'],
            'order' => ['NAME' => 'ASC'],
        ]);

        $productsByCategory = [];
        while ($element = $result->fetch()) {
            $categoryName = $this->getCategoryPath((int)$element['IBLOCK_SECTION_ID']);
            $key = $categoryName . '|' . $element['NAME'];

            $productsByCategory[$key] = [
                'id' => (int)$element['ID'],
                'name' => $element['NAME'],
                'categoryName' => $categoryName,
                'detailPageUrl' => '/catalog/product/' . $element['CODE'] . '/',
                'offersCount' => $this->getOffersCount((int)$element['ID']),
                'minPrice' => $this->getMinPrice((int)$element['ID']),
            ];
        }

        // Сортируем по ключу (категория + название)
        ksort($productsByCategory);

        return array_values($productsByCategory);
    }

    /**
     * Получить путь категории
     */
    private function getCategoryPath(int $sectionId): string
    {
        if (!$sectionId) {
            return '';
        }

        $path = [];
        $currentId = $sectionId;

        while ($currentId) {
            $section = SectionTable::getList([
                'filter' => ['ID' => $currentId],
                'select' => ['NAME', 'IBLOCK_SECTION_ID'],
                'limit' => 1,
            ])->fetch();

            if (!$section) {
                break;
            }

            array_unshift($path, $section['NAME']);
            $currentId = (int)$section['IBLOCK_SECTION_ID'];
        }

        return implode(' / ', $path);
    }

    /**
     * Получить количество торговых предложений
     */
    private function getOffersCount(int $productId): int
    {
        if (!$this->offerIblockId) {
            return 0;
        }

        $count = ElementTable::getCount([
            'IBLOCK_ID' => $this->offerIblockId,
            'PROPERTY_CML2_LINK' => $productId,
            'ACTIVE' => 'Y',
        ]);

        return $count;
    }

    /**
     * Получить минимальную цену товара
     */
    private function getMinPrice(int $productId): ?float
    {
        $minPrice = null;

        // Пробуем получить цену самого товара
        $price = PriceTable::getList([
            'filter' => [
                'PRODUCT_ID' => $productId,
                'CATALOG_GROUP_ID' => 1,
            ],
            'select' => ['PRICE'],
            'order' => ['PRICE' => 'ASC'],
            'limit' => 1,
        ])->fetch();

        if ($price) {
            $minPrice = (float)$price['PRICE'];
        }

        // Если цены нет, ищем минимальную среди торговых предложений
        if ($minPrice === null && $this->offerIblockId) {
            $offers = ElementTable::getList([
                'filter' => [
                    'IBLOCK_ID' => $this->offerIblockId,
                    'PROPERTY_CML2_LINK' => $productId,
                    'ACTIVE' => 'Y',
                ],
                'select' => ['ID'],
            ])->fetchAll();

            foreach ($offers as $offer) {
                $offerPrice = PriceTable::getList([
                    'filter' => [
                        'PRODUCT_ID' => $offer['ID'],
                        'CATALOG_GROUP_ID' => 1,
                    ],
                    'select' => ['PRICE'],
                    'order' => ['PRICE' => 'ASC'],
                    'limit' => 1,
                ])->fetch();

                if ($offerPrice) {
                    $price = (float)$offerPrice['PRICE'];
                    if ($minPrice === null || $price < $minPrice) {
                        $minPrice = $price;
                    }
                }
            }
        }

        return $minPrice;
    }

    /**
     * Отправить файл на email
     *
     * @param string $filePath Путь к файлу
     * @param string $email Email получателя
     * @return bool
     */
    public function sendEmail(string $filePath, string $email): bool
    {
        /**
         * НАСТРОЙКА ПОЧТОВОГО ШАБЛОНА:
         *
         * 1. Перейдите в административную панель Bitrix: Настройки -> Настройки продукта -> Почтовые события
         *
         * 2. Создайте новый тип почтового события:
         *    - Код: PRODUCTS_EXPORT
         *    - Название: Выгрузка товаров в Excel
         *    - Описание: Отправка файла с выгрузкой товаров
         *
         * 3. Создайте почтовый шаблон для этого типа события:
         *    - Тема письма: Выгрузка товаров #DATE#
         *    - Тело письма (HTML):
         *      Добрый день!
         *
         *      Во вложении файл с выгрузкой товаров.
         *      Дата создания: #DATE#
         *      Количество товаров: #COUNT#
         *
         *      С уважением,
         *      Администрация сайта
         *
         *    - Вложения: используйте макрос #FILE# для прикрепления файла
         *
         * 4. Доступные макросы:
         *    #EMAIL# - Email получателя
         *    #DATE# - Дата создания файла
         *    #COUNT# - Количество товаров
         *    #FILE# - Путь к файлу (для вложения)
         */

        $arEventFields = [
            'EMAIL' => $email,
            'DATE' => date('d.m.Y H:i:s'),
            'COUNT' => $this->getProductsCount(),
            'FILE' => $filePath,
        ];

        // Отправка почтового события
        $result = \CEvent::SendImmediate(
            'PRODUCTS_EXPORT',
            SITE_ID,
            $arEventFields,
            'Y',
            '',
            [$filePath]
        );

        return $result > 0;
    }

    /**
     * Получить общее количество активных товаров
     */
    private function getProductsCount(): int
    {
        return ElementTable::getCount([
            'IBLOCK_ID' => $this->iblockId,
            'ACTIVE' => 'Y',
        ]);
    }
}
