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

        $this->piece = create(Piece::class, ['creator_id' => $this->admin->id]);
        $this->piece->tags()->attach($this->tag);
    }

    protected function signIn($admin = null)
    {
        $admin = ($admin) ?: $this->admin;
        return $this->actingAs($admin, 'pianolit-admin');
    }
}
