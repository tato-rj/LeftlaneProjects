<?php

namespace Tests\Feature\PianoLit;

use Tests\PianoLitTest;

class UserTest extends PianoLitTest
{
	/** @test */
	public function users_can_signup_from_the_app()
	{
		$this->register();

		$this->assertDatabaseHas('users', ['first_name' => 'John']); 
	}
}
