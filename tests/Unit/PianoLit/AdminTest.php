<?php

namespace Tests\Unit\PianoLit;

use App\Projects\PianoLit\{Admin, User, Piece, Composer, Tag};
use Tests\PianoLitTest;

class AdminTest extends PianoLitTest
{
	/** @test */
	public function it_can_log_into_the_admin_page()
	{
		$this->signIn();
		$this->get('/piano-lit')->assertStatus(200); 
	}

	/** @test */
	public function an_unauthorized_user_cannot_sign_into_the_admin_page()
	{
		$this->expectException('Illuminate\Auth\AuthenticationException');
		$this->get('/piano-lit');
	}

	/** @test */
	public function it_is_associated_with_a_created_piece()
	{
		$this->assertInstanceOf(Piece::class, $this->admin->pieces()->first());
	}

	/** @test */
	public function it_is_associated_with_a_created_composer()
	{
		$this->assertInstanceOf(Composer::class, $this->admin->composers()->first());
	}
	
	/** @test */
	public function it_is_associated_with_a_created_tag()
	{
		$this->assertInstanceOf(Tag::class, $this->admin->tags()->first());
	}
}
