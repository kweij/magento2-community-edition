<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Update;

use Magento\Update\Queue\JobUpdate\ComposerManager;

/**
 * Utility class that checks available versions for Magento Community Edition
 */
class VersionCheck
{
    const DEFAULT_PACKAGE = 'magento/product-community-edition';

    /**
     * @var string
     */
    protected $composerConfigFileDir;

    /**
     * @var array
     */
    protected $availableVersions;

    /**
     * @var \Magento\Update\Queue\JobUpdate\ComposerManager
     */
    protected $composerManager;

    /**
     * @var string
     */
    protected $package;

    /**
     * Initialize dependencies.
     *
     * @param string|null $package Name of package
     * @param string|null $composerConfigFileDir
     * @param \Magento\Update\Queue\JobUpdate\ComposerManager|null $composerManager
     */
    public function __construct(
        $package = self::DEFAULT_PACKAGE,
        $composerConfigFileDir = MAGENTO_BP,
        ComposerManager $composerManager = null
    ) {
        $this->package = $package;
        $this->composerConfigFileDir = $composerConfigFileDir;
        $this->composerManager = $composerManager
            ? $composerManager
            : new ComposerManager(
                $this->composerConfigFileDir
            );
    }

    /**
     * Retrieve all available versions for a package
     *
     * @return array
     */
    public function getAvailableVersions()
    {
        $this->availableVersions = $this->composerManager->getAvailableVersions($this->package);
        return $this->availableVersions;
    }

    /**
     * Retrieve the latest available product version for magento/product-community-edition package
     *
     * @return string
     */
    public function getLatestProductVersion()
    {
        $availableVersions = isset($this->availableVersions) ? $this->availableVersions : $this->getAvailableVersions();

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
        $availableVersions = isset($this->availableVersions) ? $this->availableVersions : $this->getAvailableVersions();

        foreach ($availableVersions as $version) {
            if (substr($version, 0, 3) === 'dev') {
                return $version;
            }
        }

        return '';
    }
}
