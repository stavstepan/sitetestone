<?php

namespace Custom\ShopApi\Service;

use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;
use Bitrix\Catalog\PriceTable;

class ProductService
{
    private const IBLOCK_CODE = 'clothes';
    private int $iblockId;

    public function __construct()
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');
        $this->iblockId = $this->getIblockId();
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
     * Получить список товаров в категории
     */
    public function getProductsByCategory(int $categoryId): array
    {
        if (!$this->iblockId) {
            return [];
        }

        $products = [];
        $result = ElementTable::getList([
            'filter' => [
                'IBLOCK_ID' => $this->iblockId,
                'IBLOCK_SECTION_ID' => $categoryId,
                'ACTIVE' => 'Y',
            ],
            'select' => ['ID', 'NAME', 'CODE', 'PREVIEW_PICTURE'],
            'order' => ['SORT' => 'ASC'],
        ]);

        while ($element = $result->fetch()) {
            $products[] = $this->formatProductShort($element);
        }

        return $products;
    }

    /**
     * Получить детальную информацию о товаре
     */
    public function getProductById(int $id): ?array
    {
        if (!$this->iblockId) {
            return null;
        }

        $element = ElementTable::getList([
            'filter' => [
                'IBLOCK_ID' => $this->iblockId,
                'ID' => $id,
                'ACTIVE' => 'Y',
            ],
            'select' => ['ID', 'NAME', 'CODE', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'],
            'limit' => 1,
        ])->fetch();

        if (!$element) {
            return null;
        }

        return $this->formatProductDetail($element);
    }

    /**
     * Форматировать краткую информацию о товаре (для списка)
     */
    private function formatProductShort(array $element): array
    {
        $pictureUrl = null;
        if ($element['PREVIEW_PICTURE']) {
            $file = \CFile::GetFileArray($element['PREVIEW_PICTURE']);
            if ($file) {
                $pictureUrl = $file['SRC'];
            }
        }

        return [
            'id' => (int)$element['ID'],
            'name' => $element['NAME'],
            'detailPageUrl' => $this->getDetailPageUrl($element['CODE']),
            'picture' => $pictureUrl,
            'priceFrom' => $this->getMinPrice((int)$element['ID']),
        ];
    }

    /**
     * Форматировать детальную информацию о товаре
     */
    private function formatProductDetail(array $element): array
    {
        $gallery = $this->getProductGallery($element);
        $properties = $this->getProductProperties((int)$element['ID']);
        $offers = $this->getProductOffers((int)$element['ID']);

        return [
            'id' => (int)$element['ID'],
            'name' => $element['NAME'],
            'detailPageUrl' => $this->getDetailPageUrl($element['CODE']),
            'gallery' => $gallery,
            'properties' => $properties,
            'offers' => $offers,
        ];
    }

    /**
     * Получить галерею товара
     */
    private function getProductGallery(array $element): array
    {
        $gallery = [];

        if ($element['PREVIEW_PICTURE']) {
            $file = \CFile::GetFileArray($element['PREVIEW_PICTURE']);
            if ($file) {
                $gallery[] = $file['SRC'];
            }
        }

        if ($element['DETAIL_PICTURE']) {
            $file = \CFile::GetFileArray($element['DETAIL_PICTURE']);
            if ($file) {
                $gallery[] = $file['SRC'];
            }
        }

        // Дополнительные изображения можно получить из свойства "MORE_PHOTO"
        $morePhotos = \CIBlockElement::GetProperty(
            $this->iblockId,
            $element['ID'],
            [],
            ['CODE' => 'MORE_PHOTO']
        );

        while ($photo = $morePhotos->Fetch()) {
            if ($photo['VALUE']) {
                $file = \CFile::GetFileArray($photo['VALUE']);
                if ($file) {
                    $gallery[] = $file['SRC'];
                }
            }
        }

        return array_unique($gallery);
    }

    /**
     * Получить свойства товара (бренд, производитель, материал)
     */
    private function getProductProperties(int $productId): array
    {
        $properties = [
            'brand' => null,
            'manufacturer' => null,
            'material' => null,
        ];

        $propertyMap = [
            'BRAND' => 'brand',
            'MANUFACTURER' => 'manufacturer',
            'MATERIAL' => 'material',
        ];

        foreach ($propertyMap as $code => $key) {
            $prop = \CIBlockElement::GetProperty(
                $this->iblockId,
                $productId,
                [],
                ['CODE' => $code]
            )->Fetch();

            if ($prop && $prop['VALUE']) {
                $properties[$key] = $prop['VALUE'];
            }
        }

        return $properties;
    }

    /**
     * Получить торговые предложения (SKU)
     */
    private function getProductOffers(int $productId): array
    {
        $offers = [];

        // Получаем ID инфоблока торговых предложений
        $offerIblockId = \CCatalogSku::GetInfoByProductIBlock($this->iblockId)['IBLOCK_ID'] ?? 0;

        if (!$offerIblockId) {
            return [];
        }

        $result = ElementTable::getList([
            'filter' => [
                'IBLOCK_ID' => $offerIblockId,
                'PROPERTY_CML2_LINK' => $productId,
                'ACTIVE' => 'Y',
            ],
            'select' => ['ID', 'NAME', 'XML_ID'],
        ]);

        while ($offer = $result->fetch()) {
            $offers[] = $this->formatOffer($offer, $offerIblockId);
        }

        return $offers;
    }

    /**
     * Форматировать торговое предложение
     */
    private function formatOffer(array $offer, int $offerIblockId): array
    {
        $properties = [];
        $propertyMap = [
            'ARTNUMBER' => 'article',
            'COLOR' => 'color',
            'SIZE' => 'size',
        ];

        foreach ($propertyMap as $code => $key) {
            $prop = \CIBlockElement::GetProperty(
                $offerIblockId,
                $offer['ID'],
                [],
                ['CODE' => $code]
            )->Fetch();

            if ($prop && $prop['VALUE']) {
                $properties[$key] = $prop['VALUE'];
            }
        }

        return [
            'id' => (int)$offer['ID'],
            'name' => $offer['NAME'],
            'article' => $properties['article'] ?? $offer['XML_ID'],
            'color' => $properties['color'] ?? null,
            'size' => $properties['size'] ?? null,
        ];
    }

    /**
     * Получить минимальную цену товара
     */
    private function getMinPrice(int $productId): ?float
    {
        $minPrice = null;

        // Сначала пробуем получить цену самого товара
        $price = PriceTable::getList([
            'filter' => [
                'PRODUCT_ID' => $productId,
                'CATALOG_GROUP_ID' => 1, // Базовая цена
            ],
            'select' => ['PRICE'],
            'order' => ['PRICE' => 'ASC'],
            'limit' => 1,
        ])->fetch();

        if ($price) {
            $minPrice = (float)$price['PRICE'];
        }

        // Если цены нет, пробуем получить минимальную цену среди торговых предложений
        if ($minPrice === null) {
            $offerIblockId = \CCatalogSku::GetInfoByProductIBlock($this->iblockId)['IBLOCK_ID'] ?? 0;

            if ($offerIblockId) {
                $offers = ElementTable::getList([
                    'filter' => [
                        'IBLOCK_ID' => $offerIblockId,
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
        }

        return $minPrice;
    }

    /**
     * Получить URL детальной страницы товара
     */
    private function getDetailPageUrl(?string $code): ?string
    {
        if (!$code) {
            return null;
        }

        return '/catalog/product/' . $code . '/';
    }
}
