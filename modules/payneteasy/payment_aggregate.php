<?php

namespace PaynetEasy;

require_once __DIR__ . '/vendor/autoload.php';

use PaynetEasy\PaynetEasyApi\PaymentData\PaymentTransaction;
use PaynetEasy\PaynetEasyApi\PaymentData\Payment;
use PaynetEasy\PaynetEasyApi\PaymentData\Customer as PaynetCustomer;
use PaynetEasy\PaynetEasyApi\PaymentData\BillingAddress;
use PaynetEasy\PaynetEasyApi\PaymentData\QueryConfig;
use PaynetEasy\PaynetEasyApi\PaymentProcessor;
use PaynetEasy\PaynetEasyApi\Transport\CallbackResponse;
use PaynetEasy\PaynetEasyApi\Util\RegionFinder;

use Module;
use Cart;
use Address;
use Country;
use State;
use Customer;
use Shop;
use Currency;
use Validate;
use Configuration;
use Tools;

/**
 * Aggregate with common payment methods.
 */
class PaymentAggregate
{
    /**
     * PaynetEasy module instance (for localization purpose only).
     *
     * @var Module
     */
    protected $module;

    /**
     * @param       Module      $module     PaynetEasy module instance
     */
    public function __construct(Module $module)
    {
        $this->module  = $module;
    }

    /**
     * Starts order processing.
     * Method executes query to PaynetEasy gateway and returns response from gateway.
     * After that user must be redirected to the Response::getRedirectUrl()
     *
     * @param       Cart        $prestashop_cart        Prestashop cart.
     * @param       string      $return_url             Url for order processing after payment.
     *
     * @return      Response        Gateway response object.
     */
    public function startSale(Cart $prestashop_cart, $return_url)
    {
        $payment_processor  = new PaymentProcessor;
        $paynet_transaction = $this->getPaynetTransaction($prestashop_cart, $return_url);

        return $payment_processor->executeQuery('sale-form', $paynet_transaction);
    }

    /**
     * Finish order processing.
     * Method checks callback data and returns object with them.
     * After that order processing result can be displayed.
     *
     * @param       Cart                    $prestashop_cart        Prestashop cart.
     * @param       CallbackResponse        $callback               Callback object.
     *
     * @return      CallbackResponse        Callback object.
     */
    public function finishSale(Cart $prestashop_cart, CallbackResponse $callback)
    {
        $payment_processor  = new PaymentProcessor;
        $paynet_transaction = $this->getPaynetTransaction($prestashop_cart);
        $paynet_transaction->setStatus(PaymentTransaction::STATUS_PROCESSING);

        return $payment_processor->processCustomerReturn($callback, $paynet_transaction);
    }

    /**
     * Get PaynetEasy payment transaction object by Prestashop cart object.
     *
     * @param       Cart        $prestashop_cart        Prestashop cart.
     * @param       string      $return_url             Url for final payment processing.
     *
     * @return      PaynetTransaction       PaynetEasy payment transaction
     */
    protected function getPaynetTransaction(Cart $prestashop_cart, $return_url = null)
    {
        $paynet_transaction = new PaymentTransaction;

        $paynet_transaction
            ->setPayment($this->getPaynetPayment($prestashop_cart))
            ->setQueryConfig($this->getQueryConfig($return_url))
        ;

        return $paynet_transaction;
    }

    /**
     * Get PaynetEasy query config object.
     *
     * @param       string      $return_url             Url for final payment processing.
     *
     * @return      QueryConfig     PaynetEasy payment transaction
     */
    protected function getQueryConfig($return_url = null)
    {
        $query_config = new QueryConfig;

        $query_config
            ->setEndPoint((int) Configuration::get('PAYNETEASY_END_POINT'))
            ->setLogin(Configuration::get('PAYNETEASY_LOGIN'))
            ->setSigningKey(Configuration::get('PAYNETEASY_SIGNING_KEY'))
            ->setGatewayMode(Configuration::get('PAYNETEASY_GATEWAY_MODE'))
            ->setGatewayUrlSandbox(Configuration::get('PAYNETEASY_SANDBOX_GATEWAY'))
            ->setGatewayUrlProduction(Configuration::get('PAYNETEASY_PRODUCTION_GATEWAY'))
        ;

        if (Validate::isUrl($return_url))
        {
            $query_config
                ->setRedirectUrl($return_url)
                ->setCallbackUrl($return_url)
            ;
        }

        return $query_config;
    }

