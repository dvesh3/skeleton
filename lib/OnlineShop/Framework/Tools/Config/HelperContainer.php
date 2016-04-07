<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace OnlineShop\Framework\Tools\Config;

/**
 * Class \OnlineShop\Framework\Tools\Config\HelperContainer
 *
 * Helper class for online shop config in combination with tenants
 *
 * tries to use config for current checkout tenant, uses default config if corresponding root attribute is not set
 *
 */
class HelperContainer {

    /**
     * @var \Zend_Config
     */
    protected $defaultConfig;

    /**
     * @var \Zend_Config[]
     */
    protected $tenantConfigs;

    /**
     * @param \Zend_Config $config     -> configuration to contain
     * @param string      $identifier -> cache identifier for caching sub files
     */
    public function __construct(\Zend_Config $config, $identifier) {
        $this->defaultConfig = $config;

        if (!$config->tenants || empty($config->tenants)) {
            return;
        }

        foreach($config->tenants->toArray() as $tenantName => $tenantConfig) {

            $tenantConfig = $config->tenants->{$tenantName};
            if($tenantConfig instanceof \Zend_Config) {
                if($tenantConfig->file) {

                    $cacheKey = "onlineshop_config_" . $identifier . "_checkout_tenant_" . $tenantName;

                    if(!$tenantConfigFile =  \Pimcore\Model\Cache::load($cacheKey)) {
                        $tenantConfigFile = new \Zend_Config_Xml(PIMCORE_DOCUMENT_ROOT . ((string)$tenantConfig->file), null, true);
                        $tenantConfigFile = $tenantConfigFile->tenant;
                        \Pimcore\Model\Cache::save($tenantConfigFile, $cacheKey, array("ecommerceconfig"), 9999);
                    }

                    $this->tenantConfigs[$tenantName] = $tenantConfigFile;
                } else {
                    $this->tenantConfigs[$tenantName] = $tenantConfig;
                }
            }
        }
    }



    public function __get($name) {
        $currentCheckoutTenant = \OnlineShop\Framework\Factory::getInstance()->getEnvironment()->getCurrentCheckoutTenant();

        if($currentCheckoutTenant && $this->tenantConfigs[$currentCheckoutTenant]) {
            $option = $this->tenantConfigs[$currentCheckoutTenant]->$name;
            if($option) {
                return $option;
            }
        }

        return $this->defaultConfig->$name;
    }





}
