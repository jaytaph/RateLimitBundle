NoxlogicRateLimitBundle
========================

This bundle provides enables the @xratelimit annotation which allows you to limit the number of connections to actions.
This is mostly useful in APIs. All information is currently only available to be stored in Redis, but adding other storage systems should be not difficult.

Right now, the bundle will work in coorporation with the FOSOAuthServerBundle. It contains a listener that adds the oauth token to the cache-key, but it's easy to implement your own listener that will use your setup (like the user that has logged in for instance, when you don't use oauth). Look for the `xratelimit.generate.key` event.

This bundle is partially inspired by a github gist from Ruud Kamphuis: https://gist.github.com/ruudk/3350405

## Features

    * Simple usage through annotations
    * Customize rates per controller, action and even per HTTP method
    * Multiple storage backends: redis, memcached etc


## Installation

Installation takes just few easy steps:

### Step 1: Add the bundle to your composer.json

If you're not yet familiar with Composer see http://getcomposer.org.
Add the NoxlogicRateLimitBundle in your composer.json:

```js
{
    "require": {
        "noxlogic/ratelimit-bundle": "@dev-master"
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




## Details

### Simple rate limiting

To enable rate limiting, you only need to add the annotation to the docblock of the specified action

    /**
     * @route(...)
     *
     * @xratelimit(limit=1000, period=3600)
     */
    public function someApiAction() { ... }



### Limit per method

It's possible to rate-limit specific HTTP methods as well. This can be either a string or an array of methods. When no
method argument is given, all other methods not defined are rated. This allows to add a default rate limit if needed.

    /**
     * @route(...)
     *
     * @xratelimit(methods={ PUT,POST }, limit=1000, period=3600)
     * @xratelimit(methods="GET", limit=1000, period=3600)
     * @xratelimit(limit=5000, period=3600)
     */
    public function someApiAction() { ... }



### Limit per controller

It's also possible to add rate-limits to a controller class instead of a single action. This will act as a default rate
limit for all actions, except the ones that actually defines a custom rate-limit.

    /**
     * @xratelimit(method="POST", limit=100, period=10);        // 100 POST requests per 10 seconds
     */
   class DefaultController extends Controller
   {

        /**
         * @xratelimit(method="POST", limit=200, period=10);        // 200 POST requests to indexAction allowed.
         */
        function indexAction() {
        }

   }



## TODO

  * Add default x-rate limit to config (like: rate-limiting an /api directory for instance)
  * Split backends in a better way (right now, it depends on redis too much)
  * Add memcache storage
  * Unittests
