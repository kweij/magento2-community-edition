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

    /**
     * @var \Magento\Update\Queue\JobUpdate\ComposerManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $composerManagerMock;

    /**
     * @var array
     */
    protected $versions;

    protected function setUp()
    {
        $this->versions = ['dev-master', '0.74.0-beta3', '0.74.0-beta2', '0.74.0-beta1'];
        $this->composerManagerMock = $this->getMockBuilder('Magento\Update\Queue\JobUpdate\ComposerManager')
            ->setMethods(['getAvailableVersions'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->composerManagerMock->expects($this->once())
            ->method('getAvailableVersions')
            ->willReturn($this->versions);
        $this->versionCheck = new \Magento\Update\VersionCheck(null, null, $this->composerManagerMock);
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->versionCheck);
    }

    public function testGetAvailableVersions()
    {
        $actualVersions = $this->versionCheck->getAvailableVersions();
        $this->assertNotEmpty($actualVersions);
        $this->assertCount(count($this->versions), $actualVersions);
    }

    public function testGetLatestDevelopmentVersion()
    {
        $this->assertEquals($this->versions[0], $this->versionCheck->getLatestDevelopmentVersion());
    }

    public function testGetLatestProductVersion()
    {
        $this->assertEquals($this->versions[1], $this->versionCheck->getLatestProductVersion());
    }
}
