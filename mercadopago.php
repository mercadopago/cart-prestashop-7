<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
define('MP_VERSION', '4.19.3');

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'mercadopago/vendor/autoload.php';

class Mercadopago extends PaymentModule
{
    public $tab;
    public $name;
    public $path;
    public $author;
    public $version;
    public $context;
    public $mpuseful;
    public $bootstrap;
    public $module_key;
    public $mercadopago;
    public $displayName;
    public $description;
    public $need_instance;
    public $assets_ext_min;
    public $customCheckout;
    public $ticketCheckout;
    public $standardCheckout;
    public $pixCheckout;
    public $pseCheckout;
    public $confirmUninstall;
    public $ps_versions_compliancy;
    public $ps_version;
    public static $form_alert;
    public static $form_message;

    public const PRESTA17 = '1.7';

    private const MAX_CARRIER_REFERENCE = '2147483647';

    private const INSTALLATION_HOOKS = [
        'displayHeader',
        'displayPaymentReturn',
        'paymentOptions',
        'displayOrderConfirmation',
        'displayWrapperTop',
    ];

    protected $installationCarrierRestrictionRows = [];
    protected $installationCarrierPreflightReady = false;
    protected $installationHasZeroCarrierReference = false;
    protected $installationPreflightActive = false;
    protected $installationConfigurationSnapshots = [];
    protected $installationCreatedOrderStates = [];
    protected $installationCreatedStateFiles = [];
    protected $installationRegisteredHooks = [];
    protected $installationTablesBefore = [];
    protected $installationCreatedMpModuleId = 0;
    protected $installationPreviousMpModule;
    protected $installationModuleWasInstalled = false;
    protected $installationParentStarted = false;
    protected $installationLockName = '';
    protected $installationStage = 'preflight';

    public function __construct()
    {
        $this->loadFiles();
        $this->mercadopago = MPApi::getInstance();
        $this->mpuseful = MPUseful::getInstance();

        $this->name = 'mercadopago';
        $this->tab = 'payments_gateways';
        $this->author = 'mercadopago';
        $this->need_instance = 1;
        $this->bootstrap = true;

        // Always update, because prestashop doesn't accept version coming from another variable (MP_VERSION)
        $this->version = '4.19.3';
        $this->ps_versions_compliancy = ['min' => '1.7.7.0', 'max' => '8.2.7'];

        parent::__construct();

        $this->displayName = $this->l('Mercado Pago');
        $this->description = $this->l('Customize the payment experience of your customers in your online store.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the module?');
        $this->module_key = '4380f33bbe84e7899aacb0b7a601376f';
        $this->ps_version = _PS_VERSION_;
        $this->assets_ext_min = !_PS_MODE_DEV_ ? '.min' : '';
        $this->path = $this->_path;
        $this->standardCheckout = new StandardCheckout($this);
        $this->customCheckout = new CustomCheckout($this);
        $this->ticketCheckout = new TicketCheckout($this);
        $this->pixCheckout = new PixCheckout($this);
        $this->pseCheckout = new PseCheckout();
    }

    /**
     * Load files
     *
     * @return void
     */
    public function loadFiles()
    {
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/MPApi.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/MPLog.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/MPUseful.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/MPRestCli.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/preference/StandardPreference.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/preference/WalletButtonPreference.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/MPModule.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/MPTransaction.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/MPTransaction.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/PSCartRule.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/PSCartRuleRule.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/PSOrderState.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/PSOrderStateLang.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/StandardCheckout.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/CustomCheckout.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/TicketCheckout.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/PixCheckout.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/PseCheckout.php';
    }

    /**
     * Install the module
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function install()
    {
        $this->resetInstallationTracking();

        // This validation must run before parent::install(), configuration writes,
        // module tables, order states or hooks create any persistent artifact.
        try {
            if (!$this->prepareCarrierRestrictionPreflight()) {
                return false;
            }
        } catch (Throwable $throwable) {
            $this->installationCarrierPreflightReady = false;
            $this->_errors[] = $this->l('Mercado Pago installation preflight failed.');

            return false;
        }

        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension ') .
                $this->l('on your server to install this module.');

            return false;
        }

        try {
            $this->installationStage = 'exclusive installation lock';
            if (!$this->acquireInstallationLock()) {
                throw new RuntimeException('The exclusive installation lock could not be acquired.');
            }

            $this->installationModuleWasInstalled = Module::isInstalled($this->name);
            $this->installationStage = 'configuration snapshot';
            if (!$this->snapshotParentPaymentConfiguration()) {
                throw new RuntimeException('The pre-install configuration could not be read.');
            }

            $this->installationStage = 'PrestaShop module registration';
            $this->installationParentStarted = true;
            if (!parent::install()) {
                throw new RuntimeException('PrestaShop could not register the module dependencies.');
            }

            $this->installationStage = 'PrestaShop payment configuration';
            if (!$this->ensureParentPaymentConfiguration()) {
                throw new RuntimeException('The PrestaShop payment configuration could not be saved.');
            }

            $this->installationStage = 'Mercado Pago database installation';
            if (!$this->captureInstallationTableState()) {
                throw new RuntimeException('The Mercado Pago table state could not be read.');
            }

            $record_installation_mp_module_id = function ($id_mp_module) {
                $this->recordInstallationMpModuleId($id_mp_module);
            };
            $record_installation_previous_mp_module = function (array $row) {
                $this->recordInstallationPreviousMpModule($row);
            };
            $sql_installed = include _PS_MODULE_DIR_ . 'mercadopago/sql/install.php';
            if ($sql_installed !== true) {
                $this->reportDatabaseError('The Mercado Pago database installation failed.');
                throw new RuntimeException('The Mercado Pago database installation failed.');
            }

            $this->installationStage = 'Mercado Pago configuration';
            if (!$this->configureInstallation()) {
                throw new RuntimeException('The Mercado Pago configuration could not be saved.');
            }

            $this->installationStage = 'Mercado Pago order states';
            if (!$this->createPaymentStates()) {
                throw new RuntimeException('The Mercado Pago order states could not be created.');
            }

            $this->installationStage = 'Mercado Pago hooks';
            if (!$this->registerInstallationHooks()) {
                throw new RuntimeException('The Mercado Pago hooks could not be registered.');
            }

            $this->installationStage = 'installation verification';
            if (!$this->validateInstallationIntegrity()) {
                throw new RuntimeException('The Mercado Pago installation verification failed.');
            }

            $this->installationCarrierPreflightReady = false;
            $this->generateInstallationLog(sprintf('Mercadopago plugin %s installed in the store', MP_VERSION));

            return true;
        } catch (Throwable $throwable) {
            return $this->abortInstallation($throwable);
        } finally {
            $this->releaseInstallationLock();
        }
    }

    /**
     * Install carrier restrictions using the same shop and carrier selection as
     * PrestaShop, while keeping duplicate retries atomic and idempotent.
     *
     * @param array $shops
     *
     * @return bool
     */
    public function addCheckboxCarrierRestrictionsForModule(array $shops = [])
    {
        $has_zero_reference = false;
        if (!$shops && $this->installationCarrierPreflightReady) {
            $rows = $this->installationCarrierRestrictionRows;
            $has_zero_reference = $this->installationHasZeroCarrierReference;
        } else {
            if (!$this->validateModuleCarrierPrimaryKey()) {
                return false;
            }

            $rows = $this->buildCarrierRestrictionRows($shops, $has_zero_reference);
            if ($rows === false) {
                return false;
            }
        }

        if ((int) $this->id <= 0) {
            $this->reportInstallationError('Carrier restrictions cannot be created without a valid module ID.');

            return false;
        }

        foreach ($rows as $row) {
            $inserted = Db::getInstance()->insert(
                'module_carrier',
                [
                    'id_module' => (int) $this->id,
                    'id_shop' => $row['id_shop'],
                    'id_reference' => $row['id_reference'],
                ],
                false,
                true,
                Db::ON_DUPLICATE_KEY
            );

            if (!$inserted) {
                $this->reportDatabaseError('Carrier restriction insertion failed.');

                return false;
            }
        }

        if ($has_zero_reference) {
            $this->generateInstallationLog(
                'Carrier restrictions contain id_reference 0; it will be preserved once per shop.',
                'warning'
            );
        }

        return true;
    }

