<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_request_uses_the_business_timezone_when_enabled(): void
    {
        config(['business.apply_timezone' => true]);

        $business = Business::create([
            'name' => 'TZ Test', 'slug' => 'tz-test', 'timezone' => 'Asia/Tokyo',
            'trial_ends_at' => now()->addWeek(),
        ]);
        $owner = User::create([
            'business_id' => $business->id, 'name' => 'Owner', 'email' => 'tz-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);

        $this->actingAs($owner)->get(route('dashboard'))->assertOk();

        $this->assertSame('Asia/Tokyo', config('app.timezone'));
        $this->assertSame('Asia/Tokyo', date_default_timezone_get());
    }
}
