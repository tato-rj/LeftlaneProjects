<?php

namespace Tests\Feature\Quickreads;

use Illuminate\Support\Facades\Notification;

class OpenApiContractTest extends QuickreadsTestCase
{
    use AssertsV2Contract;

    public function setUp(): void
    {
        parent::setUp();
        // JSON syntax is the YAML 1.2 subset used by this authoritative file.
        $this->spec = json_decode(file_get_contents(base_path('docs/openapi.yaml')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_spec_resolves_every_reference_and_matches_all_registered_v2_methods(): void
    {
        $walk = function ($node) use (&$walk) {
            if (!is_array($node)) return;
            if (isset($node['$ref'])) $this->resolve($node);
            foreach ($node as $child) $walk($child);
        };
        $walk($this->spec);
        $actual = [];
        foreach ($this->app['router']->getRoutes() as $route) {
            if (!str_starts_with($route->uri(), 'api/v2/')) continue;
            foreach ($route->methods() as $method) $actual[] = strtolower($method).' /'.$route->uri();
        }
        $documented = [];
        $operationIds = [];
        foreach ($this->spec['paths'] as $path => $methods) {
            $this->assertStringStartsWith('/api/v2/', $path);
            foreach ($methods as $method => $operation) {
                $documented[] = $method.' '.$path;
                $operationIds[] = $operation['operationId'];
                preg_match_all('/\{([^}]+)\}/', $path, $matches);
                $parameters = array_map(fn ($parameter) => $this->resolve($parameter), $operation['parameters']);
                foreach ($matches[1] as $name) {
                    $parameter = array_values(array_filter($parameters, fn ($item) => $item['name'] === $name && $item['in'] === 'path'));
                    $this->assertCount(1, $parameter);
                    $this->assertTrue($parameter[0]['required']);
                }
            }
        }
        sort($actual); sort($documented);
        $this->assertSame($actual, $documented);
        $this->assertCount(count($operationIds), array_unique($operationIds));
    }

    public function test_catalog_and_error_representations_conform_to_the_contract(): void
    {
        foreach (['/stories', '/authors', '/categories', '/explore', '/stories/resolve?title=Story%201', '/stories/1', '/stories/1/content'] as $url) {
            $path = preg_replace('/\/[1-9][0-9]*(?=\/|$)/', '/{storyId}', strtok('/api/v2'.$url, '?'));
            $response = $this->getJson('/api/v2'.$url)->assertOk();
            $this->assertResponse($path, 'get', $response);
        }
        $response = $this->getJson('/api/v2/stories');
        $this->assertResponse('/api/v2/stories', 'get', $this->getJson('/api/v2/stories', ['If-None-Match' => $response->headers->get('ETag')])->assertStatus(304));
        $this->assertResponse('/api/v2/stories', 'head', $this->call('HEAD', '/api/v2/stories')->assertOk());
        $this->assertResponse('/api/v2/stories/{storyId}', 'get', $this->getJson('/api/v2/stories/999')->assertNotFound());
        $this->assertResponse('/api/v2/stories', 'get', $this->getJson('/api/v2/stories?page=0')->assertStatus(422));
        $this->assertResponse('/api/v2/me', 'get', $this->getJson('/api/v2/me')->assertUnauthorized());
    }

    public function test_authentication_and_private_responses_conform_to_the_contract(): void
    {
        Notification::fake();
        $user = $this->user();
        $input = ['email' => $user->email, 'password' => 'password123'];
        $this->assertResponse('/api/v2/auth/login', 'post', $this->postJson('/api/v2/auth/login', $input)->assertOk());
        $this->assertResponse('/api/v2/auth/register', 'post', $this->postJson('/api/v2/auth/register', [
            'email' => 'new@example.test', 'first_name' => 'New', 'last_name' => 'Reader', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertCreated());
        $headers = $this->bearer($user);
        $this->assertResponse('/api/v2/me', 'get', $this->getJson('/api/v2/me', $headers)->assertOk());
        $this->assertResponse('/api/v2/me/favorites/{storyId}', 'put', $this->putJson('/api/v2/me/favorites/1', ['ignored' => true], $headers)->assertOk());
        $this->assertResponse('/api/v2/me/favorites', 'get', $this->getJson('/api/v2/me/favorites', $headers)->assertOk());
        $this->assertResponse('/api/v2/me/favorites/{storyId}', 'delete', $this->deleteJson('/api/v2/me/favorites/1', ['ignored' => true], $headers)->assertOk());
        $this->assertResponse('/api/v2/auth/password/email', 'post', $this->postJson('/api/v2/auth/password/email', ['email' => 'absent@example.test'])->assertStatus(202));
        $token = \Illuminate\Support\Facades\Password::broker('quickreads-v2')->createToken($user);
        $this->assertResponse('/api/v2/auth/password/reset', 'post', $this->postJson('/api/v2/auth/password/reset', [
            'email' => $user->email, 'token' => $token, 'password' => 'changed123', 'password_confirmation' => 'changed123',
        ])->assertOk());
        $this->assertResponse('/api/v2/auth/logout', 'post', $this->postJson('/api/v2/auth/logout', ['ignored' => true], $this->bearer($user))->assertOk());
    }
}
