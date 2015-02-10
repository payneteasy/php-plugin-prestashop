# Prestashop 1.6 Module for PaynetEasy Payment Gateway

## Implemented Functionality

This module allows processing payment via [Merchant PaynetEasy API](http://wiki.payneteasy.com/index.php/PnE:Merchant_API). The following payment methods are currently implemented:
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

## System Requirements

* PHP 5.3 - 5.5
* [curl extension](http://php.net/manual/en/book.curl.php)
* [Prestashop](http://www.prestashop.com/en/download) 1.6.x (the module has been tested with version 1.6.0.9)

## <a name="get_package"></a> Download package containing module

### Bulding package manually
1. [Install composer](http://getcomposer.org/doc/00-intro.md), if it is not installed
2. Clone module source code: `composer create-project payneteasy/php-plugin-prestashop --stability=dev --prefer-dist`
3. Go to module source code directory: `cd php-plugin-prestashop`
4. Pack the module into archiver: `composer archive --format=zip`

## Install, Configure and Remove Module Instruction

* [Install Module](01-installation.md)
* [Configure Module](02-configuration.md)
* [Remove Module](03-uninstalling.md)
