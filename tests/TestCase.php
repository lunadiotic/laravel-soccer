<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    // make user available in tests
    protected function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        return $user;
    }
}
