<?php

require_once __DIR__ . '/../../payment_aggregate.php';

use PaynetEasy\PaymentAggregate;
use PaynetEasy\PaynetEasyApi\Transport\Response;

/**
 * Executes a request to the PaynetEasy server
 * to start the payment process.
 */
class PayneteasyStartsaleModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();

        $this->ssl = true;
        $this->display_column_left = false;
        $this->display_column_right = false;
    }

    /**
     * Executes a request to the PaynetEasy server
     * to start the payment process.
     *
     * If the request is successful, redirects the
     * customer to the PaynetEasy payment form.
     *
     * If the request is made with an error,
     * displays an error message.
     */
    public function display()
    {
        $payment_aggregate = new PaymentAggregate($this->module);
        $prestashop_cart = $this->context->cart;
        $return_url = $this->context->link->getModuleLink('payneteasy', 'finishsale', array(), true);

        try
        {
            $paynet_response = $payment_aggregate->startSale($prestashop_cart, $return_url);
            $this->savePaynetPayment($paynet_response, $prestashop_cart);
        }
        catch (Exception $ex)
        {
            return $this->displayTechnicalError($ex);
        }

        Tools::redirectLink($paynet_response->getRedirectUrl());
    }

    /**
     * Saves information about PaynetEasy payment to database.
     *
     * @param       Response        $paynet_response        Response from PaynetEasy server.
     * @param       Cart            $prestashop_cart        Prestashop cart object.
     */
    protected function savePaynetPayment(Response $paynet_response, Cart $prestashop_cart)
    {
        Db::getInstance()->insert('paynet_payments', array(
            'id_paynet_payment'  => $paynet_response->getPaymentPaynetId(),
            'id_prestashop_cart' => $prestashop_cart->id
        ));
    }

    /**
     * Displays message about occured technical error.
     *
     * @param       Exception       $ex     Error cause.
     */
    protected function displayTechnicalError(Exception $ex)
    {
        PrestaShopLogger::addLog((string) $ex, 50);
        $this->context->smarty->assign('error_message', $this->module->l('Technical error occured'));
        $this->setTemplate('payment_error.tpl');

        parent::display();
    }

}

