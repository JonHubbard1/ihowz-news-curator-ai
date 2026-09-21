<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserWordPressCredentialsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_add_a_user_with_a_wordpress_application_password(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('users.store'), [
            'name' => 'Writer',
            'email' => 'writer@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'wp_application_password' => 'abcd EFGH ijkl MNOP qrst-uvwx',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'writer@example.com',
            'wp_application_password' => 'abcd EFGH ijkl MNOP qrst-uvwx',
        ]);
    }

    public function test_admin_can_update_a_users_wordpress_application_password(): void
    {
        $this->actingAs($this->admin());
        $user = User::factory()->create();

        $this->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'wp_application_password' => 'new-password-value',
        ])->assertRedirect();

        $this->assertSame('new-password-value', $user->fresh()->wp_application_password);
    }

    public function test_blank_application_password_keeps_the_existing_value(): void
    {
        $this->actingAs($this->admin());
        $user = User::factory()->create(['wp_application_password' => 'secret-app-password']);

        $this->put(route('users.update', $user), [
            'name' => 'Renamed',
            'email' => $user->email,
            'wp_application_password' => '',
        ])->assertRedirect();

        $this->assertSame('secret-app-password', $user->fresh()->wp_application_password);
    }

    public function test_non_admins_cannot_manage_users(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $user = User::factory()->create();

        $this->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'wp_application_password' => 'sneaky',
        ])->assertForbidden();

        $this->assertNull($user->fresh()->wp_application_password);
    }
}
