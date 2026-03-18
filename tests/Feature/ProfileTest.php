<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/user/profile');

    $response->assertOk();
});

test('profile name can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put('/user/profile', [
            'name' => 'Updated Name',
        ]);

    $response->assertSessionHasNoErrors();

    $user->refresh();

    $this->assertSame('Updated Name', $user->name);
});
