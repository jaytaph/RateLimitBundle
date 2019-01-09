<?php

namespace Noxlogic\RateLimitBundle\Tests\DependencyInjection;

use Noxlogic\RateLimitBundle\DependencyInjection\Configuration;
use Noxlogic\RateLimitBundle\Whitelisting\WhitelistInterface;
use Symfony\Bundle\FrameworkBundle\Tests\Functional\WebTestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\Request;

/**
 * ConfigurationTest
 */
class ConfigurationTest extends WebTestCase
{
    /**
     * @var Processor
     */
    private $processor;

    public function setUp()
    {
        $this->processor = new Processor();
    }

    private function getConfigs(array $configArray)
    {
        $configuration = new Configuration();

        return $this->processor->processConfiguration($configuration, array($configArray));
    }

    public function testUnconfiguredConfiguration()
    {
        $configuration = $this->getConfigs(array());

        $this->assertSame(array(
            'enabled' => true,
            'storage_engine' => 'redis',
            'redis_client' => 'default_client',
            'redis_service' => null,
            'php_redis_service' => null,
            'memcache_client' => 'default',
            'memcache_service' => null,
            'doctrine_provider' => null,
            'doctrine_service' => null,
            'simple_cache_service' => null,
            'cache_service' => null,
            'rate_response_code' => 429,
            'rate_response_exception' => null,
            'rate_response_message' => 'You exceeded the rate limit',
            'display_headers' => true,
            'headers' => array(
                'limit' => 'X-RateLimit-Limit',
                'remaining' => 'X-RateLimit-Remaining',
                'reset' => 'X-RateLimit-Reset',
            ),
            'path_limits' => array(),
            'whitelist_interface' => null,
            'fos_oauth_key_listener' => true
        ), $configuration);
    }

    public function testDisabledConfiguration()
    {
        $configuration = $this->getConfigs(array('enabled' => false));

        $this->assertArrayHasKey('enabled', $configuration);
        $this->assertFalse($configuration['enabled']);
    }

    public function testPathLimitConfiguration()
    {
        $pathLimits = array(
            'api' => array(
                'path' => 'api/',
                'methods' => array('GET'),
                'limit' => 100,
                'period' => 60
            )
        );

        $configuration = $this->getConfigs(array(
            'path_limits' => $pathLimits
        ));

        $this->assertArrayHasKey('path_limits', $configuration);
        $this->assertEquals($pathLimits, $configuration['path_limits']);
    }

    public function testMultiplePathLimitConfiguration()
    {
        $pathLimits = array(
            'api' => array(
                'path' => 'api/',
                'methods' => array('GET', 'POST'),
                'limit' => 200,
                'period' => 10
            ),
            'api2' => array(
                'path' => 'api2/',
                'methods' => array('*'),
                'limit' => 1000,
                'period' => 15
            )
        );

        $configuration = $this->getConfigs(array(
            'path_limits' => $pathLimits
        ));

        $this->assertArrayHasKey('path_limits', $configuration);
        $this->assertEquals($pathLimits, $configuration['path_limits']);
    }

    public function testDefaultPathLimitMethods()
    {
        $pathLimits = array(
            'api' => array(
                'path' => 'api/',
                'methods' => array('GET', 'POST'),
                'limit' => 200,
                'period' => 10
            ),
            'api2' => array(
                'path' => 'api2/',
                'limit' => 1000,
                'period' => 15
            )
        );

        $configuration = $this->getConfigs(array(
            'path_limits' => $pathLimits
        ));

        $pathLimits['api2']['methods'] = array('*');

        $this->assertArrayHasKey('path_limits', $configuration);
        $this->assertEquals($pathLimits, $configuration['path_limits']);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testMustBeBasedOnWhitelistInterface() {
        $configuration = $this->getConfigs(array('whitelist_interface' => '\StdClass'));
    }

    public function testMustBeBasedOnWhitelistInterface2() {
        $configuration = $this->getConfigs(array('whitelist_interface' => "\Noxlogic\RateLimitBundle\Tests\Whitelisting\WhitelistAll"));
        assert(true);
    }

    public function testMustBeBasedOnWhitelistInterface3() {
        /** @var Configuration $configuration */
        $configuration = $this->getConfigs(array('whitelist_interface' => "\Noxlogic\RateLimitBundle\Tests\Whitelisting\WhitelistAll"));
        $whitelistClass = $configuration["whitelist_interface"];
        $whitelist = new $whitelistClass();
        assert($whitelist instanceof WhitelistInterface);
    }

    public function testMustBeBasedOnWhitelistInterfaceOrNull() {
        $configuration = $this->getConfigs(array('whitelist_interface' => null));

        # no exception triggered is ok.
        $this->assertTrue(true);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testMustBeBasedOnExceptionClass()
    {
        $configuration = $this->getConfigs(array('rate_response_exception' => '\StdClass'));
    }

    /**
     *
     */
    public function testMustBeBasedOnExceptionClass2()
    {
        $configuration = $this->getConfigs(array('rate_response_exception' => '\InvalidArgumentException'));

        # no exception triggered is ok.
        $this->assertTrue(true);
    }

    public function testMustBeBasedOnExceptionOrNull()
    {
        $configuration = $this->getConfigs(array('rate_response_exception' => null));

        # no exception triggered is ok.
        $this->assertTrue(true);
    }
}
