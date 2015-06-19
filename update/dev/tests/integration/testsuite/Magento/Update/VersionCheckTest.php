<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Update;

/**
 * Integration test for \Magento\Update\VersionCheck
 */
class VersionCheckTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Update\versionCheck
     */
    protected $versionCheck;

    protected function setUp()
    {
        $composerConfigFileDir = __DIR__ . '/_files';
        $this->versionCheck = new \Magento\Update\VersionCheck($composerConfigFileDir);
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->versionCheck);
    }

    public function testVersionCheck()
    {
        $composerFilePath = MAGENTO_BP . '/composer.json';
        $fileContent = file_get_contents($composerFilePath);
        $currentReleaseVersion = json_decode($fileContent, true)['version'];

        $this->assertNotEmpty($this->versionCheck->getAvailableVersions());

        $this->assertEquals($currentReleaseVersion, $this->versionCheck->getLatestProductVersion());

        $this->assertEquals('dev-master', $this->versionCheck->getLatestDevelopmentVersion());
    }
}
