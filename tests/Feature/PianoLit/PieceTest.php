<?php

namespace Tests\Feature\PianoLit;

use App\Projects\PianoLit\{Admin, Piece};
use Tests\PianoLitTest;

class PieceTest extends PianoLitTest
{
	/** @test */
	public function all_managers_can_add_pieces_to_the_database()
	{
		$admin = create(Admin::class, ['role' => 'manager']);
		$piece = make(Piece::class)->toArray();
		$piece = array_merge($piece, [
			'period' => [1],
			'length' => [2],
			'level' => [3]
		]);

		$this->signIn($admin);

		$this->post(route('piano-lit.pieces.store'), $piece);

		$this->assertDatabaseHas('pieces', ['name' => $piece['name']]);
	}

	/** @test */
	public function all_managers_can_edit_the_pieces_in_the_database()
	{
		$admin = create(Admin::class, ['role' => 'manager']);
		$updatedPiece = make(Piece::class)->toArray();
		$updatedPiece = array_merge($updatedPiece, [
			'period' => [1],
			'length' => [2],
			'level' => [3]
		]);

		$this->signIn($admin);

		$this->patch(route('piano-lit.pieces.update', $this->piece->id), $updatedPiece);

		$this->assertEquals($this->piece->fresh()->name, $updatedPiece['name']);
	}

	/** @test */
	public function authorized_managers_can_delete_pieces_in_the_database()
	{
		$admin = create(Admin::class, ['role' => 'manager']);
		$pieceId = $this->piece->id;

		$this->signIn($admin);

		$this->delete(route('piano-lit.pieces.destroy', $this->piece->id));

		$this->assertDatabaseMissing('pieces', ['id' => $pieceId]);
	}

	/** @test */
	public function unauthorized_managers_cannot_delete_pieces_in_the_database()
	{
		$this->expectException('Illuminate\Auth\Access\AuthorizationException');

		$admin = create(Admin::class, ['role' => 'unauthorized']);

		$this->signIn($admin);

		$this->delete(route('piano-lit.pieces.destroy', $this->piece->id));
	}
}
