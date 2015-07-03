<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Update\Queue\JobUpdate;

class ComposerManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var string */
    protected $composerConfigFileDir;

    /** @var string */
    protected $composerConfigFilePath;

    /** @var string */
    protected $expectedRequireDirectiveParam;

    /** @var string */
    protected $composerContent;

    /**
     * @var \Magento\Update\Queue\JobUpdate\ComposerManager
     */
    protected $composerManager;

    protected function setUp()
    {
        parent::setUp();
        $this->composerConfigFileDir = TESTS_TEMP_DIR;
        $this->composerConfigFilePath = $this->composerConfigFileDir . '/composer.json';
        copy(__DIR__ . '/../../_files/composer.json', $this->composerConfigFilePath);
        $this->expectedRequireDirectiveParam = [
            [ComposerManager::PACKAGE_NAME => "php", ComposerManager::PACKAGE_VERSION => "~5.6.0"],
            [ComposerManager::PACKAGE_NAME => "composer/composer", ComposerManager::PACKAGE_VERSION => "1.0.0-alpha8"],
            [
                ComposerManager::PACKAGE_NAME => "magento/product-community-edition",
                ComposerManager::PACKAGE_VERSION => "0.74.0-beta12"
            ]
        ];
        $this->composerManager = new ComposerManager($this->composerConfigFileDir);
    }

    protected function tearDown()
    {
        parent::tearDown();
        unlink($this->composerConfigFilePath);
    }

    public function testUpdateComposerConfigFile()
    {
        $this->composerManager->updateComposerConfigFile('require', $this->expectedRequireDirectiveParam);
        $expectedRequireDirective = [
            "php" => "~5.6.0",
            "composer/composer" => "1.0.0-alpha8",
            "magento/product-community-edition" => "0.74.0-beta12"
        ];
        $actualRequireDirective = json_decode(file_get_contents($this->composerConfigFilePath), true)['require'];
        $this->assertEquals($expectedRequireDirective, $actualRequireDirective);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Composer directive "nonSupport" is not supported
     */
    public function testUpdateComposerConfigFileNonSupportedDirective()
    {
        $this->composerManager->updateComposerConfigFile('nonSupport', $this->expectedRequireDirectiveParam);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Incorrect/missing parameters for composer directive "require"
     */
    public function testUpdateComposerConfigFileMissedParam()
    {
        $expectedRequireDirectiveParam = [
            [ComposerManager::PACKAGE_NAME => "php"],
        ];
        $this->composerManager->updateComposerConfigFile('require', $expectedRequireDirectiveParam);
    }

    public function testUpdateComposerConfigFileAddNewDependency()
    {
        $testPackageName = 'magento/module-admin-notification';
        $testPackageVersion = '0.74.0-beta2';
        $expectedRequireDirectiveParam = [
            [
                ComposerManager::PACKAGE_NAME => $testPackageName,
                ComposerManager::PACKAGE_VERSION => $testPackageVersion
            ]
        ];
        $this->composerManager->updateComposerConfigFile('require', $expectedRequireDirectiveParam);
        $fileJsonFormat = json_decode(file_get_contents($this->composerConfigFilePath), true);

        // Assert that dependency is removed from "replace"
        $this->assertEmpty($fileJsonFormat['replace']);

        // Assert that dependency is added to "require"
        $actualRequireDirective = $fileJsonFormat['require'];
        $this->assertTrue(array_key_exists($testPackageName, $actualRequireDirective));
        $this->assertEquals($testPackageVersion, $actualRequireDirective[$testPackageName]);
    }

    public function testGetAvailableVersions()
    {
        $package = 'magento/product-community-edition';
        $actualVersions = $this->composerManager->getAvailableVersions($package);
        $this->assertNotEmpty($actualVersions);
        $this->assertContains('dev-master', $actualVersions);
    }
}
