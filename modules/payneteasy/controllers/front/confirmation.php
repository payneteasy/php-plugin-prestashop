<?php

/**
 * Displays order confirmation page.
 */
class PaynetEasyConfirmationModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();

        $this->ssl = true;
        $this->display_column_left = false;
        $this->display_column_right = false;
    }

    /**
     * Displays order confirmation page.
     */
	public function display()
	{
		$cart = $this->context->cart;

		$this->context->smarty->assign(array(
			'order_total' => $cart->getOrderTotal(true, Cart::BOTH),
			'is_empty' => $cart->nbProducts() == 0
		));

		$this->setTemplate('confirmation.tpl');

        parent::display();
	}
}

