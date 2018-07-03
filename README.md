NoxlogicRateLimitBundle
========================

[![Build Status](https://travis-ci.org/jaytaph/RateLimitBundle.svg?branch=master)](https://travis-ci.org/jaytaph/RateLimitBundle)
[![Code Coverage](https://scrutinizer-ci.com/g/jaytaph/RateLimitBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jaytaph/RateLimitBundle/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jaytaph/RateLimitBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jaytaph/RateLimitBundle/?branch=master)

[![Latest Stable Version](https://poser.pugx.org/noxlogic/ratelimit-bundle/v/stable.svg)](https://packagist.org/packages/noxlogic/ratelimit-bundle) [![Total Downloads](https://poser.pugx.org/noxlogic/ratelimit-bundle/downloads.svg)](https://packagist.org/packages/noxlogic/ratelimit-bundle) [![Latest Unstable Version](https://poser.pugx.org/noxlogic/ratelimit-bundle/v/unstable.svg)](https://packagist.org/packages/noxlogic/ratelimit-bundle) [![License](https://poser.pugx.org/noxlogic/ratelimit-bundle/license.svg)](https://packagist.org/packages/noxlogic/ratelimit-bundle)

This bundle provides enables the `@RateLimit` annotation which allows you to limit the number of connections to actions.
This is mostly useful in APIs.

The bundle is prepared to work by default in cooperation with the `FOSOAuthServerBundle`. It contains a listener that adds the OAuth token to the cache-key. However, you can create your own key generator to allow custom rate limiting based on the request. See *Create a custom key generator* below.

This bundle is partially inspired by a GitHub gist from Ruud Kamphuis: https://gist.github.com/ruudk/3350405

## Features

 * Simple usage through annotations
 * Customize rates per controller, action and even per HTTP method
 * Customize the period of a lock 
 * Multiple storage backends: Redis, Memcached and Doctrine cache

## Installation

Installation takes just few easy steps:

### Step 1: Add the bundle to your composer.json

If you're not yet familiar with Composer see http://getcomposer.org.
Add the NoxlogicRateLimitBundle in your composer.json:

```json
{
    "require": {
        "noxlogic/ratelimit-bundle": "1.x"
    }
}
```

Now tell composer to download the bundle by running the command:

``` bash
php composer.phar update noxlogic/ratelimit-bundle
```

### Step 2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Noxlogic\RateLimitBundle\NoxlogicRateLimitBundle(),
    );
}
```

## Step 3: Install a storage engine

### Redis

If you want to use Redis as your storage engine, you might want  to install `SncRedisBundle`:

* https://github.com/snc/SncRedisBundle

### Memcache

If you want to use Memcache, you might want to install `LswMemcacheBundle`

* https://github.com/LeaseWeb/LswMemcacheBundle

### Doctrine cache

If you want to use Doctrine cache as your storage engine, you might want to install `DoctrineCacheBundle`:

* https://github.com/doctrine/DoctrineCacheBundle

Referer to their documentations for more details. You can change your storage engine with the `storage_engine` configuration parameter. See *Configuration reference*.

## Configuration

### Enable bundle only in production

If you wish to enable the bundle only in production environment (so you can test without worrying about limit in your development environments), you can use the `enabled` configuration setting to enable/disable the bundle completely. It's enabled by default:

```yaml
# config_dev.yml
noxlogic_rate_limit:
    enabled: false
```

### Configuration reference

This is the default bundle configuration:

```yaml
noxlogic_rate_limit:
    enabled:              true

    # The storage engine where all the rates will be stored
    storage_engine:       ~ # One of "redis"; "memcache"; "doctrine"; "php_redis"

    # The redis client to use for the redis storage engine
    redis_client:         default_client
    
    # The Redis service, use this if you dont use SncRedisBundle and want to specify a service to use
    # Should be instance of \Predis\Client
    redis_service:    null # Example: project.predis

    # The Redis client to use for the php_redis storage engine
    # Should be an instance of \Redis
    php_redis_service:    null # Example: project.redis

    # The memcache client to use for the memcache storage engine
    memcache_client:      default
    
    # The Memcached service, use this if you dont use LswMemcacheBundle and want to specify a service to use
    # Should be instance of \Memcached
    memcache_service:    null # Example: project.memcached

    # The Doctrine Cache provider to use for the doctrine storage engine
    doctrine_provider:    null # Example: my_apc_cache
    
    # The Doctrine Cache service, use this if you dont use DoctrineCacheBundle and want to specify a service to use
    # Should be an instance of \Doctrine\Common\Cache\Cache
    doctrine_service:    null # Example: project.my_apc_cache

    # The HTTP status code to return when a client hits the rate limit
    rate_response_code:   429

    # Optional exception class that will be returned when a client hits the rate limit
    rate_response_exception:  null

    # The HTTP message to return when a client hits the rate limit
    rate_response_message:  'You exceeded the rate limit'

    # Should the ratelimit headers be automatically added to the response?
    display_headers:      true

    # What are the different header names to add
    headers:
        limit:                X-RateLimit-Limit
        remaining:            X-RateLimit-Remaining
        reset:                X-RateLimit-Reset

    # Rate limits for paths
    path_limits:
        path:                 ~ # Required
        methods:

            # Default:
            - *
        limit:                ~ # Required
        period:               ~ # Required
        block_period:         ~ # Optional
        
    # - { path: /api, limit: 1000, period: 3600 }
    # - { path: /auth, limit: 1000, period: 3600, block_period: 7200 }
    # - { path: /dashboard, limit: 100, period: 3600, methods: ['GET', 'POST']}

    # Should the FOS OAuthServerBundle listener be enabled 
    fos_oauth_key_listener: true
```


## Usage

### Simple rate limiting

To enable rate limiting, you only need to add the annotation to the docblock of the specified action

```php
<?php

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route(...)
 *
 * @RateLimit(limit=1000, period=3600)
 */
public function someApiAction()
{
}
```

### Simple rate limit with custom block period

To enable this feature, you should write `blockPeriod` into the annotation `RateLimit`. If `blockPeriod` is not set up
its value will be equaled `period` parameter.

```php
<?php

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route(...)
 *
 * @RateLimit(limit=1000, period=3600, blockPeriod=7200)
 */
public function someApiAction()
{
}
```

### Limit per method

It's possible to rate-limit specific HTTP methods as well. This can be either a string or an array of methods. When no
method argument is given, all other methods not defined are rated. This allows to add a default rate limit if needed.

```php
<?php

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route(...)
 *
 * @RateLimit(methods={"PUT", "POST"}, limit=1000, period=3600)
 * @RateLimit(methods={"GET"}, limit=1000, period=3600)
 * @RateLimit(limit=5000, period=3600)
 */
public function someApiAction()
{
}
```

### Limit per controller

It's also possible to add rate-limits to a controller class instead of a single action. This will act as a default rate
limit for all actions, except the ones that actually defines a custom rate-limit.

```php
<?php

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Ratelimit(methods={"POST"}, limit=100, period=10); // 100 POST requests per 10 seconds
 */
class DefaultController extends Controller
{
    /**
     * @Ratelimit(method="POST", limit=200, period=10); // 200 POST requests to indexAction allowed.
     */
    public function indexAction()
    {
    }
}
```

## Events

This bundle has several events. They are placed in
[Noxlogic\RateLimitBundle\Events\RateLimitEvents](Events/RateLimitEvents.php)

 * `ratelimit.generate.key`
 * `ratelimit.block.after` you can do additional manipulations when a block happens, for example to register a block 
 event in journal
 * `ratelimit.response.sending.before` you can use this event for making your own response when somebody call a blocked
 URL

## Create a custom key generator

If you need to create a custom key generator, you need to register a listener to listen to the `ratelimit.generate.key` event:

```yaml
services:
    mybundle.listener.rate_limit_generate_key:
        class: MyBundle\Listener\RateLimitGenerateKeyListener
        tags:
            - { name: kernel.event_listener, event: 'ratelimit.generate.key', method: 'onGenerateKey' }
```

```php
<?php

namespace MyBundle\Listener;

use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;

class RateLimitGenerateKeyListener
{
    public function onGenerateKey(GenerateKeyEvent $event)
    {
        $key = $this->generateKey();

        $event->addToKey($key);
        // $event->setKey($key); // to overwrite key completely
    }
}
```

Make sure to generate a key based on what is rate limited in your controllers.

## Creating custom response with `ratelimit.response.sending.before`

```yaml
services:
    mybundle.listener.rate_limit_custom_response:
        class: MyBundle\Listener\RateLimitResponseListener
        tags:
            - { name: kernel.event_listener, event: 'ratelimit.response.sending.before', method: 'onResponse' }
```

```php
<?php

namespace MyBundle\Listener;

use Noxlogic\RateLimitBundle\Events\GetResponseEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class RateLimitResponseListener
{
    public function onResponse(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $rateLimitInfo = $event->getRateLimitInfo();

        $event->setResponse(new JsonResponse(
            array(
                "error" => sprintf("Access for URL %s deny", $request->getRequestUri()),
                "block_period" => $rateLimitInfo->getResetTimestamp() - time() 
            ), 
            Response::HTTP_TOO_MANY_REQUESTS
        ));
    }
}
```

## Logging a block into a journal

You could do a lot of things with an event `ratelimit.block.after`. This event is dispatched just before a lock is set.
For example below you could register every blocked URL in your security journal.

```yaml
services:
    mybundle.listener.rate_limit_log_block:
        class: MyBundle\Listener\RateLimitLogBlockListener
        arguments: ["@my.service.journal"]
        tags:
            - { name: kernel.event_listener, event: 'ratelimit.block.after', method: 'onLog' }
```

```php
<?php

namespace MyBundle\Listener;

use Noxlogic\RateLimitBundle\Events\BlockEvent;

class RateLimitLogBlockListener
{
    /**
     * @var object security journal 
     */
    private $journal;
    
    public function __construct($journal)
    {
        $this->journal = $journal;
    }
    
    public function onLog(BlockEvent $event)
    {
        $message = sprintf(
            "Somebody tries to make brute force on URL %s with login %s. They will be lock until %s", 
            $event->getRequest()->getUri(), 
            $event->getRequest()->request->get('login'),
            date(\DateTime::ISO8601, $event->getRateLimitInfo()->getResetTimestamp())
        );
        $this->journal->log($message);
    }
}
```

## Throwing exceptions

Instead of returning a Response object when a rate limit has exceeded, it's also possible to throw an exception. This 
allows you to easily handle the rate limit on another level, for instance by capturing the ``kernel.exception`` event. 

## Running tests

If you want to run the tests use:

```
./vendor/bin/phpunit ./Tests
```
