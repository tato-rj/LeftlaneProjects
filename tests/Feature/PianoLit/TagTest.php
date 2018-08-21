<?php

namespace Tests\Feature\PianoLit;

use App\Projects\PianoLit\Admin;
use Tests\PianoLitTest;

class TagTest extends PianoLitTest
{
	/** @test */
	public function authorized_managers_can_add_tags_to_the_database()
	{
		$admin = create(Admin::class, ['role' => 'manager']);

		$this->signIn($admin);

		$this->post(route('piano-lit.tags.store'), [
			'name' => 'new tag'
		]);

		$this->assertDatabaseHas('tags', ['name' => 'new tag']);
	}

	/** @test */
	public function unauthorized_managers_cannot_add_tags_to_the_database()
	{
		$this->expectException('Illuminate\Auth\Access\AuthorizationException');

		$admin = create(Admin::class, ['role' => 'unauthorized']);

		$this->signIn($admin);

		$this->post(route('piano-lit.tags.store'), [
			'name' => 'new tag'
		]);

		$this->assertDatabaseHas('tags', ['name' => 'new tag']);
	}

	/** @test */
	public function authorized_managers_can_edit_the_tags_in_the_database()
	{
		$admin = create(Admin::class, ['role' => 'manager']);

		$this->signIn($admin);

		$this->patch(route('piano-lit.tags.update', $this->tag->id), [
			'name' => 'new name'
		]);

		$this->assertEquals($this->tag->fresh()->name, 'new name');
	}

	/** @test */
	public function unauthorized_managers_cannot_edit_the_tags_in_the_database()
	{
		$this->expectException('Illuminate\Auth\Access\AuthorizationException');

		$admin = create(Admin::class, ['role' => 'unauthorized']);

		$this->signIn($admin);

		$this->patch(route('piano-lit.tags.update', $this->tag->id), [
			'name' => 'new tag'
		]);
	}

	/** @test */
	public function authorized_managers_can_delete_tags_in_the_database()
	{
		$admin = create(Admin::class, ['role' => 'manager']);
		$tagId = $this->tag->id;

		$this->signIn($admin);

		$this->delete(route('piano-lit.tags.destroy', $this->tag->id));

		$this->assertDatabaseMissing('tags', ['id' => $tagId]);
	}

	/** @test */
	public function unauthorized_managers_cannot_delete_tags_in_the_database()
	{
		$this->expectException('Illuminate\Auth\Access\AuthorizationException');

		$admin = create(Admin::class, ['role' => 'unauthorized']);

		$this->signIn($admin);

		$this->delete(route('piano-lit.tags.destroy', $this->tag->id));
	}
}
