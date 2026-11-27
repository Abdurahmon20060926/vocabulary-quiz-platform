<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

test('visitor is redirected to Google for sign-in', function () {
    Socialite::fake('google');

    $this->get('/auth/google/redirect')->assertRedirect();
});

test('Google callback creates and authenticates a new user', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-new-user',
        'name' => 'New Google User',
        'email' => 'new-google-user@example.test',
        'avatar' => 'https://example.test/avatar.png',
    ]));

    $this->get('/auth/google/callback')
        ->assertRedirect('/');

    $user = User::where('google_id', 'google-new-user')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Google User',
        'email' => 'new-google-user@example.test',
        'avatar_url' => 'https://example.test/avatar.png',
    ]);
});

test('Google callback updates an existing user by provider id', function () {
    $user = User::create([
        'google_id' => 'google-returning-user',
        'name' => 'Old Google Name',
        'email' => 'old-google-user@example.test',
    ]);
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-returning-user',
        'name' => 'Updated Google Name',
        'email' => 'updated-google-user@example.test',
    ]));

    $this->get('/auth/google/callback')
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Google Name',
        'email' => 'updated-google-user@example.test',
    ]);
});

test('Google callback redirects with an error when OAuth state is invalid', function () {
    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andThrow(new InvalidStateException('Invalid OAuth state.'));

    $this->get('/auth/google/callback')
        ->assertRedirect('/')
        ->assertSessionHasErrors(['auth']);
});
