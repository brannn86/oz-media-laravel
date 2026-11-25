<?php

use App\Http\Middleware\ServeStorageFromThisHost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

it('builds public file URLs on the host the admin is open on', function () {
    config(['filesystems.disks.public.url' => 'http://127.0.0.1:8000/storage']);
    Storage::disk('public'); // Resolved (and cached) with the APP_URL host first.

    (new ServeStorageFromThisHost)->handle(
        Request::create('https://admin.ozmedianet.com/programs/1/edit'),
        fn () => new Response,
    );

    expect(Storage::disk('public')->url('4/cover.png'))->toBe('https://admin.ozmedianet.com/storage/4/cover.png');
});

it('runs on the Livewire requests that load the upload previews', function () {
    expect(Livewire::getPersistentMiddleware())->toContain(ServeStorageFromThisHost::class);
});
