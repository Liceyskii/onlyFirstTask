<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$this->addExternalCss("/local/templates/landing24/components/bitrix/form.result.new/custom_form/css/common.css");
/*if ($arResult["isFormErrors"] == "Y"):?><?=$arResult["FORM_ERRORS_TEXT"];?><?endif;*/?>
<?=$arResult["FORM_NOTE"]?>
<?if ($arResult["isFormNote"] != "Y")
{
?>
<div class="contact-form">
    <div class="contact-form__head">
        <div class="contact-form__head-title"><?=$arResult["FORM_TITLE"]?></div>
        <div class="contact-form__head-text"><?=$arResult["FORM_DESCRIPTION"]?></div>
    </div>
    <?=str_replace('<form', '<form class="contact-form__form"', $arResult["FORM_HEADER"])?>
        <div class="contact-form__form-inputs">
            <div class="input contact-form__input"><label class="input__label" for="NAME">
                <div class="input__label-text">Ваше имя*</div>
                       <?=str_replace('<input', '<input class="input__input"', $arResult["QUESTIONS"]["NAME"]["HTML_CODE"])?>
                <?if(strpos($arResult["FORM_ERRORS_TEXT"], 'Ваше имя')):?>
                    <div class="input__notification">Поле должно содержать не менее 3-х символов</div>
                <?endif;?>
            </label></div>
            <div class="input contact-form__input"><label class="input__label" for="COMPANY">
                <div class="input__label-text">Компания/Должность*</div>
                <?=str_replace('<input', '<input class="input__input"', $arResult["QUESTIONS"]["COMPANY"]["HTML_CODE"])?>
                <?if(strpos($arResult["FORM_ERRORS_TEXT"], 'Компания/Должность')):?>
                    <div class="input__notification">Поле должно содержать не менее 3-х символов</div>
                <?endif;?>
            </label></div>
            <div class="input contact-form__input"><label class="input__label" for="EMAIL">
                <div class="input__label-text">Email*</div>
                <?=str_replace('<input', '<input class="input__input"', $arResult["QUESTIONS"]["EMAIL"]["HTML_CODE"])?>
                <?if(is_array($arResult["FORM_ERRORS"]) && array_key_exists("COMPANY", $arResult["FORM_ERRORS"])):?>
                    <div class="input__notification">Неверный формат почты</div>
                <?endif;?>
            </label></div>
            <div class="input contact-form__input"><label class="input__label" for="PHONE">
                <div class="input__label-text">Номер телефона*</div>
                <?=str_replace('<input', '<input class="input__input" type="tel"
                       data-inputmask="\'mask\': \'+79999999999\', \'clearIncomplete\': \'true\'" maxlength="12"
                       x-autocompletetype="phone-full"', $arResult["QUESTIONS"]["PHONE"]["HTML_CODE"])?>
            </label></div>
        </div>
        <div class="contact-form__form-message">
            <div class="input"><label class="input__label" for="MESSAGE">
                <div class="input__label-text">Сообщение</div>
                <?=str_replace('<textarea', '<textarea class="input__input"', $arResult["QUESTIONS"]["MESSAGE"]["HTML_CODE"])?>
                <div class="input__notification"></div>
            </label></div>
        </div>
        <div class="contact-form__bottom">
            <div class="contact-form__bottom-policy">Нажимая &laquo;Отправить&raquo;, Вы&nbsp;подтверждаете, что
                ознакомлены, полностью согласны и&nbsp;принимаете условия &laquo;Согласия на&nbsp;обработку персональных
                данных&raquo;.
                </div>
            <input class="form-button contact-form__bottom-button" <?=(intval($arResult["F_RIGHT"]) < 10 ? "disabled=\"disabled\"" : "");?> type="submit" name="web_form_submit" data-success="Отправлено" data-error="Ошибка отправки" value="<?=htmlspecialcharsbx(trim($arResult["arForm"]["BUTTON"]) == '' ? GetMessage("FORM_ADD") : $arResult["arForm"]["BUTTON"]);?>" />
        </div>
    <?=$arResult["FORM_FOOTER"]?>
</div>

<?
} //endif (isFormNote)