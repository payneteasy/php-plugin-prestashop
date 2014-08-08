# Module for Prestashop 1.6 for pay by PaynetEasy

## Доступная функциональность

Данный  модуль позволяет производить оплату с помощью [merchant PaynetEasy API](http://wiki.payneteasy.com/index.php/PnE:Merchant_API). На текущий момент реализованы следующие платежные методы:
- [x] [Sale Transactions](http://wiki.payneteasy.com/index.php/PnE:Sale_Transactions)
- [ ] [Preauth/Capture Transactions](http://wiki.payneteasy.com/index.php/PnE:Preauth/Capture_Transactions)
- [ ] [Transfer Transactions](http://wiki.payneteasy.com/index.php/PnE:Transfer_Transactions)
- [ ] [Return Transactions](http://wiki.payneteasy.com/index.php/PnE:Return_Transactions)
- [ ] [Recurrent Transactions](http://wiki.payneteasy.com/index.php/PnE:Recurrent_Transactions)
- [ ] [Payment Form Integration](http://wiki.payneteasy.com/index.php/PnE:Payment_Form_integration)
- [ ] [Buy Now Button integration](http://wiki.payneteasy.com/index.php/PnE:Buy_Now_Button_integration)
- [ ] [eCheck integration](http://wiki.payneteasy.com/index.php/PnE:eCheck_integration)
- [ ] [Western Union Integration](http://wiki.payneteasy.com/index.php/PnE:Western_Union_Integration)
- [ ] [Bitcoin Integration](http://wiki.payneteasy.com/index.php/PnE:Bitcoin_integration)
- [ ] [Loan Integration](http://wiki.payneteasy.com/index.php/PnE:Loan_integration)
- [ ] [Qiwi Integration](http://wiki.payneteasy.com/index.php/PnE:Qiwi_integration)
- [ ] [Merchant Callbacks](http://wiki.payneteasy.com/index.php/PnE:Merchant_Callbacks)

## Системные требования

* PHP 5.3 - 5.5
* [Расширение curl](http://php.net/manual/en/book.curl.php)
* [Prestashop](http://www.prestashop.com/en/download) 1.6.x (модуль тестировался с версией 1.6.0.9)

## <a name="get_package"></a> Получение пакета с модулем

### Самостоятельная сборка пакета
1. [Установите composer](http://getcomposer.org/doc/00-intro.md), если его еще нет
2. Клонируйте репозиторий с модулем: `composer create-project payneteasy/php-plugin-prestashop --stability=dev --prefer-dist`
3. Перейдите в папку модуля: `cd php-plugin-prestashop`
4. Упакуйте модуль в архив: `composer archive --format=zip`

## Установка, настройка, удаление модуля

* [Установка модуля](01-installation.md)
* [Настройка модуля](02-configuration.md)
* [Удаление модуля](03-uninstalling.md)