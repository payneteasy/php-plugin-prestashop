<?php

if (!defined('_PS_VERSION_'))
{
    exit;
}

/**
 * Payment module for PaynetEasy payment form.
 *
 * @author imenem
 */
class PaynetEasy extends PaymentModule
{
    /**
     * Name for forms submit input.
     *
     * @var string
     */
    protected $submit_action;

    /**
     * Set up module metadata.
     */
    public function __construct()
    {
        $this->name            = 'payneteasy';
        $this->tab             = 'payments_gateways';
        $this->version         = '1.0.0';
        $this->author          = 'Artem Ponomarenko';
        $this->currencies_mode = 'radio';
        $this->submit_action   = 'submit_' . $this->name;

        parent::__construct();

        $this->displayName      = $this->l('PaynetEasy payment form');
        $this->description      = $this->l('Accepts payments by credit cards with PaynetEasy payment form.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Creates settings in database.
     *
     * @return      boolean     Operation result (true if success).
     */
    public function install()
    {
        if (!parent::install())
        {
            return false;
        }

        if (!$this->registerHooks(array('displayPayment')))
        {
            return false;
        }

        if (Shop::isFeatureActive())
        {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!$this->saveConfig())
        {
            return false;
        }

        return true;
    }

    /**
     * Deletes settings from database.
     *
     * @return      boolean     Operation result (true if success).
     */
    public function uninstall()
    {
        if (!parent::uninstall())
        {
            return false;
        }

        return $this->deleteConfigValues(array(
            'PAYNETEASY_END_POINT',
            'PAYNETEASY_LOGIN',
            'PAYNETEASY_SIGNING_KEY',
            'PAYNETEASY_SANDBOX_GATEWAY',
            'PAYNETEASY_PRODUCTION_GATEWAY',
            'PAYNETEASY_GATEWAY_MODE'
        ));
    }

    /**
     * Creates configuration form.
     * Saves configuration if form is submitted.
     *
     * @return      string      Configuration form html with operation result if form is submitted.
     */
    public function getContent()
    {
        if (!Tools::isSubmit($this->submit_action))
        {
            return $this->generateConfigForm();
        }

        if (!$this->saveConfig())
        {
            return $this->displayError($this->l('Can not save configuration.')) . $this->generateConfigForm();
        }

        return $this->displayConfirmation($this->l('Configuration saved.')) . $this->generateConfigForm();
    }

    /**
     * Display "Pay by PaynetEasy" button.
     *
     * @return      string      "Pay bu PaynetEasy" button html.
     */
    public function hookDisplayPayment()
    {
        if (!$this->active)
        {
			return;
        }

        return $this->display(__FILE__, 'payment.tpl');
    }

    /**
     * Saves configuration to database.
     *
     * @return      boolean     Operation result (true if success).
     */
    protected function saveConfig() {
        return $this->updateConfigValues(array(
            'PAYNETEASY_END_POINT',
            'PAYNETEASY_LOGIN',
            'PAYNETEASY_SIGNING_KEY',
            'PAYNETEASY_SANDBOX_GATEWAY',
            'PAYNETEASY_PRODUCTION_GATEWAY',
            'PAYNETEASY_GATEWAY_MODE'
        ));
    }

    /**
     * Generates configuration form.
     *
     * @return      string      Configuration form html.
     */
    protected function generateConfigForm()
    {
        $helper = new HelperForm();
        $helper->module          = $this;
        $helper->name_controller = $this->name;
        $helper->token           = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex    = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title           = $this->displayName;
        $helper->submit_action   = $this->submit_action;

        return $helper->generateForm(array(array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                ),
                'input'  => array(
                    $this->configTextField($helper, 'PAYNETEASY_END_POINT', 'End point'),
                    $this->configTextField($helper, 'PAYNETEASY_LOGIN', 'Login'),
                    $this->configTextField($helper, 'PAYNETEASY_SIGNING_KEY', 'Signing key'),
                    $this->configTextField($helper, 'PAYNETEASY_SANDBOX_GATEWAY', 'Sandbox gateway url'),
                    $this->configTextField($helper, 'PAYNETEASY_PRODUCTION_GATEWAY', 'Production gateway url'),
                    $this->configRadioField($helper, 'PAYNETEASY_GATEWAY_MODE', 'Gateway mode', array('sandbox', 'production'))
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'button'
                )
            )
        )));
    }

    /**
     * Updates configuration keys values.
     * For each key in array value will be taken from the request ($_POST or $_GET).
     *
     * @param       array       $config_keys        Configuration keys to update.
     *
     * @return      boolean     Operation result (true if success).
     */
    protected function updateConfigValues(array $config_keys)
    {
        foreach ($config_keys as $config_key)
        {
            if (!Configuration::updateValue($config_key, Tools::getValue($config_key, '')))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes configuration keys values.
     *
     * @param       array       $config_keys        Configuration keys to delete.
     *
     * @return      boolean     Operation result (true if success).
     */
    protected function deleteConfigValues(array $config_keys)
    {
        foreach ($config_keys as $config_key)
        {
            if (!Configuration::deleteByName($config_key))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Register hooks.
     *
     * @param       array       $hook_names         Array with hook names.
     *
     * @return      boolean     Operation result (true if success).
     */
    protected function registerHooks(array $hook_names)
    {
        foreach ($hook_names as $hook_name)
        {
            if (!$this->registerHook($hook_name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Creates text field for configuration form.
     * Field value will be taken from configuration.
     *
     * @param       HelperForm      $helper             Form helper.
     * @param       string          $config_key         Configuration key.
     * @param       string          $field_label        Field label.
     * @param       boolean         $required           Does field required or not.
     * @param       integer         $size               Field length.
     *
     * @return      string          Form field html.
     */
    protected function configTextField(HelperForm $helper, $config_key, $field_label, $required = true, $size = 20) {
        $this->loadConfigValue($helper, $config_key);

        return array(
            'type'     => 'text',
            'label'    => $this->l($field_label),
            'name'     => $config_key,
            'size'     => $size,
            'required' => $required
        );

    }

    /**
     * Creates radiobutton field for configuration form.
     * Field value will be taken from configuration.
     *
     * Options array must be in format ['first_option', 'second_option', ...].
     * Each option will be used as radiobutton option id, value and label (with localization).
     *
     * @param       HelperForm      $helper             Form helper.
     * @param       string          $config_key         Configuration key.
     * @param       string          $field_label        Field label.
     * @param       array           $options            Array with radiobutton options.
     * @param       boolean         $required           Does field required or not.
     *
     * @return      string          Form field html.
     */
    protected function configRadioField(HelperForm $helper, $config_key, $field_label, array $options, $required = true)
    {
        $this->loadConfigValue($helper, $config_key);

        return array(
            'type'      => 'radio',
            'label'     => $this->l($field_label),
            'name'      => $config_key,
            'required'  => $required,
            'values'    => array_map(function($value)
            {
                return array(
                    'id'    => $value,
                    'value' => $value,
                    'label' => $this->l($value)
                );
            },
            $options)
        );
    }

    /**
     * Loads configuration value from database to form helper.
     *
     * @param       HelperForm      $helper             Form helper.
     * @param       string          $config_key         Configuration key.
     */
    protected function loadConfigValue(HelperForm $helper, $config_key)
    {
        $helper->fields_value[$config_key] = Configuration::get($config_key);
    }

}