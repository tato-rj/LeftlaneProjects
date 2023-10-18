<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Jobs\ProcessVideo;
use App\Projects\VideoUploader\{Video, Admin};

class VideoTest extends TestCase
{
    use WithFaker;

    public function setUp() : void
    {
        parent::setUp();
        
        \Storage::fake('public');
        \Storage::fake('gcs');
        \Queue::fake();
        
        $this->actingAs(Admin::factory()->create());

        auth()->user()->createToken('token');

        $this->videoRequest = [
            'secret' => auth()->user()->tokens->first()->name,
            'origin' => 'test',
            'video' => UploadedFile::fake()->image('cover.mp4'),
            'email' => $this->faker->email,
            'user_id' => $this->faker->randomDigit,
            'piece_id' => $this->faker->randomDigit
        ];

        $this->setUpFaker();
    }

    /** @test */
    public function api_requests_need_authentication()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        array_shift($this->videoRequest);

        $this->post(route('videouploader.upload'), $this->videoRequest);
    }

    /** @test */
    public function it_returns_validation_errors_in_json_to_api_requests()
    {
        array_pop($this->videoRequest);

        $this->postJson(route('videouploader.upload'), $this->videoRequest)->assertJson([
            'piece_id' => ['The piece id field is required.']
        ]);
    }
}
