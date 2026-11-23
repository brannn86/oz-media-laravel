<?php

use Illuminate\Support\Facades\Cache;

/*
| Production caches (Redis) refuse to unserialize PHP objects. The test cache
| is configured the same way (phpunit.xml CACHE_ARRAY_SERIALIZE), so caching a
| model or collection by mistake fails here, not on the live site.
*/

it('behaves like Redis: objects do not survive the cache, arrays do', function () {
    Cache::put('probe:object', collect([1, 2]));
    Cache::put('probe:array', ['a' => 1]);

    expect(Cache::get('probe:object'))->toBeInstanceOf(__PHP_Incomplete_Class::class)
        ->and(Cache::get('probe:array'))->toBe(['a' => 1]);
});
