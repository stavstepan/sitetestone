<?php

namespace Custom\ShopApi\Service;

use Bitrix\Main\Loader;
use Bitrix\Iblock\SectionTable;

class CategoryService
{
    private const IBLOCK_CODE = 'clothes'; // Код инфоблока "Одежда"
    private int $iblockId;

    public function __construct()
    {
        Loader::includeModule('iblock');
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
     * Получить список активных категорий
     */
    public function getCategories(): array
    {
        if (!$this->iblockId) {
            return [];
        }

        $categories = [];
        $result = SectionTable::getList([
            'filter' => [
                'IBLOCK_ID' => $this->iblockId,
                'ACTIVE' => 'Y',
                'DEPTH_LEVEL' => 1, // Только категории первого уровня
            ],
            'select' => ['ID', 'NAME', 'CODE', 'PICTURE', 'SORT'],
            'order' => ['SORT' => 'ASC'],
        ]);

        while ($section = $result->fetch()) {
            $categories[] = $this->formatCategory($section);
        }

        return $categories;
    }

    /**
     * Получить категорию по ID
     */
    public function getCategoryById(int $id): ?array
    {
        if (!$this->iblockId) {
            return null;
        }

        $section = SectionTable::getList([
            'filter' => [
                'IBLOCK_ID' => $this->iblockId,
                'ID' => $id,
                'ACTIVE' => 'Y',
            ],
            'select' => ['ID', 'NAME', 'CODE', 'PICTURE', 'SORT'],
            'limit' => 1,
        ])->fetch();

        if (!$section) {
            return null;
        }

        return $this->formatCategory($section);
    }

    /**
     * Получить дочерние категории
     */
    private function getChildCategories(int $parentId): array
    {
        $children = [];
        $result = SectionTable::getList([
            'filter' => [
                'IBLOCK_ID' => $this->iblockId,
                'IBLOCK_SECTION_ID' => $parentId,
                'ACTIVE' => 'Y',
            ],
            'select' => ['ID', 'NAME', 'CODE', 'PICTURE', 'SORT'],
            'order' => ['SORT' => 'ASC'],
        ]);

        while ($section = $result->fetch()) {
            $children[] = $this->formatCategory($section, false);
        }

        return $children;
    }

    /**
     * Форматировать данные категории
     */
    private function formatCategory(array $section, bool $includeChildren = true): array
    {
        $pictureUrl = null;
        if ($section['PICTURE']) {
            $file = \CFile::GetFileArray($section['PICTURE']);
            if ($file) {
                $pictureUrl = $file['SRC'];
            }
        }

        $category = [
            'id' => (int)$section['ID'],
            'name' => $section['NAME'],
            'detailPageUrl' => $this->getDetailPageUrl($section['CODE']),
            'picture' => $pictureUrl,
        ];

        if ($includeChildren) {
            $category['children'] = $this->getChildCategories((int)$section['ID']);
        }

        return $category;
    }

    /**
     * Получить URL детальной страницы категории
     */
    private function getDetailPageUrl(?string $code): ?string
    {
        if (!$code) {
            return null;
        }

        return '/catalog/' . $code . '/';
    }
}
