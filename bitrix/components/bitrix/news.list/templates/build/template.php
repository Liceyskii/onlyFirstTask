<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>
<div id="barba-wrapper">
<div class="article-list">
    <?if($arParams["DISPLAY_TOP_PAGER"]):?>
        <?=$arResult["NAV_STRING"]?><br />
    <?endif;?>
    <?foreach($arResult["ITEMS"] as $arItem):?>
		<a class="article-item article-list__item" href="<?=$arItem["DETAIL_PAGE_URL"]?>" data-anim="anim-3" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
			<div class="article-item__background"><img src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" data-src="xxxHTMLLINKxxx0.39186223192351520.41491856731872767xxx" alt=""/></div>
			<div class="article-item__wrapper">
				<div class="article-item__title"><?=$arItem["NAME"]?></div>
				<div class="article-item__content"><?=$arItem["PREVIEW_TEXT"]?></div>
			</div>
		</a>
    <?endforeach;?>
    <?if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
        <br /><?=$arResult["NAV_STRING"]?>
    <?endif;?>
</div>
</div>