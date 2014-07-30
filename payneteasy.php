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
     * Database key for end point.
     */
    const END_POINT_KEY          = 'PAYNETEASY_END_POINT';

    /**
     * Database key for login.
     */
    const LOGIN_KEY              = 'PAYNETEASY_LOGIN';

    /**
     * Database key for signing key.
     */
    const SIGNING_KEY_KEY        = 'PAYNETEASY_SIGNING_KEY';

    /**
     * Database key for sandbox gateway url.
     */
    const SANDBOX_GATEWAY_KEY    = 'PAYNETEASY_SANDBOX_GATEWAY';

    /**
     * Database key for production gateway url.
     */
    const PRODUCTION_GATEWAY_KEY = 'PAYNETEASY_PRODUCTION_GATEWAY';

    /**
     * Database key for gateway mode (production or sandbox).
     */
    const GATEWAY_MODE_KEY       = 'PAYNETEASY_GATEWAY_MODE';

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

        if (Shop::isFeatureActive())
        {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return $this->updateConfigValues(array(
            self::END_POINT_KEY,
            self::LOGIN_KEY,
            self::SIGNING_KEY_KEY,
            self::SANDBOX_GATEWAY_KEY,
            self::PRODUCTION_GATEWAY_KEY,
            self::GATEWAY_MODE_KEY
        ));
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
            self::END_POINT_KEY,
            self::LOGIN_KEY,
            self::SIGNING_KEY_KEY,
            self::SANDBOX_GATEWAY_KEY,
            self::PRODUCTION_GATEWAY_KEY,
            self::GATEWAY_MODE_KEY
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

        if (!$this->saveConfigForm())
        {
            return $this->displayError($this->l('Can not save configuration.')) . $this->generateConfigForm();
        }

        return $this->displayConfirmation($this->l('Configuration saved.')) . $this->generateConfigForm();
    }

    /**
     * Saves configuration form to database.
     *
     * @return      boolean     Operation result (true if success).
     */
    protected function saveConfigForm() {
        return $this->updateConfigValues(array(
            self::END_POINT_KEY,
            self::LOGIN_KEY,
            self::SIGNING_KEY_KEY,
            self::SANDBOX_GATEWAY_KEY,
            self::PRODUCTION_GATEWAY_KEY,
            self::GATEWAY_MODE_KEY
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
                    $this->configTextField($helper, self::END_POINT_KEY, 'End point'),
                    $this->configTextField($helper, self::LOGIN_KEY, 'Login'),
                    $this->configTextField($helper, self::SIGNING_KEY_KEY, 'Signing key'),
                    $this->configTextField($helper, self::SANDBOX_GATEWAY_KEY, 'Sandbox gateway url'),
                    $this->configTextField($helper, self::PRODUCTION_GATEWAY_KEY, 'Production gateway url'),
                    $this->configRadioField($helper, self::GATEWAY_MODE_KEY, 'Gateway mode', array('sandbox', 'production'))
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