    /**
     * Reset state used only to compensate a failed installation attempt.
     *
     * @return void
     */
    protected function resetInstallationTracking()
    {
        $this->installationCarrierRestrictionRows = [];
        $this->installationCarrierPreflightReady = false;
        $this->installationHasZeroCarrierReference = false;
        $this->installationPreflightActive = false;
        $this->installationConfigurationSnapshots = [];
        $this->installationCreatedOrderStates = [];
        $this->installationCreatedStateFiles = [];
        $this->installationRegisteredHooks = [];
        $this->installationTablesBefore = [];
        $this->installationCreatedMpModuleId = 0;
        $this->installationPreviousMpModule = null;
        $this->installationModuleWasInstalled = false;
        $this->installationParentStarted = false;
        $this->installationLockName = '';
        $this->installationStage = 'preflight';
    }

    /**
     * Serialize installation attempts for this module and database.
     *
     * @return bool
     */
    protected function acquireInstallationLock()
    {
        $this->installationLockName = 'mp_install_' . sha1(_DB_NAME_ . '|' . _DB_PREFIX_ . '|' . $this->name);
        $acquired = Db::getInstance()->getValue(
            "SELECT GET_LOCK('" . pSQL($this->installationLockName) . "', 10)",
            false
        );

        if ((int) $acquired !== 1) {
            $this->installationLockName = '';
            $this->reportInstallationError('Another Mercado Pago installation is already running.');

            return false;
        }

        return true;
    }

    /**
     * Release the connection-local installation lock without masking results.
     *
     * @return void
     */
    protected function releaseInstallationLock()
    {
        if ($this->installationLockName === '') {
            return;
        }

        $lock_name = $this->installationLockName;

        try {
            $released = Db::getInstance()->getValue(
                "SELECT RELEASE_LOCK('" . pSQL($lock_name) . "')",
                false
            );
            if ((int) $released !== 1) {
                $this->generateInstallationLog(
                    'The Mercado Pago installation lock could not be released.',
                    'warning'
                );
            }
        } catch (Throwable $throwable) {
            $this->generateInstallationLog('The Mercado Pago installation lock could not be released.', 'warning');
        }

        $this->installationLockName = '';
    }

    /**
     * Validate and cache carrier rows before installation creates side effects.
     *
     * @return bool
     */
    protected function prepareCarrierRestrictionPreflight()
    {
        $this->installationPreflightActive = true;
        $has_zero_reference = false;

        try {
            if (!$this->validateModuleCarrierPrimaryKey()) {
                return false;
            }

            $rows = $this->buildCarrierRestrictionRows([], $has_zero_reference);
            if ($rows === false) {
                return false;
            }
        } finally {
            $this->installationPreflightActive = false;
        }

        $this->installationCarrierRestrictionRows = $rows;
        $this->installationHasZeroCarrierReference = $has_zero_reference;
        $this->installationCarrierPreflightReady = true;

        return true;
    }

    /**
     * Confirm the unique key required by Db::ON_DUPLICATE_KEY is intact.
     *
     * @return bool
     */
    protected function validateModuleCarrierPrimaryKey()
    {
        $indexes = Db::getInstance()->executeS(
            'SHOW INDEX FROM `' . _DB_PREFIX_ . 'module_carrier`',
            true,
            false
        );
        if (!is_array($indexes)) {
            $this->reportDatabaseError('The module_carrier indexes could not be read.');

            return false;
        }

        $primary = [];
        foreach ($indexes as $index) {
            $key_name = isset($index['Key_name'])
                ? $index['Key_name']
                : (isset($index['key_name']) ? $index['key_name'] : null);
            if ($key_name !== 'PRIMARY') {
                continue;
            }

            $non_unique = isset($index['Non_unique'])
                ? $index['Non_unique']
                : (isset($index['non_unique']) ? $index['non_unique'] : null);
            $sequence = isset($index['Seq_in_index'])
                ? $index['Seq_in_index']
                : (isset($index['seq_in_index']) ? $index['seq_in_index'] : null);
            $column = isset($index['Column_name'])
                ? $index['Column_name']
                : (isset($index['column_name']) ? $index['column_name'] : null);

            if ((string) $non_unique !== '0' || !is_numeric($sequence) || !is_string($column)) {
                $this->reportInstallationError('The module_carrier primary key is invalid.');

                return false;
            }

            $primary[(int) $sequence] = $column;
        }

        ksort($primary);
        if (array_values($primary) !== ['id_module', 'id_shop', 'id_reference']) {
            $this->reportInstallationError(
                'The module_carrier primary key must contain id_module, id_shop and id_reference in that order.'
            );

            return false;
        }

        return true;
    }

    /**
     * Build one unique row for each active shop and carrier reference.
     *
     * @param array $shops
     * @param bool $has_zero_reference
     *
     * @return array|false
     */
    protected function buildCarrierRestrictionRows(array $shops = [], &$has_zero_reference = false)
    {
        if (!$shops) {
            $shops = Shop::getShops(true, null, true);
        }

        if (!is_array($shops)) {
            $this->reportInstallationError('The active shop list is invalid.');

            return false;
        }

        $shop_ids = [];
        foreach ($shops as $shop) {
            if (!$this->normalizeUnsignedInteger($shop, false, $id_shop)) {
                $this->reportInstallationError('A carrier restriction contains an invalid shop ID.');

                return false;
            }

            $shop_ids[$id_shop] = $id_shop;
        }

        $id_lang = (int) Context::getContext()->language->id;
        $carriers = Carrier::getCarriers($id_lang, false, false, false, null, Carrier::ALL_CARRIERS);

        if (!is_array($carriers)) {
            $this->reportInstallationError('The carrier list is invalid.');

            return false;
        }

        $references = [];
        $has_zero_reference = false;
        foreach ($carriers as $carrier) {
            if (!is_array($carrier) || !array_key_exists('id_reference', $carrier)) {
                $this->reportInstallationError('A carrier is missing its id_reference value.');

                return false;
            }

            if (!$this->normalizeUnsignedInteger($carrier['id_reference'], true, $id_reference)) {
                $this->reportInstallationError('A carrier contains an invalid id_reference value.');

                return false;
            }

            $references[$id_reference] = $id_reference;
            $has_zero_reference = $has_zero_reference || $id_reference === 0;
        }

        $rows = [];
        foreach ($shop_ids as $id_shop) {
            foreach ($references as $id_reference) {
                $key = $id_shop . ':' . $id_reference;
                $rows[$key] = [
                    'id_shop' => $id_shop,
                    'id_reference' => $id_reference,
                ];
            }
        }

        return array_values($rows);
    }

