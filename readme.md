# Задача
Написать свой компонент на основе news.list  
1. Компонент должен уметь получать все злементы только по типу инфоблока(без ID конкретного инфоблока)  
2. Если ID инфоблока передан, компонент получает элементы только этого инфоблока(по сути, обычный news.list)  
3. Компонент должен группировать элементы в $arResult['ITEMS'] по ID инфоблоков, из которых они были получены  
4. Компонент должен иметь ООП структуру(вся логика должна быть ревлизована в class.php в виде методов)  
5. (Дополнительно) Добавить поддержку фильтрации по полям. 
6. (Дополнительно) Добавить проверку вводимых параметров и вывод ошибок через ShowError. <hr>
<h3>class.php</h3>
<b>getIblock()</b><br>
<i>component.php:226</i><br>
В оригинальном news.list получает инфоблок по переданному IBLOCK_ID и записывает в переменную $arResult,<br>
Теперь там происходит проверка, если IBLOCK_ID не передан, тогда извлекаются все инфоблки данного типа и помещаются в $arResult.<br><br>
<b>groupItemsByIblock()</b><br>
<i>component.php:525</i><br>
Все элементы в $arResult['ITEMS'] группируются по ID инфоблоков. Старые, не сгруппированные данные стираются.<br>
<h3>template.php</h3>
<b>Строчки 20-21:</b><br>
Цикл перебирает данные, сгруппированные по ID инфоблков и выводят ID на экран.
