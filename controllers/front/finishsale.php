<?php

require_once __DIR__ . '/../../payment_aggregate.php';

use PaynetEasy\PaymentAggregate;
use PaynetEasy\PaynetEasyApi\Transport\CallbackResponse;

/**
 * Processes a response from the PaynetEasy server
 * to finish the payment process.
 */
class PayneteasyFinishsaleModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();

        $this->ssl = true;
        $this->display_column_left = false;
        $this->display_column_right = false;
    }

    /**
     * Validates response from PaynetEasy server.
     *
     * Creates the order if the payment was successful
     * and redirect customer to result page with success message.
     *
     * If the payment is made with an error,
     * displays an error message.
     */
    public function display()
    {
        $payment_aggregate = new PaymentAggregate($this->module);
        $prestashop_cart = new Cart(Tools::getValue('id_cart'));
        $paynet_response = new CallbackResponse($_REQUEST);

        try
        {
            $this->validatePaymentData($prestashop_cart, $paynet_response);
            $paynet_response = $payment_aggregate->finishSale($prestashop_cart, $paynet_response);
        }
        catch (Exception $ex)
        {
            $this->logException($ex);
            return $this->displayError('Invalid payment data received.');
        }

        if (!$paynet_response->isApproved())
        {
            return $this->displayError('Payment not passed');
        }

        try
        {
            $this->createOrder($prestashop_cart, $paynet_response);
        }
        catch (Exception $ex)
        {
            $this->logException($ex);
            return $this->displayError();
        }

        $this->successRedirect();
    }

    /**
     * Verifies the existence of the cart and payment.
     *
     * @param       Cart                    $prestashop_cart        Prestashop cart object.
     * @param       CallbackResponse        $paynet_response        PaynetEasy payment response.
     *
     * @throws      Exception       Cart or payment does not exists.
     */
    protected function validatePaymentData(Cart $prestashop_cart, CallbackResponse $paynet_response)
    {
        if (!Validate::isLoadedObject($prestashop_cart))
        {
            throw new Exception('Can not found cart with given id.');
        }

        $db = Db::getInstance();
        $paynet_payment_id = $db->escape($paynet_response->getPaymentPaynetId());
        $prestashop_cart_id = $db->escape($prestashop_cart->id);

        $saved_paynet_payment = Db::getInstance()->getValue("
            SELECT * FROM `" . _DB_PREFIX_ . "paynet_payments`
            WHERE `id_paynet_payment` = {$paynet_payment_id} AND `id_prestashop_cart` = {$prestashop_cart_id}
            LIMIT 1;
        ");

        if (empty($saved_paynet_payment))
        {
            throw new Exception('Can not found cart with given id.');
        }
    }

    /**
     * Creates order for cart and payment response.
     *
     * @param       Cart                    $prestashop_cart        Prestashop cart object.
     * @param       CallbackResponse        $paynet_response        PaynetEasy payment response.
     */
    protected function createOrder(Cart $prestashop_cart, CallbackResponse $paynet_response)
    {
        $db = Db::getInstance();
        $paynet_payment_id = $db->escape($paynet_response->getPaymentPaynetId());
        $prestashop_cart_id = $db->escape($prestashop_cart->id);

        $this->module->validateOrder(
            $prestashop_cart_id,
            Configuration::get('PS_OS_PAYMENT'),
            $prestashop_cart->getOrderTotal(),
            $this->module->name,
            null,
            array('transaction_id' => $paynet_payment_id)
        );

        $db->update(
            'paynet_payments',
            array('id_prestashop_order' => $this->module->currentOrder),
            "`id_paynet_payment` = {$paynet_payment_id} AND `id_prestashop_cart` = {$prestashop_cart_id}"
        );
    }

    /**
     * Redirects customer to result page with success message.
     */
    protected function successRedirect()
    {
        $redirect_url = $this->context->link->getPageLink('order-confirmation', true, null, array(
            'id_cart'   => $this->context->cart->id,
            'id_module' => $this->module->id,
            'id_order'  => $this->module->currentOrder,
            'key'       => $this->context->cart->secure_key
        ));

        Tools::redirectLink($redirect_url);
    }

    /**
     * Log exception.
     *
     * @param       Exception       $ex     Exception to log.
     */
    protected function logException(Exception $ex)
    {
        PrestaShopLogger::addLog((string) $ex, 50);
    }

    /**
     * Displays message about occured error.
     *
     * @param       string      $message        Error message.
     */
    protected function displayError($message = 'Technical error occured')
    {
        PrestaShopLogger::addLog('Callback data: ' . print_r($_REQUEST, true), 4);

        $this->context->smarty->assign('error_message', $this->module->l($message));
        $this->setTemplate('payment_error.tpl');

        parent::display();
    }
}

