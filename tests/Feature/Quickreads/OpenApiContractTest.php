<?php

namespace Tests\Feature\Quickreads;

use Illuminate\Support\Facades\Notification;
use stdClass;

class OpenApiContractTest extends QuickreadsTestCase
{
    private array $spec;

    public function setUp(): void
    {
        parent::setUp();
        // JSON syntax is the YAML 1.2 subset used by this authoritative file.
        $this->spec = json_decode(file_get_contents(base_path('docs/openapi.yaml')), true, 512, JSON_THROW_ON_ERROR);
    }

    private function resolve(array $schema): array
    {
        if (!isset($schema['$ref'])) return $schema;
        $parts = explode('/', substr($schema['$ref'], 2));
        $target = $this->spec;
        foreach ($parts as $part) {
            $this->assertArrayHasKey($part, $target, 'Unresolved '.$schema['$ref']);
            $target = $target[$part];
        }
        return $target;
    }

    private function assertSchema($value, array $schema, string $path = '$'): void
    {
        $schema = $this->resolve($schema);
        if ($value === null && ($schema['nullable'] ?? false)) return;
        foreach ($schema['allOf'] ?? [] as $part) $this->assertSchema($value, $part, $path);
        if (isset($schema['enum'])) $this->assertContains($value, $schema['enum'], $path, true);
        switch ($schema['type'] ?? null) {
            case 'object':
                $this->assertInstanceOf(stdClass::class, $value, $path);
                $properties = get_object_vars($value);
                foreach ($schema['required'] ?? [] as $name) $this->assertArrayHasKey($name, $properties, $path);
                foreach ($properties as $name => $item) {
                    if (isset($schema['properties'][$name])) $this->assertSchema($item, $schema['properties'][$name], $path.'.'.$name);
                    elseif (is_array($schema['additionalProperties'] ?? null)) $this->assertSchema($item, $schema['additionalProperties'], $path.'.'.$name);
                    elseif (($schema['additionalProperties'] ?? true) === false) $this->fail('Undocumented property '.$path.'.'.$name);
                }
                break;
            case 'array':
                $this->assertIsArray($value, $path);
                foreach ($value as $index => $item) $this->assertSchema($item, $schema['items'], $path.'.'.$index);
                break;
            case 'string':
                $this->assertIsString($value, $path);
                if (isset($schema['pattern'])) $this->assertMatchesRegularExpression('~'.$schema['pattern'].'~uD', $value, $path);
                if (($schema['format'] ?? '') === 'date-time') $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|\+00:00)$/D', $value, $path);
                if (($schema['format'] ?? '') === 'uri') $this->assertNotFalse(filter_var($value, FILTER_VALIDATE_URL), $path);
                if (isset($schema['maxLength'])) $this->assertLessThanOrEqual($schema['maxLength'], mb_strlen($value), $path);
                if (isset($schema['minLength'])) $this->assertGreaterThanOrEqual($schema['minLength'], mb_strlen($value), $path);
                break;
            case 'boolean': $this->assertIsBool($value, $path); break;
            case 'integer': $this->assertIsInt($value, $path); break;
            case 'number': $this->assertTrue(is_int($value) || is_float($value), $path); break;
        }
        if (isset($schema['minimum'])) $this->assertGreaterThanOrEqual($schema['minimum'], $value, $path);
        if (isset($schema['maximum'])) $this->assertLessThanOrEqual($schema['maximum'], $value, $path);
    }

    private function assertResponse(string $path, string $method, $response): void
    {
        $operation = $this->spec['paths'][$path][$method];
        $status = (string) $response->status();
        $this->assertArrayHasKey($status, $operation['responses']);
        $documented = $this->resolve($operation['responses'][$status]);
        if (isset($documented['content'])) {
            $this->assertSchema(json_decode($response->getContent(), false, 512, JSON_THROW_ON_ERROR), $documented['content']['application/json']['schema']);
        } else {
            $response->assertContent('');
        }
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
