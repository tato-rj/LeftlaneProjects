<?php

namespace Tests\Unit\PianoLit;

use App\Projects\PianoLit\{Admin, User, Piece, Composer, Tag, Country};
use Tests\PianoLitTest;

class PieceTest extends PianoLitTest
{
	/** @test */
	public function it_knows_who_added_it_to_the_database()
	{
		$this->assertInstanceOf(Admin::class, $this->piece->creator);
	}

	/** @test */
	public function it_belongs_to_a_composer()
	{
		$this->assertInstanceOf(Composer::class, $this->piece->composer);
	}

	/** @test */
	public function it_knows_its_nationality()
	{
		$this->assertInstanceOf(Country::class, $this->piece->country);
	}

	/** @test */
	public function it_has_many_tags()
	{
		$this->assertInstanceOf(Tag::class, $this->piece->tags()->first());
	}
}
