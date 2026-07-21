<?php

use App\Models\User;
use App\Models\ShopSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{actingAs, post, get};

uses(RefreshDatabase::class);

beforeEach(function () {
    // Disable Vite asset rendering in tests
    $this->withoutVite();

    // Seed the database to create shop, branches, and user
    $this->artisan('db:seed');

    $this->owner = User::where('email', 'owner@demo.com')->first();
});

test('it blocks access and redirects to license index if offline mode is active and current month/year is not allowed', function () {
    // Set environment variable Mode to offline
    config(['app.env' => 'production']); // just to make sure
    putenv('Mode=offline');
    $_ENV['Mode'] = 'offline';

    // Explicitly configure allowed months and years to exclude current year
    $currentYear = (string) date('Y');
    $wrongYear = (string) ($currentYear - 1);

    ShopSetting::set($this->owner->shop_id, 'offline_allowed_years', [$wrongYear], 'json');
    ShopSetting::set($this->owner->shop_id, 'offline_allowed_months', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], 'json');

    // Try to access dashboard as authenticated user
    $response = actingAs($this->owner)->get('/');

    // Should redirect to license.index with warning
    $response->assertRedirect(route('license.index'));
    $response->assertSessionHas('warning');
});

test('it allows access to settings routes even if offline mode is active and current month/year is not allowed', function () {
    putenv('Mode=offline');
    $_ENV['Mode'] = 'offline';

    // Explicitly configure allowed months and years to exclude current year
    $currentYear = (string) date('Y');
    $wrongYear = (string) ($currentYear - 1);

    ShopSetting::set($this->owner->shop_id, 'offline_allowed_years', [$wrongYear], 'json');
    ShopSetting::set($this->owner->shop_id, 'offline_allowed_months', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], 'json');

    // Try to access settings index page as authenticated user
    $response = actingAs($this->owner)->get(route('settings.index'));

    // Should be successful (status 200) instead of redirecting
    $response->assertStatus(200);
});

test('it allows access if offline mode is active and current month/year is allowed', function () {
    // Set environment variable Mode to offline
    putenv('Mode=offline');
    $_ENV['Mode'] = 'offline';

    $currentYear = (string) date('Y');
    $currentMonth = (int) date('n');

    ShopSetting::set($this->owner->shop_id, 'offline_allowed_years', [$currentYear], 'json');
    ShopSetting::set($this->owner->shop_id, 'offline_allowed_months', [$currentMonth], 'json');

    // Try to access dashboard as authenticated user
    $response = actingAs($this->owner)->get('/');

    // Should succeed
    $response->assertStatus(200);
});

test('it verifies the offline password correctly and locks/unlocks session', function () {
    // Set Mode to offline
    putenv('Mode=offline');
    $_ENV['Mode'] = 'offline';

    // Set the password in env
    putenv('OFFLINE_PASSWORD=midnyt123456789');
    $_ENV['OFFLINE_PASSWORD'] = 'midnyt123456789';

    // Ensure initially locked
    expect(session('offline_unlocked'))->toBeNull();

    // Try to unlock with wrong password
    $response = actingAs($this->owner)->post(route('settings.offline.verify'), [
        'password' => 'wrong-pass'
    ]);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    expect(session('offline_unlocked'))->toBeNull();

    // Try to unlock with correct password
    $response = actingAs($this->owner)->post(route('settings.offline.verify'), [
        'password' => 'midnyt123456789'
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
    expect(session('offline_unlocked'))->toBeTrue();

    // Lock the tab
    $response = actingAs($this->owner)->get(route('settings.offline.lock'));
    $response->assertRedirect(route('settings.index'));
    expect(session('offline_unlocked'))->toBeNull();
});

test('it permits saving offline settings when unlocked', function () {
    putenv('Mode=offline');
    $_ENV['Mode'] = 'offline';

    // Unlock session
    session(['offline_unlocked' => true]);

    $response = actingAs($this->owner)->post(route('settings.offline'), [
        'allowed_years' => ['2026', '2027'],
        'allowed_months' => [1, 2, 12]
    ]);

    $response->assertRedirect(route('settings.index'));
    $response->assertSessionHas('success');

    // Verify values stored in DB
    $storedYears = ShopSetting::get($this->owner->shop_id, 'offline_allowed_years');
    $storedMonths = ShopSetting::get($this->owner->shop_id, 'offline_allowed_months');

    expect($storedYears)->toEqual(['2026', '2027']);
    expect($storedMonths)->toEqual([1, 2, 12]);
});

// Clean up env variables after tests
afterAll(function () {
    putenv('Mode');
    unset($_ENV['Mode']);
    putenv('OFFLINE_PASSWORD');
    unset($_ENV['OFFLINE_PASSWORD']);
});
