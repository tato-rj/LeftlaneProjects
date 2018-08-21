<?php

namespace Tests\Feature\PianoLit;

use App\Projects\PianoLit\{Admin, Composer};
use Tests\PianoLitTest;

class ComposerTest extends PianoLitTest
{
	/** @test */
	public function all_managers_can_add_composers_to_the_database()
	{
		$admin = create(Admin::class, ['role' => 'manager']);
		$composer = make(Composer::class)->toArray();

		$this->signIn($admin);

		$this->post(route('piano-lit.composers.store'), $composer);

		$this->assertDatabaseHas('composers', ['name' => $composer['name']]);
	}

	/** @test */
	public function all_managers_can_edit_the_composers_in_the_database()
	{
		$admin = create(Admin::class, ['role' => 'manager']);
		$updatedComposer = make(Composer::class)->toArray();

		$this->signIn($admin);

		$this->patch(route('piano-lit.composers.update', $this->composer->id), $updatedComposer);

		$this->assertEquals($this->composer->fresh()->name, $updatedComposer['name']);
	}

	/** @test */
	public function authorized_managers_can_delete_composers_in_the_database()
	{
		$admin = create(Admin::class, ['role' => 'manager']);
		$composerId = $this->composer->id;

		$this->signIn($admin);

		$this->delete(route('piano-lit.composers.destroy', $this->composer->id));

		$this->assertDatabaseMissing('composers', ['id' => $composerId]);
	}

	/** @test */
	public function unauthorized_managers_cannot_delete_composers_in_the_database()
	{
		$this->expectException('Illuminate\Auth\Access\AuthorizationException');

		$admin = create(Admin::class, ['role' => 'unauthorized']);

		$this->signIn($admin);

		$this->delete(route('piano-lit.composers.destroy', $this->composer->id));
	}
}