    /**
     * Get PaynetEasy payment object by Prestashop cart object.
     *
     * @param       Cart        $prestashop_cart        Prestashop cart.
     *
     * @return      Payment     PaynetEasy payment transaction
     */
    protected function getPaynetPayment(Cart $prestashop_cart)
    {
        $paynet_payment = new Payment;

        if (Tools::getIsset('orderid'))
        {
            $paynet_payment->setPaynetId(Tools::getValue('orderid'));
        }

        $paynet_payment
            ->setClientId($prestashop_cart->id)
            ->setDescription($this->getPaynetOrderDescription($prestashop_cart))
            ->setAmount($prestashop_cart->getOrderTotal())
            ->setCurrency(Currency::getCurrencyInstance($prestashop_cart->id_currency)->iso_code)
            ->setCustomer($this->getPaynetCustomer($prestashop_cart))
            ->setBillingAddress($this->getPaynetAddress($prestashop_cart))
        ;

        return $paynet_payment;
    }

    /**
     * Get PaynetEasy address object by Prestashop cart object.
     *
     * @param       Cart        $prestashop_cart        Prestashop cart.
     *
     * @return      BillingAddress      PaynetEasy payment transaction
     */
    protected function getPaynetAddress(Cart $prestashop_cart)
    {
        $paynet_address     = new BillingAddress;
        $prestashop_address = new Address(intval($prestashop_cart->id_address_invoice));
        $prestashop_country = new Country(intval($prestashop_address->id_country));

        // In Prestashop to many countries has states.
        // PaynetEasy API requires state code only for some countries.
        if (    RegionFinder::hasStates($prestashop_country->iso_code)
            &&  Country::containsStates($prestashop_address->id_country))
        {
            $prestashop_state = new State($prestashop_address->id_state);
            $paynet_address->setState($prestashop_state->iso_code);
        }

        if (Validate::isPhoneNumber($prestashop_address->phone))
        {
            $paynet_address->setPhone($prestashop_address->phone);
        }

        if (Validate::isPhoneNumber($prestashop_address->phone_mobile))
        {
            $paynet_address->setCellPhone($prestashop_address->phone_mobile);
        }

        $paynet_address
            ->setCountry($prestashop_country->iso_code)
            ->setCity($prestashop_address->city)
            ->setFirstLine($prestashop_address->address1)
            ->setZipCode($prestashop_address->postcode)
        ;

        return $paynet_address;
    }

    /**
     * Get PaynetEasy customer object by Prestashop cart object.
     *
     * @param       Cart        $prestashop_cart        Prestashop cart.
     *
     * @return      PaynetCustomer      PaynetEasy payment transaction
     */
    protected function getPaynetCustomer(Cart $prestashop_cart)
    {
        $paynet_customer     = new PaynetCustomer;
        $prestashop_customer = new Customer(intval($prestashop_cart->id_customer));

        $paynet_customer
            ->setEmail($prestashop_customer->email)
            ->setFirstName($prestashop_customer->firstname)
            ->setLastName($prestashop_customer->lastname)
            ->setIpAddress(Tools::getRemoteAddr())
        ;

        return $paynet_customer;
    }

    /**
     * Get PaynetEasy payment description by Prestashop cart object.
     *
     * @param       Cart        $prestashop_cart        Prestashop cart.
     *
     * @return      string      PaynetEasy payment description.
     */
    protected function getPaynetOrderDescription(Cart $prestashop_cart)
    {
        $prestashop_shop = new Shop($prestashop_cart->id_shop);
        return "{$this->l('Shopping in')}: {$prestashop_shop->name}; {$this->l('Order id')}: {$prestashop_cart->id}";
    }

    /**
     * Translates phrase.
     *
     * @param       string      $phrase     Phrase.
     *
     * @return      string      Translated phrase.
     */
    private function l($phrase)
    {
        return $this->module->l($phrase);
    }
}

