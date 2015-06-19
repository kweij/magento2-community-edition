<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Update;

/**
 * Utility class that checks available versions for Magento Community Edition
 */
class VersionCheck
{
    /**
     * @var string
     */
    protected $composerConfigFileDir;

    /**
     * @var array
     */
    protected $availableVersions;

    /**
     * Initialize dependencies.
     *
     * @param string|null $composerConfigFileDir
     */
    public function __construct($composerConfigFileDir = null)
    {
        $this->composerConfigFileDir = $composerConfigFileDir ? $composerConfigFileDir : MAGENTO_BP;
    }

    /**
     * Retrieve all available versions for magento/product-community-edition package
     *
     * @return array
     */
    public function getAvailableVersions()
    {
        $vendorName = 'magento';
        $packageName = 'product-community-edition';

        $fullCommand = sprintf(
            'cd %s && php -f %s/vendor/composer/composer/bin/composer show %s/%s | grep "versions"',
            $this->composerConfigFileDir,
            UPDATER_BP,
            $vendorName,
            $packageName
        );

        exec($fullCommand, $output, $return);

        if ($return) {
            throw new \RuntimeException(sprintf('Command "%s" failed: %s', $fullCommand, join("\n", $output)));
        }

        $this->availableVersions = explode(', ', str_replace('versions : ', '', $output[0]));
        return $this->availableVersions;
    }

    /**
     * Retrieve the latest available product version for magento/product-community-edition package
     *
     * @return string
     */
    public function getLatestProductVersion()
    {
        $availableVersions =
            isset($this->availableVersions) ? $this->availableVersions : $this->getAvailableVersions();

        foreach ($availableVersions as $version) {
            if (substr($version, 0, 3) !== 'dev') {
                return $version;
            }
        }

        return '';
    }

    /**
     * Retrieve the latest available development version for magento/product-community-edition package
     *
     * @return string
     */
    public function getLatestDevelopmentVersion()
    {
        $availableVersions =
            isset($this->availableVersions) ? $this->availableVersions: $this->getAvailableVersions();

        foreach ($availableVersions as $version) {
            if (substr($version, 0, 3) === 'dev') {
                return $version;
            }
        }

        return '';
    }
}
