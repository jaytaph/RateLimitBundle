<?php

namespace Noxlogic\RateLimitBundle\Tests\DependencyInjection;

use Noxlogic\RateLimitBundle\DependencyInjection\Configuration;
use Noxlogic\RateLimitBundle\Tests\WebTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends WebTestCase
{
    private Processor $processor;

    public function setUp():void
    {
        $this->processor = new Processor();
    }

    private function getConfigs(array $configArray): array
    {
        $configuration = new Configuration();

        return $this->processor->processConfiguration($configuration, array($configArray));
    }

    public function testUnconfiguredConfiguration(): void
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
            'fail_open' => false,
            'fos_oauth_key_listener' => true
        ), $configuration);
    }

    public function testDisabledConfiguration(): void
    {
        $configuration = $this->getConfigs(array('enabled' => false));

        $this->assertArrayHasKey('enabled', $configuration);
        $this->assertFalse($configuration['enabled']);
    }

    public function testPathLimitConfiguration(): void
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

    public function testMultiplePathLimitConfiguration(): void
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

    public function testDefaultPathLimitMethods(): void
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

    public function testMustBeBasedOnExceptionClass(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->getConfigs(array('rate_response_exception' => '\StdClass'));
    }

    /**
     * @testWith [""]
     *           [null]
     */
    public function testEmptyPathIsNotAllowed(mixed $path): void
    {
        $pathLimits = [
            'api' => [
                'path' => $path,
                'methods' => ['GET'],
                'limit' => 200,
                'period' => 10
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);

        $this->getConfigs([
            'path_limits' => $pathLimits
        ]);
    }

    /**
     *
     */
    public function testMustBeBasedOnExceptionClass2(): void
    {
        $this->getConfigs(array('rate_response_exception' => '\InvalidArgumentException'));

        # no exception triggered is ok.
        $this->expectNotToPerformAssertions();
    }

    public function testMustBeBasedOnExceptionOrNull(): void
    {
        $this->getConfigs(array('rate_response_exception' => null));

        # no exception triggered is ok.
        $this->expectNotToPerformAssertions();
    }

    public function testFailOpen(): void
    {
        $config = $this->getConfigs(['fail_open' => true]);

        self::assertTrue($config['fail_open']);
    }

    public function testFailOpen_false(): void
    {
        $config = $this->getConfigs(['fail_open' => false]);

        self::assertFalse($config['fail_open']);
    }

    public function testFailOpen_nullShouldBeTreatedAsFalse(): void
    {
        $config = $this->getConfigs(['fail_open' => null]);

        self::assertFalse($config['fail_open']);
    }

    /**
     * @testWith ["not-a-boolean"]
     *           [123]
     */
    public function testFailOpen_mustBeBool(mixed $value): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->getConfigs(['fail_open' => $value]);
    }
}
