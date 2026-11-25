<?php

use App\Support\UserAgent;

const IPHONE_SAFARI = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Mobile/15E148 Safari/604.1';
const IPHONE_INSTAGRAM = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Instagram 356.0.0.30.83 (iPhone15,3; iOS 18_1; en_US)';
const ANDROID_CHROME = 'Mozilla/5.0 (Linux; Android 14; SM-A546E) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36';
const ANDROID_TABLET = 'Mozilla/5.0 (Linux; Android 13; SM-X200) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
const WINDOWS_EDGE = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0';
const MAC_SAFARI = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Safari/605.1.15';

it('reads the device, OS and browser of a visitor', function (string $ua, string $device, string $os, string $browser) {
    expect(UserAgent::device($ua))->toBe($device)
        ->and(UserAgent::os($ua))->toBe($os)
        ->and(UserAgent::browser($ua))->toBe($browser);
})->with([
    'iPhone Safari' => [IPHONE_SAFARI, 'Phone', 'iOS', 'Safari'],
    'Instagram in-app' => [IPHONE_INSTAGRAM, 'Phone', 'iOS', 'Instagram (in-app)'],
    'Android Chrome' => [ANDROID_CHROME, 'Phone', 'Android', 'Chrome'],
    'Android tablet' => [ANDROID_TABLET, 'Tablet', 'Android', 'Chrome'],
    'Windows Edge' => [WINDOWS_EDGE, 'Desktop', 'Windows', 'Edge'],
    'Mac Safari' => [MAC_SAFARI, 'Desktop', 'macOS', 'Safari'],
]);

it('sorts Icecast listeners by kind of player', function (string $ua, string $kind) {
    expect(UserAgent::icecastClient($ua))->toBe($kind);
})->with([
    'browser' => [WINDOWS_EDGE, 'Web browsers'],
    'iPhone Safari audio' => ['AppleCoreMedia/1.0.0.22B83 (iPhone; U; CPU OS 18_1 like Mac OS X; en_us)', 'Apple devices (Safari & apps)'],
    'Android app' => ['ExoPlayerLib/2.19.1', 'Android apps'],
    'Alexa' => ['AlexaMediaPlayer/2.1.4676.0 (Linux;Android 6.0.1)', 'Smart speakers & TVs'],
    'Sonos' => ['Sonos/80.1-55014 (ZPS27)', 'Smart speakers & TVs'],
    'TuneIn' => ['TuneIn Radio/29.4.0; iPhone15,3; iOS/18.1', 'Radio apps'],
    'VLC' => ['VLC/3.0.20 LibVLC/3.0.20', 'Media players'],
    'nothing' => ['', 'Unknown'],
]);
