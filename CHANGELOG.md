## 1.14.0
2019-02-19 goetas
- Added Payload to RateLimit to allow better exceptions

## 1.13.0
2019-01-04 axi
- Added CheckedRateLimit event that allows RateLimit to be changed

## 1.12.0
2019-01-04
- Something has gone wrong with the versions, just going to make a new one and
move on

## 1.11.1
2018-07-02 mcfedr
- Accept null rate response exception configuration

## 1.10.3
2019-01-04 DemigodCode
- Deprecations in Symfony 4.2

## 1.10.2
2018-09-28 pierniq
- Fixed setting calls in Redis/PhpRedis storage

## 1.10.1
2018-06-29 mcfedr
- Change cache keys to be valid PSR-6 keys

## 1.10.0
2018-06-27 mcfedr
- Add Psr-6 Cache storage engine

## 1.9.1
2018-06-27 mcfedr
- Optimisation for Memcached storage engine
- Reduce likely hood of infinite loop in memcached storage
- Memcached storage will silently fail in the same way other storages fail

## 1.9.0
2018-06-27 mcfedr
- Add Psr-16 Simple Cache storage engine
- Optimisation for Doctrine Cache storage engine

## 1.8.2
2018-06-04 goetas
- Fix Symfony 4 support by allowing newer versions of `framework-extra-bundle`
- Fix travis tests as some just seem to fail

## 1.8.1
2018-05-24 mcfedr
- Support for Symfony 4  

2017-10-19 merk
- Force $methods to be an array 

2017-08-17 odoucet
- Fix and improve Travis builds 

2017-08-11 mcfedr
- More efficient use of Redis
- Option to disable fos listener 
- Easy to use RateLimitBundle without extra bundles 
- Add support for using a php redis client 

## 1.7.0
2016-03-25  Joshua Thijsen <jthijssen@noxlogic.nl>
	Fixed issue where manual reset did not correctly reset in redis

2016-03-18  Scott Brown <scott.brown@verified.com>
	Implement reset of rate limit

## 1.6.0

2015-24-12  Roland Ekström <peshis@gmail.com>
	Initial support for Symfony 3

## 1.5.0

2015-04-10  Sam Van der Borght <sam@king-foo.be>
	[Security] Prevent ratelimit bypassing by encoding url paths

2015-01-05  Jonathan McLean <defenestrator@gmail.com>
	Fix rate_response_exception

## 1.4

2014-12-10  Koen Vlaswinkel <koen@vlaswinkel.info>
	Add Doctrine Cache storage engine

## 1.3

2014-11-17  Joshua Thijssen <jthijssen@noxlogic.nl>
	Ratelimit can trigger exceptions

## 1.2
2014-07-15  Dan Spencer  <danrspen@gmail.com>
	Added global rate limits

## 1.1
2014-07-08  Joshua Thijssen  <jthijssen@noxlogic.nl>
	Added changelog to reflect changes in the different releases
	
2014-07-07  Tobias Berchtold  <admin@fahrschulcard.de>
	removed dependency to constant used in Symfony > 2.3

2014-07-05  Alberto Fernández  <albertofem@gmail.com>
	Improved README, added enabled configuration

## 1.0.1
2014-06-23  Joshua Thijssen  <jthijssen@noxlogic.nl>
	Fixed installation documentation for 1.x

## 1.0 - Initial release
