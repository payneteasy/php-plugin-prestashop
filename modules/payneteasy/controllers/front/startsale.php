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

        // The payment is started only for the checkout cart of the current customer
        if (!$this->isCartReadyForPayment($prestashop_cart))
        {
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }

        $return_params = array('id_cart' => $prestashop_cart->id, 'key' => $prestashop_cart->secure_key);
        $return_url = $this->context->link
            ->getModuleLink('payneteasy', 'finishsale', $return_params, true);
        // The gateway server calls its own address: it is answered at once, without a redirect
        $callback_url = $this->context->link
            ->getModuleLink('payneteasy', 'finishsale', $return_params + array('callback' => 1), true);

        try
        {
            $paynet_response = $payment_aggregate->startSale($prestashop_cart, $return_url, $callback_url);
            $this->savePaynetPayment($paynet_response, $prestashop_cart);
        }
        catch (Exception $ex)
        {
            // Nothing was paid: the cart is kept, so the customer can try again
            return $this->displayTechnicalError($ex);
        }

        Tools::redirectLink($paynet_response->getRedirectUrl());
    }

    /**
     * Checks that the cart belongs to the current customer, can be ordered
     * and that this payment module is available.
     *
     * @param       Cart        $prestashop_cart        Prestashop cart object.
     *
     * @return      boolean
     */
    protected function isCartReadyForPayment($prestashop_cart)
    {
        if (    !Validate::isLoadedObject($prestashop_cart)
            ||  !$prestashop_cart->id_customer
            ||  !$prestashop_cart->id_address_delivery
            ||  !$prestashop_cart->id_address_invoice
            ||  !Validate::isLoadedObject($this->context->customer)
            ||  (int) $prestashop_cart->id_customer !== (int) $this->context->customer->id
            ||  !$prestashop_cart->nbProducts()
            ||  $prestashop_cart->orderExists())
        {
            return false;
        }

        if (!$this->module->active || !Module::isEnabled($this->module->name))
        {
            return false;
        }

        // PrestaShop 1.7+ lists the payment modules by a hook this module does not use
        if (version_compare(_PS_VERSION_, '1.7', '>='))
        {
            return true;
        }

        // Country, currency and customer group restrictions of the module
        foreach (Module::getPaymentModules() as $payment_module)
        {
            if ($payment_module['name'] == $this->module->name)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Saves information about PaynetEasy payment to database.
     *
     * @param       Response        $paynet_response        Response from PaynetEasy server.
     * @param       Cart            $prestashop_cart        Prestashop cart object.
     */
    protected function savePaynetPayment(Response $paynet_response, Cart $prestashop_cart)
    {
        Payneteasy::ensurePaymentColumns();

        // The amount and currency sent to the gateway: the order is created with them
        Db::getInstance()->insert('paynet_payments', array(
            'id_paynet_payment'  => (int) $paynet_response->getPaymentPaynetId(),
            'id_prestashop_cart' => (int) $prestashop_cart->id,
            'amount'             => number_format((float) $prestashop_cart->getOrderTotal(), 2, '.', ''),
            'currency'           => pSQL(Currency::getCurrencyInstance($prestashop_cart->id_currency)->iso_code),
        ));
    }

    /**
     * Displays message about occured technical error.
     *
     * @param       Exception       $ex     Error cause.
     */
    protected function displayTechnicalError(Exception $ex)
    {
        PrestaShopLogger::addLog(
            'PaynetEasy: payment for cart ' . (int) $this->context->cart->id . ' was not started: ' .
            Tools::substr(preg_replace('/\s+/', ' ', strip_tags($ex->getMessage())), 0, 500),
            3
        );
        $this->context->smarty->assign('error_message', $this->module->l('Technical error occured'));

		// No order was created: the customer session and the cart are kept
		if ($this->context->customer->is_guest)
		{
			$this->context->smarty->assign(array(
				'reference_order'   => '',
				'email'             => $this->context->customer->email
			));
		}

        // PrestaShop 1.7+ has no templates of the 1.6 theme: the message is shown
        // by the shop itself on the order page
        if (version_compare(_PS_VERSION_, '1.7', '>=') && method_exists($this, 'redirectWithNotifications'))
        {
            $message = $this->context->smarty->getTemplateVars('error_message');
            $this->errors[] = is_string($message) && $message !== '' ? $message : $this->module->l('Technical error occured');
            $this->redirectWithNotifications($this->context->link->getPageLink('order', true));
            return;
        }

        $this->setTemplate('payment_error.tpl');

        parent::display();
    }

}

