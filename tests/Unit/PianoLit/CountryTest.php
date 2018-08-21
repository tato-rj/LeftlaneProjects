<?php

namespace Tests\Unit\PianoLit;

use App\Projects\PianoLit\{Admin, User, Piece, Composer, Tag, Country};
use Tests\PianoLitTest;

class CountryTest extends PianoLitTest
{
	/** @test */
	public function it_has_many_composers()
	{
		$this->assertInstanceOf(Composer::class, $this->country->composers()->first());
	}

	/** @test */
	public function it_has_many_pieces()
	{
		$this->assertInstanceOf(Piece::class, $this->country->pieces()->first());
	}
}
