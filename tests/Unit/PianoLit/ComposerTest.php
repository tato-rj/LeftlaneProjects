<?php

namespace Tests\Unit\PianoLit;

use App\Projects\PianoLit\{Admin, User, Piece, Composer, Tag, Country};
use Tests\PianoLitTest;

class ComposerTest extends PianoLitTest
{
	/** @test */
	public function it_knows_who_added_it_to_the_database()
	{
		$this->assertInstanceOf(Admin::class, $this->composer->creator);
	}

	/** @test */
	public function it_knows_its_nationality()
	{
		$this->assertInstanceOf(Country::class, $this->composer->country);
	}

	/** @test */
	public function it_has_many_pieces()
	{
		$this->assertInstanceOf(Piece::class, $this->composer->pieces()->first());
	}
}
