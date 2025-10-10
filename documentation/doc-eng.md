# PaynetEasy Payment Plugin for Prestashop 1.6

# 1. [Requirements](https://github.com/annihilatoratm/prestashop-doc/blob/main/documentation/doc-eng.md#1-requirements-1)
# 2. [Functionality](https://github.com/annihilatoratm/prestashop-doc/blob/main/documentation/doc-eng.md#2-functionality-1)
# 3. [Installation Steps](https://github.com/annihilatoratm/prestashop-doc/blob/main/documentation/doc-eng.md#3-installation-steps-1)
# 4. [Package Build](https://github.com/annihilatoratm/prestashop-doc/blob/main/documentation/doc-eng.md#4-package-build-1)
# 5. [Plugin Installation](https://github.com/annihilatoratm/prestashop-doc/blob/main/documentation/doc-eng.md#5-plugin-installation-1)
# 6. [Plugin Configuration](https://github.com/annihilatoratm/prestashop-doc/blob/main/documentation/doc-eng.md#6-plugin-configuration-1)
# 7. [Plugin Uninstallation](https://github.com/annihilatoratm/prestashop-doc/blob/main/documentation/doc-eng.md#7-plugin-uninstallation-1)
# 8. [Payment Flow](https://github.com/annihilatoratm/prestashop-doc/blob/main/documentation/doc-eng.md#8-payment-flow)

## 1. Requirements

* PHP version: 5.3 – 5.5.
* [Curl extension](http://php.net/manual/en/book.curl.php).
* [Prestashop](http://www.prestashop.com/en/download) 1.6.x (the module has been tested with version 1.6.0.9).

## 2. Functionality

This module allows processing payment via [Connecting Party PaynetEasy API](http://wiki.payneteasy.com/index.php/PnE:Merchant_API). The following payment methods are currently implemented:
- [Sale Transactions](https://doc.payneteasy.eu/integration/api_use_cases/server_to_server_sale.html)

## 3. Installation Steps

3.1. Install prestashop 1.6.1.24  
3.2. Copy the controllers and modules folders into the root directory of your Prestashop installation.  
3.3. Go to _Advanced Parameters_ > _Performance_ and:   
   * Disable cache  
   * Disable template compilation  
   * Enable Disable all overrides    
    
3.4. Install and configure the _Paynet module_ via Modules and Services.
3.5. Clear the default product catalog (Catalog > Products) and add a new product using the **Add new product** button. Make sure to set both quantity and price. 

## 4. Package Build
4.1. [Install composer](http://getcomposer.org/doc/00-intro.md), if it is not installed.  
4.2. Clone the module source code: `composer create-project payneteasy/php-plugin-prestashop --stability=dev --prefer-dist`.  
4.3. Navigate to the module directory: `cd php-plugin-prestashop`.  
4.4. Create a ZIP archive of the module: `composer archive --format=zip`.  

## 5. Plugin Installation

5.1. [Download package containing module](00-introduction.md#get_package).  
5.2. Extract the contents into the Prestashop root directory.  
5.3. Open the Prestashop admin panel. 
5.4. Navigate to _Modules_ > _Module Manager_ (1). 
5.5. Install Module.  
    5.5.1. Search for "payneteasy" using the search field (2).
    5.5.2. Click **Install** to install the plugin.  
5.6. Confirm installation, even though the module is not verified by Prestashop.  

<img src="/images/prestashop-7.png" width=60% height=60%>

## 6. Plugin Configuration.  

6.1. Open Prestashop Administration Panel.    
6.2. Go to the _Modules_ > _Module Manager_ section. (1).    
6.3. Open the module settings page.  
    6.3.1. Search for "payneteasy" using the search field (2).  
    6.3.2. Open module setting page by pressing **Configure** button (3).  
       
  <img src="/images/prestashop-1-1.png" width=60% height=60%>
  
6.4. Fill in the required configuration settings (arrow #1).  
<img src="/images/prestashop-1-2.png" width=60% height=60%>

## 7. Plugin Uninstallation.

7.1. Open Prestashop Administration Panel.  
7.2. Navigate to _Modules_ > _Module Manager_.  
7.3. Remove Module.  
    7.3.1. Search for "payneteasy" using the search field.  
    7.3.2. Open the list of actions for the module.  
    7.3.3. Choose **Delete**.  
       
<img src="/images/prestashop-1-3.png" width=60% height=60%>

## 8. Payment Flow

8.1. On the main page, select a product and click **Add to cart**.  
<img src="/images/prestashop-1.png" width=60% height=60%>
<img src="/images/prestashop-2.png" width=60% height=60%>
  
8.2. A confirmation pop-up will appear. Click **Proceed to checkout** to continue or **Continue shopping** to go back.  

<img src="/images/prestashop-popup.png" width=60% height=60%>

8.3. When ready, click the **Cart** icon in the top navigation bar to review the items.   

<img src="/images/prestashop-3.png" width=60% height=60%>

8.4. Click **Proceed to checkout** to begin the payment process:  
   8.4.1. Personal Information.    
   8.4.2. Addresses.   
   8.4.3. Shipping Method.   
   8.4.4. Payment Method (Select Paynet Payment Method).   

<img src="/images/prestashop-5.png" width=60% height=60%>
<img src="/images/prestashop-6.png" width=60% height=60%>
