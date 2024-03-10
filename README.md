# Funpay - тестовое задание

Написать функцию формирования sql-запросов (MySQL) из шаблона и значений параметров.

Места вставки значений в шаблон помечаются вопросительным знаком, после которого может следовать спецификатор
преобразования.

Спецификаторы:

* `?d` - конвертация в целое число
* `?f` - конвертация в число с плавающей точкой
* `?a` - массив значений
* `?#` - идентификатор или массив идентификаторов

Если спецификатор не указан, то используется тип переданного значения, но допускаются только
типы `string`, `int`, `float`, `bool` (приводится к `0` или `1`) и `null`.
Параметры `?`, `?d`, `?f` могут принимать значения `null` (в этом случае в шаблон вставляется `NULL`).
Строки и идентификаторы автоматически экранируются.

Массив (параметр `?a`) преобразуется либо в список значений через запятую (список), либо в пары идентификатор и значение
через запятую (ассоциативный массив).
Каждое значение из массива форматируется в зависимости от его типа (идентично универсальному параметру без
спецификатора).

Также необходимо реализовать условные блоки, помечаемые фигурными скобками.
Если внутри условного блока есть хотя бы один параметр со специальным значением, то блок не попадает в сформированный
запрос.
Специальное значение возвращается методом `skip`.
Условные блоки не могут быть вложенными.

При ошибках в шаблонах или значениях выбрасывать исключения.

Файлы:

* В файле `Database.php` находится заготовка класса с заглушками в виде исключений.
* В файле `DatabaseTest.php` находятся примеры.
* В файле `test.php` находится скрипт для проверки решения.

# Запуск тестов

Указываем в файле `test.php` параметры подключения к базе данных и запускаем:

```
php test.php
```
