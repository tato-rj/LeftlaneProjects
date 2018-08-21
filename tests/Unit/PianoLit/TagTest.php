<?php

namespace Tests\Unit\PianoLit;

use App\Projects\PianoLit\{Admin, User, Piece, Composer, Tag, Country};
use Tests\PianoLitTest;

class TagTest extends PianoLitTest
{
	/** @test */
	public function it_has_many_pieces()
	{
		$this->assertInstanceOf(Piece::class, $this->tag->pieces()->first());
	}
}
