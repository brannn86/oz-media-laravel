<?php

use Illuminate\Foundation\Console\ServeCommand;

it('gives `php artisan serve` a temp folder so uploads work on Windows', function () {
    // Without these, PHP's built-in server can't create temporary upload files
    // and Livewire's upload endpoint answers every file with a 422.
    expect(ServeCommand::$passthroughVariables)
        ->toContain('TEMP', 'TMP', 'TMPDIR')
        ->toContain('PATH', 'SYSTEMROOT');
});
