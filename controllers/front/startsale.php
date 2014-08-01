<?php

require_once __DIR__ . '/../../payment_aggregate.php';

use PaynetEasy\PaymentAggregate;

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

        try
        {
            $response = $payment_aggregate->startSale(
                $this->context->cart,
                $this->context->link->getModuleLink('payneteasy', 'finishsale', array(), true)
            );

        }
        catch (Exception $ex)
        {
            PrestaShopLogger::addLog((string) $ex, 50);
            $this->context->smarty->assign('error_message', $this->module->l('Technical error occured'));
            $this->setTemplate('payment_error.tpl');

            return parent::display();
        }

        Tools::redirectLink($response->getRedirectUrl());
    }


}

