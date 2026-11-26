<?php

require_once __DIR__ . '/../../payment_aggregate.php';

use PaynetEasy\PaymentAggregate;
use PaynetEasy\PaynetEasyApi\Transport\CallbackResponse;
use PaynetEasy\PaynetEasyApi\Exception\PaynetException;
use PaynetEasy\PaynetEasyApi\Exception\ValidationException;

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
     * The gateway returns the customer with a cross-site POST, which carries no
     * SameSite=Lax shop cookie: the customer would lose the cart session and land
     * on the login page. Re-issue the request as a same-site GET (with the
     * gateway fields in the query) before the shop starts a new session.
     *
     * The gateway server callback has its own address and is processed at once.
     */
    public function init()
    {
        if (    !$this->isServerCallback()
            &&  isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST))
        {
            // Only the fields that are checked go to the query: the other gateway
            // fields (card and customer data) are not needed
            $request = array_merge($_GET, $_POST);
            $query   = array();

            foreach (array('id_cart', 'key', 'status', 'orderid', 'paynet-order-id', 'client_orderid', 'merchant_order',
                           'merchant-order-id', 'control', 'merchant_control', 'amount', 'type',
                           'error_message', 'error-message', 'error_code', 'error-code') as $field)
            {
                if (isset($request[$field]) && is_scalar($request[$field]))
                {
                    $query[$field] = (string) $request[$field];
                }
            }

            // Do not write the empty session cookie of this request over the customer's one
            if (isset($this->context->cookie) && method_exists($this->context->cookie, 'disallowWriting'))
            {
                $this->context->cookie->disallowWriting();
            }

            header('Location: ' . $this->context->link->getModuleLink('payneteasy', 'finishsale', $query, true), true, 303);
            exit;
        }

        parent::init();
    }

    /**
     * True if the request came to the address of the gateway server callback.
     *
     * @return      boolean
     */
    protected function isServerCallback()
    {
        return isset($_GET['callback']) && $_GET['callback'] === '1';
    }

    /**
     * Validates response from PaynetEasy server.
     *
     * Creates the order if the payment was successful
     * and redirect customer to result page with success message.
     *
     * If the payment is made with an error,
     * displays an error message.
     *
     * The order is created for a final payment result only. If the result
     * can not be validated or is not final, nothing is created or changed.
     */
    public function display()
    {
        $payment_aggregate = new PaymentAggregate($this->module);
        $is_callback = $this->isServerCallback();
        $id_cart = Tools::getValue('id_cart');
        $prestashop_cart = new Cart((is_string($id_cart) && ctype_digit($id_cart)) ? (int) $id_cart : 0);
        $paynet_response = new CallbackResponse($_REQUEST);

        // The return link carries the cart secure key: only the cart owner can finish the sale
        try
        {
            $paynet_payment = $this->validatePaymentData($prestashop_cart, $paynet_response);
        }
        catch (Exception $ex)
        {
            $this->logError($ex->getMessage());
            return $this->displayResult($is_callback, false, 'Invalid payment data received.');
        }

        $error_message = null;

        try
        {
            // The server callback is sent for other operations too: only the sale is processed here
            if ($is_callback && $paynet_response->getType() !== 'sale')
            {
                throw new Exception('Not a sale callback');
            }

            $payment_aggregate->finishSale(
                $prestashop_cart,
                $paynet_response,
                $paynet_payment['id_paynet_payment'],
                $paynet_payment['amount'],
                $is_callback
            );
        }
        catch (Exception $ex)
        {
            // The library throws the gateway error of a validated callback with the status "error".
            // Anything else (not validated data, not final status) leaves the cart as it is.
            if (    !($ex instanceof PaynetException)
                ||  $ex instanceof ValidationException
                ||  $paynet_response->getStatus() !== 'error')
            {
                $this->logError($ex->getMessage(), $prestashop_cart, $paynet_payment);
                return $this->displayResult($is_callback, false, 'Payment is not confirmed.');
            }

            $error_message = $ex->getMessage();
        }

        // Here the payment result is validated and final: approved, declined, filtered or error
        if (!$this->lockCart($prestashop_cart))
        {
            $this->logError('Can not lock the cart', $prestashop_cart, $paynet_payment);
            return $this->displayResult($is_callback, false, 'Payment is not confirmed.');
        }

        $prestashop_order_id = $this->getOrderIdByCart($prestashop_cart);

        if ($prestashop_order_id)
        {
            if ($paynet_response->isApproved())
            {
                $this->recordPaymentForExistingOrder($prestashop_order_id, $prestashop_cart, $paynet_response);
            }
        }
        elseif ($paynet_response->isApproved())
        {
            $this->createOrderWithSuccessPayment($prestashop_cart, $paynet_response);
            $prestashop_order_id = (int) $this->module->currentOrder;
        }
        else
        {
            $this->createOrderWithErrorPayment($prestashop_cart, $paynet_response, $error_message);
            $prestashop_order_id = (int) $this->module->currentOrder;
        }

        $this->unlockCart($prestashop_cart);

        if ($is_callback)
        {
            return $this->displayResult(true, true);
        }

        // The page shows the state of the order: a repeated request changes nothing
        $prestashop_order = new Order($prestashop_order_id);

        if (    !Validate::isLoadedObject($prestashop_order)
            ||  (int) $prestashop_order->getCurrentState() === (int) Configuration::get('PS_OS_ERROR'))
        {
            return $this->displayError('Payment not passed');
        }

        $this->successRedirect($prestashop_cart, $prestashop_order_id);
    }

    /**
     * Verifies the existence of the cart and payment.
     *
     * @param       Cart                    $prestashop_cart        Prestashop cart object.
     * @param       CallbackResponse        $paynet_response        PaynetEasy payment response.
     *
     * @return      array           Payment started by the shop for this cart (id_paynet_payment, amount, currency).
     *
     * @throws      Exception       Cart or payment does not exists.
     */
    protected function validatePaymentData(Cart $prestashop_cart, CallbackResponse $paynet_response)
    {
        if (!Validate::isLoadedObject($prestashop_cart))
        {
            throw new Exception('Can not found cart with given id.');
        }

        $key = Tools::getValue('key');
        if (!is_string($key) || $key === '' || !hash_equals((string) $prestashop_cart->secure_key, $key))
        {
            throw new Exception('Can not found cart with given id.');
        }

        $paynet_payment_id = $paynet_response->getPaymentPaynetId();
        if (!is_string($paynet_payment_id) || !ctype_digit($paynet_payment_id) || strlen($paynet_payment_id) > 18)
        {
            throw new Exception('Can not found cart with given id.');
        }

        $paynet_payment_id = (int) $paynet_payment_id;
        $prestashop_cart_id = (int) $prestashop_cart->id;

        Payneteasy::ensurePaymentColumns();

        $saved_paynet_payment = Db::getInstance()->getRow("
            SELECT `id_paynet_payment`, `amount`, `currency` FROM `" . _DB_PREFIX_ . "paynet_payments`
            WHERE `id_paynet_payment` = {$paynet_payment_id} AND `id_prestashop_cart` = {$prestashop_cart_id}
        ", false);

        if (empty($saved_paynet_payment))
        {
            throw new Exception('Can not found cart with given id.');
        }

        return $saved_paynet_payment;
    }

    /**
     * Takes the lock of the cart: the customer return and the server callback
     * of one payment can come at the same time.
     *
     * @param       Cart        $prestashop_cart        Prestashop cart object.
     *
     * @return      boolean
     */
    protected function lockCart(Cart $prestashop_cart)
    {
        return (bool) Db::getInstance()->getValue(
            "SELECT GET_LOCK('" . $this->getCartLockName($prestashop_cart) . "', 20)",
            false
        );
    }

    /**
     * Releases the lock of the cart.
     *
     * @param       Cart        $prestashop_cart        Prestashop cart object.
     */
    protected function unlockCart(Cart $prestashop_cart)
    {
        Db::getInstance()->getValue(
            "SELECT RELEASE_LOCK('" . $this->getCartLockName($prestashop_cart) . "')",
            false
        );
    }

    /**
     * Name of the database lock of the cart (locks are shared by all databases of the server).
     *
     * @param       Cart        $prestashop_cart        Prestashop cart object.
     *
     * @return      string
     */
    protected function getCartLockName(Cart $prestashop_cart)
    {
        return 'pne_' . md5(_DB_NAME_ . '.' . _DB_PREFIX_ . 'cart.' . (int) $prestashop_cart->id);
    }

    /**
     * Id of the order created for the cart, read from the database without cache.
     *
     * @param       Cart        $prestashop_cart        Prestashop cart object.
     *
     * @return      int         Order id, 0 if the cart has no order.
     */
    protected function getOrderIdByCart(Cart $prestashop_cart)
    {
        return (int) Db::getInstance()->getValue('
            SELECT `id_order` FROM `' . _DB_PREFIX_ . 'orders`
            WHERE `id_cart` = ' . (int) $prestashop_cart->id . '
            ORDER BY `id_order`
        ', false);
    }

    /**
     * Records an approved payment that came after the order of the cart was created
     * with a payment error. An order in any other state is not changed.
     *
     * @param       int                     $prestashop_order_id    Prestashop order id.
     * @param       Cart                    $prestashop_cart        Prestashop cart object.
     * @param       CallbackResponse        $paynet_response        PaynetEasy payment response.
     */
    protected function recordPaymentForExistingOrder($prestashop_order_id, Cart $prestashop_cart, CallbackResponse $paynet_response)
    {
        $prestashop_order = new Order((int) $prestashop_order_id);
        $paynet_payment_id = (int) $paynet_response->getPaymentPaynetId();

        if (    !Validate::isLoadedObject($prestashop_order)
            ||  $prestashop_order->module !== $this->module->name
            ||  (int) $prestashop_order->getCurrentState() !== (int) Configuration::get('PS_OS_ERROR'))
        {
            return;
        }

        // This payment is already recorded for the order
        foreach ($prestashop_order->getOrderPaymentCollection() as $order_payment)
        {
            if ((string) $order_payment->transaction_id === (string) $paynet_payment_id && (float) $order_payment->amount > 0)
            {
                return;
            }
        }

        $paid_amount = $this->getPaidAmount($prestashop_cart, $paynet_response);

        if ($paid_amount <= 0 || !$prestashop_order->addOrderPayment($paid_amount, null, (string) $paynet_payment_id))
        {
            $this->logError("Approved payment was not recorded for order {$prestashop_order->id}", $prestashop_cart, array('id_paynet_payment' => $paynet_payment_id));
            return;
        }

        Db::getInstance()->update(
            'paynet_payments',
            array('id_prestashop_order' => (int) $prestashop_order->id),
            "`id_paynet_payment` = {$paynet_payment_id} AND `id_prestashop_cart` = " . (int) $prestashop_cart->id
        );

        // The order becomes paid only if the whole order amount was paid
        if (abs($paid_amount - (float) $prestashop_order->total_paid) < 0.005)
        {
            $prestashop_order->setCurrentState((int) Configuration::get('PS_OS_PAYMENT'));
        }
        else
        {
            $this->logError("Approved payment amount differs from the total of order {$prestashop_order->id}", $prestashop_cart, array('id_paynet_payment' => $paynet_payment_id));
        }
    }

    /**
     * Creates order for cart if payment processed with error.
     *
     * @param       Cart        $prestashop_cart        Prestashop cart object.
     */
    protected function createOrderWithErrorPayment(Cart $prestashop_cart, CallbackResponse $paynet_response, $error_message = null)
    {
        $order_comment = $paynet_response->getStatus();

        if (!is_null($error_message))
        {
            $order_comment .= ": {$error_message}";
        }

        // If now set status 'PS_OS_ERROR', transaction will not be saved.
        // But because assigned amount '0' and status 'PS_OS_PAYMENT', transaction will be saved
        // and displayed with warning in admin control panel.
        $this->createOrder(
            $prestashop_cart,
            $paynet_response,
            Configuration::get('PS_OS_PAYMENT'),
            0,
            $order_comment
        );
    }

    /**
     * Creates order for cart and payment response if payment succesfully processed.
     *
     * @param       Cart                    $prestashop_cart        Prestashop cart object.
     * @param       CallbackResponse        $paynet_response        PaynetEasy payment response.
     */
    protected function createOrderWithSuccessPayment(Cart $prestashop_cart, CallbackResponse $paynet_response)
    {
        // The order is created with the amount sent to the gateway: if the cart was
        // changed after the payment was started, PrestaShop marks the order as a payment error.
        $this->createOrder(
            $prestashop_cart,
            $paynet_response,
            Configuration::get('PS_OS_PAYMENT'),
            $this->getPaidAmount($prestashop_cart, $paynet_response)
        );
    }

    /**
     * Amount sent to the gateway for this payment, 0 if the currency has changed.
     *
     * @param       Cart                    $prestashop_cart        Prestashop cart object.
     * @param       CallbackResponse        $paynet_response        PaynetEasy payment response.
     *
     * @return      float
     */
    protected function getPaidAmount(Cart $prestashop_cart, CallbackResponse $paynet_response)
    {
        Payneteasy::ensurePaymentColumns();

        $row = Db::getInstance()->getRow('
            SELECT `amount`, `currency` FROM `' . _DB_PREFIX_ . 'paynet_payments`
            WHERE `id_paynet_payment` = ' . (int) $paynet_response->getPaymentPaynetId() . '
              AND `id_prestashop_cart` = ' . (int) $prestashop_cart->id
        );

        $currency = Currency::getCurrencyInstance($prestashop_cart->id_currency)->iso_code;

        if (empty($row))
        {
            return 0;
        }

        // Payment started before the update: the amount was not saved
        if ($row['amount'] === null)
        {
            return (float) $prestashop_cart->getOrderTotal();
        }

        if (strcasecmp((string) $row['currency'], (string) $currency) !== 0)
        {
            return 0;
        }

        return (float) $row['amount'];
    }

    /**
     * Creates order for cart and payment response.
     *
     * @param       Cart                    $prestashop_cart        Prestashop cart object.
     * @param       CallbackResponse        $paynet_response        PaynetEasy payment response.
     * @param       int                     $order_status_id        Order status id.
     * @param       float                   $payment_amount         Payment amount.
     * @param       string                  $order_comment          Comment for order.
     */
    protected function createOrder(
        Cart $prestashop_cart,
        CallbackResponse $paynet_response,
        $order_status_id,
        $payment_amount,
        $order_comment = null)
    {
        $db = Db::getInstance();
        $paynet_payment_id = (int) $paynet_response->getPaymentPaynetId();
        $prestashop_cart_id = (int) $prestashop_cart->id;

        $this->module->validateOrder(
            $prestashop_cart_id,
            $order_status_id,
            $payment_amount,
            $this->module->name,
            $order_comment,
            array('transaction_id' => $paynet_payment_id),
            null,
            false,
            $prestashop_cart->secure_key
        );

        $db->update(
            'paynet_payments',
            array('id_prestashop_order' => $this->module->currentOrder),
            "`id_paynet_payment` = {$paynet_payment_id} AND `id_prestashop_cart` = {$prestashop_cart_id}"
        );
    }

    /**
     * Redirects customer to result page with success message.
     *
     * @param       Cart        $prestashop_cart            Prestashop cart object.
     * @param       int         $prestashop_order_id        Prestashop order id.
     */
    protected function successRedirect(Cart $prestashop_cart, $prestashop_order_id)
    {
        $redirect_url = $this->context->link->getPageLink('order-confirmation', true, null, array(
            'id_cart'   => $prestashop_cart->id,
            'id_module' => $this->module->id,
            'id_order'  => $prestashop_order_id,
            'key'       => $prestashop_cart->secure_key
        ));

        Tools::redirectLink($redirect_url);
    }

    /**
     * Writes a short error message to the shop log (no request data).
     *
     * @param       string      $message                Error text.
     * @param       Cart        $prestashop_cart        Prestashop cart object.
     * @param       array       $paynet_payment         Payment started by the shop.
     */
    protected function logError($message, $prestashop_cart = null, $paynet_payment = null)
    {
        $prefix = 'PaynetEasy';

        if ($this->isServerCallback())
        {
            $prefix .= ' callback';
        }

        if ($prestashop_cart && $prestashop_cart->id)
        {
            $prefix .= ', cart ' . (int) $prestashop_cart->id;
        }

        if (!empty($paynet_payment['id_paynet_payment']))
        {
            $prefix .= ', payment ' . (int) $paynet_payment['id_paynet_payment'];
        }

        $message = preg_replace('/[^\x20-\x7E]+|[<>{}]/', ' ', (string) $message);

        PrestaShopLogger::addLog($prefix . ': ' . Tools::substr($message, 0, 500), 3);
    }

    /**
     * Answers the gateway server or shows the error page to the customer.
     *
     * @param       boolean     $is_callback        True for the gateway server callback.
     * @param       boolean     $processed          True if the payment result was processed.
     * @param       string      $message            Error message for the customer.
     */
    protected function displayResult($is_callback, $processed, $message = 'Technical error occured')
    {
        if (!$is_callback)
        {
            return $this->displayError($message);
        }

        if (!$processed)
        {
            header('HTTP/1.1 400 Bad Request');
        }

        header('Content-Type: text/plain; charset=utf-8');
        die($processed ? 'OK' : 'ERROR');
    }

    /**
     * Displays message about occured error.
     *
     * @param       string      $message        Error message.
     */
    protected function displayError($message = 'Technical error occured')
    {
        $this->context->smarty->assign('error_message', $this->module->l($message));

		if ($this->context->customer->is_guest)
		{
			$this->context->smarty->assign(array(
				'id_order'           => $this->module->currentOrder,
				'reference_order'    => $this->module->currentOrderReference,
				'id_order_formatted' => sprintf('#%06d', $this->module->currentOrder),
				'email' => $this->context->customer->email
			));

			/* If guest we clear the cookie for security reason (only when the order was created) */
			if ($this->module->currentOrder)
			{
				$this->context->customer->mylogout();
			}
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
