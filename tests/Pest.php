<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A tiny upload that file-type sniffing recognises as an MP4 (an ISO Media
 * "ftyp" header), for tests of video fields. It isn't playable. The type is
 * also declared, because Livewire reports a test upload's declared type
 * (Laravel's guess for .mp4 is application/mp4) instead of sniffing it.
 */
function fakeMp4(string $name = 'intermission.mp4'): UploadedFile
{
    $header = pack('N', 24).'ftypisom'.pack('N', 512).'isomiso2mp41';

    return UploadedFile::fake()
        ->createWithContent($name, $header.str_repeat("\0", 1024))
        ->mimeType('video/mp4');
}
