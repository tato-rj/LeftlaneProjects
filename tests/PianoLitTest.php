<?php

namespace Tests;

use App\Projects\PianoLit\{Admin, User, Piece, Composer, Tag, Country};

class PianoLitTest extends TestCase
{
	protected $admin;
	protected $user;
	protected $piece;
	protected $tag;
	protected $composer;
	protected $country;

    public function setUp()
    {
        parent::setUp();

        $this->admin = create(Admin::class);
        $this->country = create(Country::class);
        $this->tag = create(Tag::class, ['creator_id' => $this->admin->id]);

        $this->composer = create(Composer::class, [
        	'creator_id' => $this->admin->id,
        	'country_id' => $this->country->id
        ]);

        $this->piece = create(Piece::class, [
            'creator_id' => $this->admin->id,
            'composer_id' => $this->composer->id
        ]);
        
        $this->piece->tags()->attach($this->tag);
    }

    protected function signIn($admin = null)
    {
        $admin = ($admin) ?: $this->admin;
        return $this->actingAs($admin, 'pianolit-admin');
    }

    protected function register($user = null)
    {
        return $this->post(route('piano-lit.api.users.store'), [
            'first_name' => $user['first_name'] ?? 'John',
            'last_name' => $user['last_name'] ?? 'Doe',
            'email' => $user['email'] ?? 'doe@email.com',
            'password' => $user['password'] ?? 'secret',
            'password_confirmation' => $user['password_confirmation'] ?? 'secret',
            'locale' => $user['locale'] ?? 'en_US',
            'age_range' => $user['age_range'] ?? '35 to 45',
            'experience' => $user['experience'] ?? 'Little',
            'preferred_piece_id' => $user['preferred_piece_id'] ?? create('App\Projects\PianoLit\Piece')->id,
            'occupation' => $user['occupation'] ?? 'Teacher',
        ])->assertSuccessful();     
    }

    protected function postSubscription($user, $subscription)
    {
        return $this->post(route('piano-lit.api.subscription.create'), [
            'user_id' => $user->id,
            'receipt_data' => $subscription->withRequest()->receipt_data,
            'password' => $subscription->withRequest()->password
        ]);
    }
}