    /**
     * Normalize a strict decimal integer without allowing casts to hide errors.
     *
     * @param mixed $value
     * @param bool $allow_zero
     * @param int $normalized
     *
     * @return bool
     */
    protected function normalizeUnsignedInteger($value, $allow_zero, &$normalized)
    {
        if (is_int($value)) {
            if ($value < 0 || (!$allow_zero && $value === 0)) {
                return false;
            }

            $decimal = (string) $value;
        } elseif (is_string($value) && preg_match('/\A[0-9]+\z/', $value) === 1) {
            $decimal = ltrim($value, '0');
            $decimal = $decimal === '' ? '0' : $decimal;

            if (!$allow_zero && $decimal === '0') {
                return false;
            }
        } else {
            return false;
        }

        if (strlen($decimal) > strlen(self::MAX_CARRIER_REFERENCE)
            || (strlen($decimal) === strlen(self::MAX_CARRIER_REFERENCE)
                && strcmp($decimal, self::MAX_CARRIER_REFERENCE) > 0)
        ) {
            return false;
        }

        $normalized = (int) $decimal;

        return true;
    }

    /**
     * Snapshot the configuration keys PaymentModule::install() may create.
     *
     * @return bool
     */
    protected function snapshotParentPaymentConfiguration()
    {
        foreach (['FIXED', 'VAR', 'FIXED_FOREIGN', 'VAR_FOREIGN'] as $suffix) {
            if (!$this->snapshotInstallationConfiguration(
                'CONF_' . strtoupper($this->name) . '_' . $suffix
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verify the configuration writes that PaymentModule::install() does not
     * check. The database is authoritative because the core cache can contain
     * a value even when its insert/update failed.
     *
     * @return bool
     */
    protected function ensureParentPaymentConfiguration()
    {
        $keys = [
            'CONF_' . strtoupper($this->name) . '_FIXED',
            'CONF_' . strtoupper($this->name) . '_VAR',
            'CONF_' . strtoupper($this->name) . '_FIXED_FOREIGN',
            'CONF_' . strtoupper($this->name) . '_VAR_FOREIGN',
        ];

        foreach ($keys as $key) {
            $snapshot = $this->installationConfigurationSnapshots[$key];
            $persisted = $this->readEffectivePersistedConfiguration(
                $key,
                $snapshot['id_shop_group'],
                $snapshot['id_shop']
            );
            if ($persisted === false) {
                return false;
            }

            if (!$persisted['exists'] || !$persisted['value']) {
                $this->reportInstallationError('Payment configuration verification failed for ' . $key . '.');

                return false;
            }
        }

        return true;
    }

    /**
     * Read the persisted value visible in the current multistore context.
     * PrestaShop resolves configuration from shop to group to global scope.
     *
     * @param string $key
     * @param int|null $id_shop_group
     * @param int|null $id_shop
     *
     * @return array|false
     */
    protected function readEffectivePersistedConfiguration($key, $id_shop_group, $id_shop)
    {
        $scopes = [];
        if ($id_shop) {
            $scopes[] = [$id_shop_group, $id_shop];
        }
        if ($id_shop_group) {
            $scopes[] = [$id_shop_group, null];
        }
        $scopes[] = [null, null];

        foreach ($scopes as $scope) {
            $persisted = $this->readPersistedConfiguration($key, $scope[0], $scope[1]);
            if ($persisted === false || $persisted['exists']) {
                return $persisted;
            }
        }

        return ['exists' => false, 'value' => null];
    }

    /**
     * Read one exact configuration scope from the database, bypassing caches.
     *
     * @param string $key
     * @param int|null $id_shop_group
     * @param int|null $id_shop
     *
     * @return array|false
     */
    protected function readPersistedConfiguration($key, $id_shop_group, $id_shop)
    {
        if ($id_shop) {
            $scope = ' AND `id_shop` = ' . (int) $id_shop;
            $scope .= $id_shop_group
                ? ' AND `id_shop_group` = ' . (int) $id_shop_group
                : ' AND (`id_shop_group` IS NULL OR `id_shop_group` = 0)';
        } elseif ($id_shop_group) {
            $scope = ' AND `id_shop_group` = ' . (int) $id_shop_group
                . ' AND (`id_shop` IS NULL OR `id_shop` = 0)';
        } else {
            $scope = ' AND (`id_shop_group` IS NULL OR `id_shop_group` = 0)'
                . ' AND (`id_shop` IS NULL OR `id_shop` = 0)';
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `id_configuration`, `value` FROM `' . _DB_PREFIX_ . 'configuration`'
            . " WHERE `name` = '" . pSQL($key) . "'" . $scope,
            true,
            false
        );

        if (!is_array($rows)) {
            $this->reportDatabaseError('Payment configuration could not be read for ' . $key . '.');

            return false;
        }

        if (count($rows) === 0) {
            return ['exists' => false, 'value' => null];
        }

        if (count($rows) !== 1
            || !isset($rows[0]['id_configuration'])
            || (int) $rows[0]['id_configuration'] <= 0
            || !array_key_exists('value', $rows[0])
        ) {
            $this->reportInstallationError('Payment configuration is ambiguous for ' . $key . '.');

            return false;
        }

        return ['exists' => true, 'value' => $rows[0]['value']];
    }

    /**
     * Reload Configuration after unchecked core writes without reflection.
     *
     * @return void
     */
    protected function resetConfigurationRuntimeCache()
    {
        if (method_exists('Configuration', 'resetStaticCache')) {
            Configuration::resetStaticCache();

            return;
        }

        if (method_exists('Configuration', 'clearConfigurationCacheForTesting')) {
            Configuration::clearConfigurationCacheForTesting();
        }
    }

    /**
     * Configure Mercado Pago only after the module and its tables exist.
     *
     * @return bool
     */
    protected function configureInstallation()
    {
        $mp_currency = $this->getContextCurrencyIsoCode();
        if (!$this->updateInstallationConfiguration(
            'MERCADOPAGO_COUNTRY_LINK',
            $this->mpuseful->setMPCurrency($mp_currency)
        )) {
            return false;
        }

        $access_token = Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
        $sandbox_access_token = Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');
        if ($access_token != '' && $sandbox_access_token != ''
            && !$this->updateInstallationConfiguration('MERCADOPAGO_STANDARD_CHECKOUT', true)
        ) {
            return false;
        }

        $defaults = [
            'MERCADOPAGO_AUTO_RETURN' => true,
            'MERCADOPAGO_PROD_STATUS' => false,
            'MERCADOPAGO_INSTALLMENTS' => 24,
            'MERCADOPAGO_STANDARD' => false,
            'MERCADOPAGO_HOMOLOGATION' => false,
            'MERCADOPAGO_STANDARD_MODAL' => true,
            'MERCADOPAGO_CUSTOM_WALLET_BUTTON' => true,
            'MERCADOENVIOS_ACTIVATE' => false,
        ];

        foreach ($defaults as $key => $value) {
            if (!$this->updateInstallationConfiguration($key, $value)) {
                return false;
            }
        }

        foreach (['MERCADOPAGO_CARRIER', 'MERCADOPAGO_CARRIER_ID_1', 'MERCADOPAGO_CARRIER_ID_2'] as $key) {
            if (!$this->deleteInstallationConfiguration($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Save a scalar configuration value and retain its exact multistore scope.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    protected function updateInstallationConfiguration($key, $value)
    {
        if (!$this->snapshotInstallationConfiguration($key)) {
            return false;
        }
        $snapshot = $this->installationConfigurationSnapshots[$key];
        $updated = Configuration::updateValue(
            $key,
            $value,
            false,
            $snapshot['id_shop_group'],
            $snapshot['id_shop']
        );

        if (!$updated) {
            $this->reportDatabaseError('Configuration update failed for ' . $key . '.');
        }

        return (bool) $updated;
    }

    /**
     * Remove only the legacy configuration row in the current multistore scope.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function deleteInstallationConfiguration($key)
    {
        if (!$this->snapshotInstallationConfiguration($key)) {
            return false;
        }
        $snapshot = $this->installationConfigurationSnapshots[$key];

        if (!$snapshot['exists']) {
            return true;
        }

        return $this->deleteConfigurationAtScope(
            $key,
            $snapshot['id_shop_group'],
            $snapshot['id_shop']
        );
    }

    /**
     * Capture a configuration row once, without collapsing shop/group scopes.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function snapshotInstallationConfiguration($key)
    {
        if (array_key_exists($key, $this->installationConfigurationSnapshots)) {
            return true;
        }

        $scope = $this->getInstallationConfigurationScope();
        $id_shop_group = $scope['id_shop_group'];
        $id_shop = $scope['id_shop'];
        $persisted = $this->readPersistedConfiguration($key, $id_shop_group, $id_shop);
        if ($persisted === false) {
            return false;
        }

        $this->installationConfigurationSnapshots[$key] = [
            'exists' => $persisted['exists'],
            'value' => $persisted['value'],
            'id_shop_group' => $id_shop_group,
            'id_shop' => $id_shop,
        ];

        return true;
    }

    /**
     * Resolve the exact multistore scope used by installation configuration.
     *
     * @return array
     */
    protected function getInstallationConfigurationScope()
    {
        $id_shop_group = (int) Shop::getContextShopGroupID(true);
        $id_shop = (int) Shop::getContextShopID(true);

        return [
            'id_shop_group' => $id_shop_group > 0 ? $id_shop_group : null,
            'id_shop' => $id_shop > 0 ? $id_shop : null,
        ];
    }

    /**
     * Delete exactly one scoped configuration row.
     *
     * @param string $key
     * @param int|null $id_shop_group
     * @param int|null $id_shop
     *
     * @return bool
     */
    protected function deleteConfigurationAtScope($key, $id_shop_group, $id_shop)
    {
        $id_configuration = (int) Configuration::getIdByName($key, $id_shop_group, $id_shop);
        if ($id_configuration <= 0) {
            $this->resetConfigurationRuntimeCache();

            return true;
        }

        $where = '`id_configuration` = ' . $id_configuration;
        $deleted_lang = Db::getInstance()->delete('configuration_lang', $where);
        $deleted = Db::getInstance()->delete('configuration', $where);

        $this->resetConfigurationRuntimeCache();

        if (!$deleted_lang || !$deleted) {
            $this->reportDatabaseError('Configuration cleanup failed for ' . $key . '.');

            return false;
        }

        return true;
    }

    /**
     * Record whether module-owned tables existed before this attempt.
     *
     * @return bool
     */
    protected function captureInstallationTableState()
    {
        foreach (['mp_module', 'mp_transactions'] as $table) {
            $exists = $this->installationTableExists($table);
            if ($exists === null) {
                return false;
            }

            $this->installationTablesBefore[$table] = $exists;
        }

        return true;
    }

    /**
     * Check a fixed module table name without relying on LIKE wildcards.
     *
     * @param string $table
     *
     * @return bool|null
     */
    protected function installationTableExists($table)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `table_name` FROM `information_schema`.`tables`'
            . ' WHERE `table_schema` = DATABASE()'
            . " AND `table_name` = '" . pSQL(_DB_PREFIX_ . $table) . "'",
            true,
            false
        );

        if (!is_array($rows)) {
            $this->reportDatabaseError('The table state could not be read for ' . $table . '.');

            return null;
        }

        return count($rows) > 0;
    }

    /**
     * Let sql/install.php record the exact metadata row created by this attempt.
     *
     * @param int $id_mp_module
     *
     * @return void
     */
    protected function recordInstallationMpModuleId($id_mp_module)
    {
        $this->installationCreatedMpModuleId = (int) $id_mp_module;
    }

    /**
     * Retain the old version marker so rollback can restore it.
     *
     * @param array $row
     *
     * @return void
     */
    protected function recordInstallationPreviousMpModule(array $row)
    {
        $this->installationPreviousMpModule = $row;
    }

    /**
     * Register and verify the five hooks owned by this module.
     *
     * @return bool
     */
    protected function registerInstallationHooks()
    {
        foreach (self::INSTALLATION_HOOKS as $hook) {
            $was_registered = (bool) $this->isRegisteredInHook($hook);
            if (!$was_registered && !$this->registerHook($hook)) {
                $this->reportDatabaseError('Hook registration failed for ' . $hook . '.');

                return false;
            }

            if (!$was_registered) {
                $this->installationRegisteredHooks[] = $hook;
            }

            if (!$this->isRegisteredInHook($hook)) {
                $this->reportInstallationError('Hook verification failed for ' . $hook . '.');

                return false;
            }
        }

        return true;
    }

    /**
     * Verify the complete installation contract before writing the success log.
     *
     * @return bool
     */
    protected function validateInstallationIntegrity()
    {
        foreach (self::INSTALLATION_HOOKS as $hook) {
            if (!$this->isRegisteredInHook($hook)) {
                $this->reportInstallationError('Required hook is missing after installation: ' . $hook . '.');

                return false;
            }
        }

        return $this->validatePaymentStates();
    }

    /**
     * Roll back only artifacts introduced by the current attempt.
     *
     * @param Throwable $throwable
     *
     * @return bool
     */
    protected function abortInstallation(Throwable $throwable)
    {
        $failure_message = 'Mercadopago installation failed during ' . $this->installationStage . '.';
        $this->_errors[] = $this->l('Mercado Pago installation failed during ') . $this->installationStage . '.';

        try {
            $this->rollbackInstallationAttempt();
        } catch (Throwable $rollback_throwable) {
            $this->generateInstallationLog(
                'Rollback could not finish after the installation failure.',
                'warning'
            );
        } finally {
            $this->installationCarrierPreflightReady = false;
        }

        $this->generateInstallationLog($failure_message, 'error');

        return false;
    }

    /**
     * Best-effort compensation that never replaces the original failure.
     *
     * @return void
     */
    protected function rollbackInstallationAttempt()
    {
        if ($this->installationParentStarted && !$this->installationModuleWasInstalled) {
            try {
                if (!parent::uninstall()) {
                    $this->generateInstallationLog(
                        'Rollback preserved retryable artifacts because the partial module could not be disabled.',
                        'warning'
                    );

                    return;
                }
            } catch (Throwable $throwable) {
                $this->generateInstallationLog(
                    'Rollback preserved retryable artifacts because the partial module could not be disabled.',
                    'warning'
                );

                return;
            }
        }

        foreach (array_reverse($this->installationRegisteredHooks) as $hook) {
            try {
                if (!$this->unregisterHook($hook)) {
                    $this->generateInstallationLog('Rollback could not unregister hook ' . $hook . '.', 'warning');
                }
            } catch (Throwable $throwable) {
                $this->generateInstallationLog('Rollback could not unregister hook ' . $hook . '.', 'warning');
            }
        }

        $preserved_configurations = $this->rollbackCreatedOrderStates();
        $this->restoreInstallationConfigurations($preserved_configurations);
        $this->rollbackInstallationDatabase();
    }

    /**
     * Restore configuration values changed in this attempt.
     *
     * @param array $preserved_configurations
     *
     * @return void
     */
    protected function restoreInstallationConfigurations(array $preserved_configurations)
    {
        foreach (array_reverse($this->installationConfigurationSnapshots, true) as $key => $snapshot) {
            if (isset($preserved_configurations[$key])) {
                continue;
            }

            try {
                if ($snapshot['exists']) {
                    $restored = Configuration::updateValue(
                        $key,
                        $snapshot['value'],
                        false,
                        $snapshot['id_shop_group'],
                        $snapshot['id_shop']
                    );
                } else {
                    $restored = $this->deleteConfigurationAtScope(
                        $key,
                        $snapshot['id_shop_group'],
                        $snapshot['id_shop']
                    );
                }

                if (!$restored) {
                    $this->generateInstallationLog(
                        'Rollback could not restore configuration ' . $key . '.',
                        'warning'
                    );
                }
            } catch (Throwable $throwable) {
                $this->generateInstallationLog(
                    'Rollback could not restore configuration ' . $key . '.',
                    'warning'
                );
            }
        }
    }

    /**
     * Remove only version metadata and tables created by this attempt.
     *
     * @return void
     */
    protected function rollbackInstallationDatabase()
    {
        if ($this->installationCreatedMpModuleId > 0
            || (isset($this->installationTablesBefore['mp_module']) && !$this->installationTablesBefore['mp_module'])
            || (isset($this->installationTablesBefore['mp_transactions'])
                && !$this->installationTablesBefore['mp_transactions'])
        ) {
            // A request that started before parent::uninstall() may still be
            // writing. Keep DDL and version metadata for the idempotent retry
            // instead of risking deletion of a concurrent transaction.
            $this->generateInstallationLog(
                'Rollback preserved Mercado Pago database artifacts for a safe retry.',
                'warning'
            );
        }
    }

    /**
     * Add an installation error without exposing database details to the UI.
     *
     * @param string $message
     *
     * @return void
     */
    protected function reportInstallationError($message)
    {
        if (!$this->installationPreflightActive) {
            $this->generateInstallationLog($message, 'error');
        }
        $this->_errors[] = $this->l($message);
    }

    /**
     * Log the actual database failure while keeping the UI error generic.
     *
     * @param string $message
     *
     * @return void
     */
    protected function reportDatabaseError($message)
    {
        if (!$this->installationPreflightActive) {
            $this->generateInstallationLog($message, 'error');
        }
        $this->_errors[] = $this->l($message);
    }

    /**
     * Keep diagnostic logging from changing installation or rollback control flow.
     *
     * @param string $message
     * @param string $severity
     *
     * @return void
     */
    protected function generateInstallationLog($message, $severity = 'informative')
    {
        try {
            MPLog::generate($message, $severity);
        } catch (Throwable $throwable) {
            // Installation integrity and compensation must not depend on the logger.
        }
    }

    /**
     * Get the ISO code of the currency currently held in the context.
     *
     * $this->context->currency can be null/non-object when the module runs
     * without a fully resolved store/currency context (e.g. install/upgrade
     * triggered from CLI via `prestashop:module install`), so this method
     * falls back to the shop's default currency instead of reading
     * iso_code directly from a possibly null object.
     *
     * @return string
     */
    protected function getContextCurrencyIsoCode()
    {
        // PHPStan's Context stub types $this->context->currency as non-null, but at
        // CLI install/upgrade time it can actually be null, so the runtime guard stays.
        if (isset($this->context->currency) && is_object($this->context->currency) && !empty($this->context->currency->iso_code)) { // @phpstan-ignore-line
            return $this->context->currency->iso_code;
        }

        $default_currency = Currency::getDefaultCurrency();

        if (is_object($default_currency) && !empty($default_currency->iso_code)) {
            return $default_currency->iso_code;
        }

        $default_currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));

        return !empty($default_currency->iso_code) ? $default_currency->iso_code : '';
    }

    /**
     * Uninstall the module
     *
     * @return bool
     */
    public function uninstall()
    {
        MPLog::generate('Mercadopago plugin uninstalled in the store');
        include _PS_MODULE_DIR_ . 'mercadopago/sql/uninstall.php';

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function getContent()
    {
        // add css to configuration page
        $this->context->controller->addCSS($this->_path . 'views/css/back' . $this->assets_ext_min . '.css');

        $this->context->smarty->assign('module_dir', $this->_path);

        // test flow
        $mp_transaction = new MPTransaction();
        $count_test = $mp_transaction->where('is_payment_test', '=', 1)->andWhere('received_webhook', '=', 1)->count();

        // return forms
        $store = '';
        $custom = '';
        $ticket = '';
        $standard = '';
        $pix = '';
        $pse = '';
        $this->loadSettings();
        new RatingSettings();

        $localization = new LocalizationSettings();
        $credentials = new CredentialsSettings();
        $homologation = new HomologationSettings();

        $localization = $this->renderForm($localization->submit, $localization->values, $localization->form);
        $credentials = $this->renderForm($credentials->submit, $credentials->values, $credentials->form);
        $homologation = $this->renderForm($homologation->submit, $homologation->values, $homologation->form);

        // variables for admin configuration
        $public_key = Configuration::get('MERCADOPAGO_PUBLIC_KEY');
        $homologated = Configuration::get('MERCADOPAGO_HOMOLOGATION');
        $country_link = Configuration::get('MERCADOPAGO_COUNTRY_LINK');
        $access_token = Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
        $sandbox_public_key = Configuration::get('MERCADOPAGO_SANDBOX_PUBLIC_KEY');
        $sandbox_access_token = Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');

        $pix_enabled = null;
        $country_id = null;

        if ($access_token != '' && $sandbox_access_token != '') {
            // verify if seller is homologated
            $credentialsWrapper = $this->mercadopago->getCredentialsWrapper($access_token);

            if ($homologated == false && $credentialsWrapper['homologated'] == true) {
                $homologated = Configuration::updateValue('MERCADOPAGO_HOMOLOGATION', true);
            }

            // return checkout forms
            $store = new StoreSettings();
            $standard = new StandardSettings();
            $custom = new CustomSettings();
            $ticket = new TicketSettings();
            $pix = new PixSettings();
            $pse = new PseSettings();

            $store = $this->renderForm($store->submit, $store->values, $store->form);
            $standard = $this->renderForm($standard->submit, $standard->values, $standard->form);
            $custom = $this->renderForm($custom->submit, $custom->values, $custom->form);
            $ticket = $this->renderForm($ticket->submit, $ticket->values, $ticket->form);
            $pix = $this->renderForm($pix->submit, $pix->values, $pix->form);
            $pse = $this->renderForm($pse->submit, $pse->values, $pse->form);

            $pix_enabled = $this->isEnabledPaymentMethod('pix');
            $country_id = $this->getSiteIdByCredentials($access_token);
        }

        $this->context->smarty->assign(
            [
                // module requirements
                'message' => self::$form_message,
                'form_alert' => self::$form_alert,
                'mp_version' => MP_VERSION,
                'url_base' => __PS_BASE_URI__,
                'log' => MPLog::getLogUrl(),
                'country_link' => $country_link,
                'application' => Configuration::get('MERCADOPAGO_APPLICATION_ID'),
                'standard_test' => Configuration::get('MERCADOPAGO_STANDARD'),
                'sandbox_status' => Configuration::get('MERCADOPAGO_PROD_STATUS'),
                'seller_protect_link' => $this->mpuseful->setSellerProtectLink($country_link),
                'psjLink' => $this->mpuseful->getCountryPsjLink($country_link),
                'pix_enabled' => $pix_enabled,
                'country_id' => $country_id,
                // credentials
                'public_key' => $public_key,
                'access_token' => $access_token,
                'sandbox_public_key' => $sandbox_public_key,
                'sandbox_access_token' => $sandbox_access_token,
                // test flow
                'count_test' => $count_test,
                'seller_homolog' => $homologated,
                // forms
                'country_form' => $localization,
                'credentials' => $credentials,
                'homolog_form' => $homologation,
                'store_form' => $store,
                'standard_form' => $standard,
                'custom_form' => $custom,
                'ticket_form' => $ticket,
                'pix_form' => $pix,
                'pse_form' => $pse,
            ]
        );

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
     * Load settings
     *
     * @return void
     */
    public function loadSettings()
    {
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/StoreSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/RatingSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/StandardSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/CustomSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/TicketSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/PixSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/CredentialsSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/LocalizationSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/HomologationSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/PseSettings.php';
        require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/CoreSdkSettings.php';
    }

    /**
     * Render forms
     *
     * @param $submit
     * @param $values
     * @param $form
     *
     * @return string
     */
    protected function renderForm($submit, $values, $form)
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->submit_action = $submit;
        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $values,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$form]);
    }

    /**
     * Create the payment states
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function createPaymentStates()
    {
        $order_states = [
            ['#ccfbff', $this->l('Transaction in Process'), 'in_process', '110010000'],
            ['#c9fecd', $this->l('Transaction Completed'), 'payment', '110010010'],
            ['#fec9c9', $this->l('Transaction Canceled'), 'order_canceled', '100010000'],
            ['#fec9c9', $this->l('Transaction Declined'), 'payment_error', '100010000'],
            ['#ffeddb', $this->l('Transaction Refunded'), 'refund', '100010000'],
            ['#c28566', $this->l('Transaction Chargedback'), 'charged_back', '100010000'],
            ['#b280b2', $this->l('Transaction in Mediation'), 'in_mediation', '100010000'],
            ['#fffb96', $this->l('Transaction Pending'), 'pending', '110010000'],
            ['#ccfbff', $this->l('Transaction Authorized'), 'authorized', '100010000'],
            ['#ffb0d9', $this->l('Transaction in Possible Fraud'), 'payment_error', '100010000'],
        ];

        foreach ($order_states as $key => $value) {
            $configuration_key = 'MERCADOPAGO_STATUS_' . $key;
            if (!$this->snapshotInstallationConfiguration($configuration_key)) {
                return false;
            }

            $snapshot = $this->installationConfigurationSnapshots[$configuration_key];
            $persisted_configuration = $this->readEffectivePersistedConfiguration(
                $configuration_key,
                $snapshot['id_shop_group'],
                $snapshot['id_shop']
            );
            if ($persisted_configuration === false) {
                return false;
            }

            $configured_state = $persisted_configuration['exists']
                ? $persisted_configuration['value']
                : false;
            if ($this->normalizeUnsignedInteger($configured_state, false, $id_order_state)
                && $this->isMercadoPagoOrderState($id_order_state)
            ) {
                if (!$this->ensurePaymentStateIcon($id_order_state, $configuration_key)) {
                    return false;
                }

                continue;
            }

            $order_state = new OrderState();
            $order_state->name = [];
            $order_state->template = [];
            $order_state->module_name = $this->name;
            $order_state->color = $value[0];
            $order_state->invoice = (bool) $value[3][0];
            $order_state->send_email = (bool) $value[3][1];
            $order_state->unremovable = (bool) $value[3][2];
            $order_state->hidden = (bool) $value[3][3];
            $order_state->logable = (bool) $value[3][4];
            $order_state->delivery = (bool) $value[3][5];
            $order_state->shipped = (bool) $value[3][6];
            $order_state->paid = (bool) $value[3][7];
            $order_state->deleted = (bool) $value[3][8];

            $order_state->name = array_fill(0, 10, $value[1]);
            $order_state->template = array_fill(0, 10, $value[2]);

            if (!$order_state->add() || (int) $order_state->id <= 0) {
                $this->reportDatabaseError('Order state creation failed for ' . $configuration_key . '.');

                return false;
            }

            $id_order_state = (int) $order_state->id;
            $configuration_updated = $this->updateInstallationConfiguration(
                $configuration_key,
                $id_order_state
            );
            $persisted = $this->readPersistedConfiguration(
                $configuration_key,
                $snapshot['id_shop_group'],
                $snapshot['id_shop']
            );

            if ($persisted === false) {
                // Persistence is uncertain. Preserve both artifacts instead of
                // deleting a state that a concurrent checkout may now resolve.
                $this->installationCreatedOrderStates[$id_order_state] = $configuration_key;

                return false;
            }

            $mapping_persisted = $persisted['exists']
                && (string) $persisted['value'] === (string) $id_order_state;
            if (!$mapping_persisted) {
                if ($configuration_updated) {
                    // The mapping was observable before another writer changed
                    // it. A checkout may already hold this state ID, so keep it.
                    $this->installationCreatedOrderStates[$id_order_state] = $configuration_key;
                    $this->reportInstallationError(
                        'Order state configuration verification failed for ' . $configuration_key . '.'
                    );
                } else {
                    $this->removeUnpublishedOrderState($order_state);
                }

                return false;
            }

            $this->installationCreatedOrderStates[$id_order_state] = $configuration_key;
            if (!$configuration_updated) {
                return false;
            }

            if (!$this->ensurePaymentStateIcon($id_order_state, $configuration_key)) {
                return false;
            }
        }

        return $this->validatePaymentStates();
    }

    /**
     * Ensure a configured state has the module icon, including retry recovery.
     *
     * @param int $id_order_state
     * @param string $configuration_key
     *
     * @return bool
     */
    protected function ensurePaymentStateIcon($id_order_state, $configuration_key)
    {
        $file = _PS_ROOT_DIR_ . '/img/os/' . (int) $id_order_state . '.gif';
        if (file_exists($file)) {
            return true;
        }

        if (!copy(dirname(__FILE__) . '/views/img/mp_icon.gif', $file)) {
            $this->reportInstallationError('Order state icon creation failed for ' . $configuration_key . '.');

            return false;
        }

        $this->installationCreatedStateFiles[(int) $id_order_state] = $file;

        return true;
    }

    /**
     * Remove a state that was never exposed through a persisted mapping.
     *
     * @param OrderState $order_state
     *
     * @return void
     */
    protected function removeUnpublishedOrderState(OrderState $order_state)
    {
        try {
            if (!$order_state->delete()) {
                $this->generateInstallationLog(
                    'The unpublished Mercado Pago order state could not be removed.',
                    'warning'
                );
            }
        } catch (Throwable $throwable) {
            $this->generateInstallationLog(
                'The unpublished Mercado Pago order state could not be removed.',
                'warning'
            );
        }
    }

    /**
     * Verify that all ten configuration keys reference distinct module states.
     *
     * @return bool
     */
    protected function validatePaymentStates()
    {
        $state_ids = [];
        $scope = $this->getInstallationConfigurationScope();
        for ($key = 0; $key < 10; ++$key) {
            $configuration_key = 'MERCADOPAGO_STATUS_' . $key;
            $persisted = $this->readEffectivePersistedConfiguration(
                $configuration_key,
                $scope['id_shop_group'],
                $scope['id_shop']
            );
            if ($persisted === false || !$persisted['exists']) {
                $this->reportInstallationError('Invalid Mercado Pago order state: ' . $configuration_key . '.');

                return false;
            }

            $configured_state = $persisted['value'];

            if (!$this->normalizeUnsignedInteger($configured_state, false, $id_order_state)
                || isset($state_ids[$id_order_state])
                || !$this->isMercadoPagoOrderState($id_order_state)
            ) {
                $this->reportInstallationError('Invalid Mercado Pago order state: ' . $configuration_key . '.');

                return false;
            }

            $state_ids[$id_order_state] = true;
        }

        return count($state_ids) === 10;
    }

    /**
     * Check that an order state exists, is active and belongs to this module.
     *
     * @param int $id_order_state
     *
     * @return bool
     */
    protected function isMercadoPagoOrderState($id_order_state)
    {
        $row = Db::getInstance()->getRow(
            'SELECT `id_order_state`, `module_name`, `deleted`'
            . ' FROM `' . _DB_PREFIX_ . 'order_state`'
            . ' WHERE id_order_state = ' . (int) $id_order_state,
            false
        );

        return is_array($row)
            && isset($row['id_order_state'])
            && (int) $row['id_order_state'] === (int) $id_order_state
            && isset($row['module_name'])
            && (string) $row['module_name'] === (string) $this->name
            && (!isset($row['deleted']) || (int) $row['deleted'] === 0);
    }

    /**
     * Preserve new states and their configuration for an idempotent retry.
     *
     * A checkout that started before the module was disabled can begin using a
     * state at any point, so a check-then-delete rollback is never safe.
     *
     * @return array
     */
    protected function rollbackCreatedOrderStates()
    {
        $preserved_configurations = [];

        foreach (array_reverse($this->installationCreatedOrderStates, true) as $id_order_state => $configuration_key) {
            $preserved_configurations[$configuration_key] = true;
            $this->generateInstallationLog(
                'Rollback preserved order state ' . (int) $id_order_state . ' for a safe retry.',
                'warning'
            );
        }

        return $preserved_configurations;
    }

    /**
     * Check if the state exist before create another one
     *
     * @param int $id_order_state
     *
     * @return int
     */
    public static function orderStateAvailable($id_order_state)
    {
        $query = 'SELECT COUNT(*) AS count_state FROM ' . _DB_PREFIX_ . 'order_state
            WHERE id_order_state = ' . (int) $id_order_state;
        $result = Db::getInstance()->getRow($query);

        return (int) $result['count_state'];
    }

    /**
     * Return null for Mercado Envios
     *
     * @return void
     */
    public function getOrderShippingCost()
    {
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO
     *
     * @return void
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/front' . $this->assets_ext_min . '.css');
        $this->context->controller->addCSS($this->_path . 'views/css/pixFront' . $this->assets_ext_min . '.css');
        $this->context->controller->addCSS($this->_path . 'views/css/pse' . $this->assets_ext_min . '.css');
        $this->context->controller->addJS($this->_path . 'views/js/front' . $this->assets_ext_min . '.js');
    }

    /**
     * Show payment options in version 1.7
     *
     * @param $params
     *
     * @return array|string|void
     */
    public function hookPaymentOptions($params)
    {
        return $this->loadPayments($params);
    }

    /**
     * @param $params
     *
     * @return array|void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function loadPayments($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $cart = $this->context->cart;
        $paymentOptions = [];

        $country = Configuration::get('MERCADOPAGO_COUNTRY_LINK');

        $checkouts = [
            'MERCADOPAGO_STANDARD_CHECKOUT' => 'getStandardCheckout',
            'MERCADOPAGO_CUSTOM_CHECKOUT' => 'getCustomCheckout',
            'MERCADOPAGO_TICKET_CHECKOUT' => 'getTicketCheckout',
            'MERCADOPAGO_PIX_CHECKOUT' => 'getPixCheckout',
            'MERCADOPAGO_PSE_CHECKOUT' => 'getPseCheckout',
        ];

        foreach ($checkouts as $checkout => $method) {
            if ($this->isActiveCheckout($checkout) && $this->isAvailableToCountry($checkout, $country)) {
                $paymentOptions[] = $this->{$method}($cart);
            } else {
                $this->disableCheckout($checkout);
            }
        }

        return $paymentOptions;
    }

    /**
     * @param $checkout
     *
     * @return bool
     */
    public function isActiveCheckout($checkout)
    {
        return Configuration::get($checkout) == true;
    }

    /**
     * @param $checkout
     * @param $country
     *
     * @return bool
     */
    public function isAvailableToCountry($checkout, $country)
    {
        $checkoutsWithCountryRestriction = [
            'MERCADOPAGO_PIX_CHECKOUT',
            PseCheckout::PSE_CHECKOUT_NAME,
        ];

        if (!in_array($checkout, $checkoutsWithCountryRestriction)) {
            return true;
        }

        if ($country === 'mlb'
            && $checkout === 'MERCADOPAGO_PIX_CHECKOUT'
            && $this->isEnabledPaymentMethod('pix')
        ) {
            return true;
        }

        if ($this->pseCheckout->isAvailableToCountry($country)
            && $checkout === PseCheckout::PSE_CHECKOUT_NAME
            && $this->isEnabledPaymentMethod(PseCheckout::PAYMENT_METHOD_NAME)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param $checkout
     *
     * @return bool
     */
    public function isEnabledPaymentMethod($checkout)
    {
        $paymentMethods = $this->mercadopago->getPaymentMethods();
        if (is_array($paymentMethods)) {
            foreach ($paymentMethods as $paymentMethod) {
                if (Tools::strtolower($paymentMethod['id']) == $checkout) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $accessToken
     *
     * @return string
     */
    public function getSiteIdByCredentials($accessToken)
    {
        $response = $this->mercadopago->isValidAccessToken($accessToken);

        return $response ? Tools::strtolower($response['site_id']) : null;
    }

    /**
     * @param $checkout
     *
     * @return void
     */
    public function disableCheckout($checkout)
    {
        Configuration::updateValue($checkout, false);
    }

    /**
     * @param $cart
     *
     * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption
     */
    public function getStandardCheckout($cart)
    {
        $frontInformations = $this->standardCheckout->getStandardCheckoutPS17($cart);
        $this->context->smarty->assign($frontInformations);
        $infoTemplate = $this->context->smarty->fetch('module:mercadopago/views/templates/hook/seven/standard.tpl');
        $standardCheckout = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $standardCheckout->setForm($infoTemplate)
            ->setCallToActionText($this->l('Mercado Pago'))
            ->setLogo('https://http2.mlstatic.com/storage/cpp/static-files/306698cd-ff92-4cc0-801c-1ca35d06ed5a.png');

        return $standardCheckout;
    }

    /**
     * @param $cart
     *
     * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption
     */
    public function getCustomCheckout($cart)
    {
        $discount = Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT');
        $str_discount = ' (' . $discount . '% OFF) ';
        $str_discount = ($discount != '') ? $str_discount : '';

        $frontInformations = $this->customCheckout->getCustomCheckoutPS17($cart);
        $this->context->smarty->assign($frontInformations);
        $infoTemplate = $this->context->smarty->fetch('module:mercadopago/views/templates/hook/seven/custom.tpl');
        $customCheckout = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $customCheckout->setForm($infoTemplate)
            ->setCallToActionText($this->l('Credit or debit card') . $str_discount);

        return $customCheckout;
    }

    /**
     * @param $cart
     *
     * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption
     */
    public function getTicketCheckout($cart)
    {
        $discount = Configuration::get('MERCADOPAGO_TICKET_DISCOUNT');
        $str_discount = ' (' . $discount . '% OFF) ';
        $str_discount = ($discount != '') ? $str_discount : '';

        $frontInformations = $this->ticketCheckout->getTicketCheckoutPS17($cart);
        $this->context->smarty->assign($frontInformations);
        $infoTemplate = $this->context->smarty->fetch('module:mercadopago/views/templates/hook/seven/ticket.tpl');
        $ticketCheckout = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $ticketCheckout->setForm($infoTemplate)
            ->setCallToActionText($this->l('Pay with payment methods in cash') . $str_discount);

        return $ticketCheckout;
    }

    /**
     * @param $cart
     *
     * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption
     */
    public function getPixCheckout($cart)
    {
        $discount = Configuration::get('MERCADOPAGO_PIX_DISCOUNT');

        $strDiscount = ' (' . $discount . '% OFF) ';
        $strDiscount = ($discount != '') ? $strDiscount : '';

        $frontInformations = $this->pixCheckout->getPixCheckoutPS17();
        $this->context->smarty->assign($frontInformations);
        $infoTemplate = $this->context->smarty->fetch('module:mercadopago/views/templates/hook/seven/pix.tpl');
        $pixCheckout = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $pixCheckout->setForm($infoTemplate)
            ->setCallToActionText($this->l('Pix') . $strDiscount);

        return $pixCheckout;
    }

    /**
     * @param $cart
     *
     * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption
     */
    public function getPseCheckout($cart)
    {
        $pluginInfos = [
            'redirect_link' => $this->context->link->getModuleLink($this->name, PseCheckout::PAYMENT_METHOD_NAME),
            'module_dir' => $this->path,
        ];
        $paymentMethods = $this->mercadopago->getPaymentMethods();
        $templateData = $this->pseCheckout->getPseTemplateData($paymentMethods, $pluginInfos);
        $this->context->smarty->assign($templateData);
        $infoTemplate = $this->context->smarty->fetch('module:mercadopago/views/templates/hook/seven/pse.tpl');
        $psePaymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $psePaymentOption->setForm($infoTemplate)
            ->setCallToActionText($this->l('PSE') . ' ' . $this->pseCheckout->getDiscountBanner())
            ->setLogo(_MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png');

        return $psePaymentOption;
    }

    /**
     * Check currency
     *
     * @param mixed $cart
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * This hook is used to display the order confirmation page.
     *
     * @param mixed $params
     *
     * @return string
     */
    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active) {
            return '';
        }

        $paymentId = Tools::getValue('payment_id');
        $payment = is_string($paymentId) ? $this->mercadopago->getPaymentStandard($paymentId) : [];

        return $this->getPaymentReturn($payment, $params);
    }

    /**
     * Get template of payment confirmation
     *
     * @param mixed $payment
     * @param mixed $params
     *
     * @return string
     */
    public function getPaymentReturn($payment, $params)
    {
        $order = array_key_exists('objOrder', $params) ? $params['objOrder'] : null;
        $products = !is_null($order) ? $order->getProducts() : null;
        // This hook always runs in a web request with a resolved currency context, but we
        // still use the defensive helper for consistency and as a safety net.
        $mp_currency = $this->getContextCurrencyIsoCode();
        if (isset($payment['transaction_details']['total_paid_amount']) && isset($payment['transaction_amount']) && isset($payment['transaction_details']['installment_amount'])) {
            $cost_of_installments = $payment['transaction_details']['total_paid_amount'] - $payment['transaction_amount'];
            $cost_of_installments_formated = $this->context->currentLocale->formatPrice($cost_of_installments, $mp_currency);
            $total_paid_amount = $this->context->currentLocale->formatPrice($payment['transaction_details']['total_paid_amount'], $mp_currency);
            $installment_amount = $this->context->currentLocale->formatPrice($payment['transaction_details']['installment_amount'], $mp_currency);
        }

        $this->context->smarty->assign(
            [
                'order' => $order,
                'payment' => $payment,
                'order_products' => $products,
                'pix_expiration' => $this->getPixExpiration(),
                'cost_of_installments' => isset($cost_of_installments) ? $cost_of_installments : null,
                'cost_of_installments_formated' => isset($cost_of_installments_formated) ? $cost_of_installments_formated : null,
                'total_paid_amount' => isset($total_paid_amount) ? $total_paid_amount : null,
                'installment_amount' => isset($installment_amount) ? $installment_amount : null,
            ]
        );

        return $this->display(__FILE__, 'views/templates/hook/seven/payment_return.tpl');
    }

    /**
     * Get pix expiration
     *
     * @return string
     */
    public function getPixExpiration()
    {
        $pixExpiration = Configuration::get('MERCADOPAGO_PIX_EXPIRATION');
        $expiration = [
            '30' => '30 ' . $this->l('minutes'),
            '60' => '1 ' . $this->l('hour'),
            '360' => '6 ' . $this->l('hours'),
            '720' => '12 ' . $this->l('hours'),
            '1440' => '1 ' . $this->l('day'),
            '10080' => '7 ' . $this->l('days'),
        ];

        return is_string($pixExpiration) ? $expiration[$pixExpiration] : $expiration['30'];
    }

    /**
     * This hook is used to display in order confirmation page.
     *
     * @param mixed $params
     *
     * @return string
     */
    public function hookDisplayOrderConfirmation($params)
    {
        $order = isset($params['order']) ? $params['order'] : $params['objOrder'];
        $checkout_type = Tools::getIsset('checkout_type') ? Tools::getValue('checkout_type') : null;
        // This hook always runs in a web request with a resolved currency context, but we
        // still use the defensive helper for consistency and as a safety net.
        $mp_currency = $this->getContextCurrencyIsoCode();
        $total_paid_amount = $this->context->currentLocale->formatPrice($order->total_paid, $mp_currency);

        $this->context->smarty->assign(
            [
                'checkout_type' => $checkout_type,
                'total_paid_amount' => $total_paid_amount,
            ]
        );

        return $this->display(__FILE__, 'views/templates/hook/seven/order_confirmation.tpl');
    }

    /**
     * Display payment failure on version 1.7
     *
     * @return string
     */
    public function hookDisplayWrapperTop()
    {
        return $this->getDisplayFailure();
    }

    /**
     * @return mixed
     */
    public function getDisplayFailure()
    {
        if (Tools::getValue('typeReturn') == 'failure') {
            $cookie = $this->context->cookie;
            if ($cookie->__isset('redirect_message')) {
                $this->context->smarty->assign(['redirect_message' => $cookie->__get('redirect_message')]);
                $cookie->__unset('redirect_message');
            }

            return $this->display(__FILE__, 'views/templates/hook/failure.tpl');
        }
    }

    /**
     * @param $sql_file
     *
     * @return bool
     */
    public function loadSQLFile($sql_file)
    {
        // Get install SQL file content
        $sql_content = Tools::file_get_contents($sql_file);

        // Replace prefix and store SQL command in array
        $sql_content = str_replace('PREFIX_', _DB_PREFIX_, $sql_content);
        $sql_requests = preg_split("/;\s*[\r\n]+/", $sql_content);

        // Execute each SQL statement
        $result = true;
        foreach ($sql_requests as $request) {
            if (!empty($request)) {
                $result &= Db::getInstance()->execute(trim($request));
            }
        }

        // Return result
        return $result;
    }
}
