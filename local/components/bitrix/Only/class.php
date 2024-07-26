<?php

class IblockHelper
{
    public static function getIblock(array $arParams): array
    {
        $arResult = [];

        if (is_numeric($arParams["IBLOCK_ID"])) {
            $rsIBlock = CIBlock::GetList(array(), array(
                "ACTIVE" => "Y",
                "ID" => $arParams["IBLOCK_ID"],
            ));
            $arResult = $rsIBlock->GetNext();
        } else {
            $rsIBlock = CIBlock::GetList(array(), array(
                "ACTIVE" => "Y",
                "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
            ));
            while ($arIBlock = $rsIBlock->Fetch()) {
                $arResult[] = $arIBlock;
            }
        }

        return $arResult;
    }

    public static function groupItemsByIblock(array $items): array
    {
        $groupedItems = [];

        foreach ($items as $item) {
            $itemIblockId = $item['IBLOCK_ID'];
            $groupedItems[$itemIblockId][] = $item;
        }

        return $groupedItems;
    }
}
