<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Projects\Quickreads\Api\V2\{Catalog, Responses, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavoritesController extends Controller
{
    public function __construct(private Catalog $catalog) {}

    public function index(Request $request)
    {
        $this->catalog->beginRequest();
        $input = Validator::make($request->query(), [
            'page' => 'sometimes|required|integer|min:1|max:1000000',
            'per_page' => 'sometimes|required|integer|min:1|max:100',
        ])->validate();
        $input = ['page' => (int) ($input['page'] ?? 1), 'per_page' => (int) ($input['per_page'] ?? 20)];
        // EXISTS deduplicates the legacy table, which permits repeated saves.
        $query = $this->catalog->query()->whereExists(function ($query) use ($request) {
            $query->selectRaw('1')->from('user_purchase_records as f')
                ->whereColumn('f.story_id', 's.id')->where('f.user_id', $request->user()->id);
        })->orderBy('s.id');
        return Responses::json($request, $this->catalog->page($query, $input, '/api/v2/me/favorites',
            fn ($story) => $this->catalog->summary($story)), false);
    }

    public function put(Request $request, string $storyId)
    {
        return $this->mutate($request, $storyId, true);
    }

    public function delete(Request $request, string $storyId)
    {
        return $this->mutate($request, $storyId, false);
    }

    private function mutate(Request $request, string $storyId, bool $favorite)
    {
        // PUT needs an extant story. DELETE also works after editorial removal.
        if ($favorite) abort_unless($this->catalog->db()->table('stories')->where('id', $storyId)->exists(), 404);
        $this->catalog->db()->transaction(function () use ($request, $storyId, $favorite) {
            // Serialize v2 writes without adding a unique constraint that breaks legacy repeated POSTs.
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $query = $this->catalog->db()->table('user_purchase_records')
                ->where('user_id', $request->user()->id)->where('story_id', $storyId);
            if ($favorite && !$query->exists()) {
                $this->catalog->db()->table('user_purchase_records')->insert([
                    'user_id' => $request->user()->id, 'story_id' => $storyId,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            } elseif (!$favorite) {
                $query->delete();
            }
        });
        return Responses::json($request, ['data' => ['account_id' => (string) $request->user()->id,
            'story_id' => $storyId, 'favorited' => $favorite]], false);
    }
}
