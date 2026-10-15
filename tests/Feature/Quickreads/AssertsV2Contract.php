<?php

namespace Tests\Feature\Quickreads;

use stdClass;

trait AssertsV2Contract
{
    private array $spec;

    protected function resolve(array $schema): array
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

    protected function assertSchema($value, array $schema, string $path = '$'): void
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
                if (($schema['format'] ?? '') === 'uuid') $this->assertMatchesRegularExpression('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/Di', $value, $path);
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

    protected function assertResponse(string $path, string $method, $response): void
    {
        if (!isset($this->spec)) $this->spec = json_decode(file_get_contents(base_path('docs/openapi.yaml')), true, 512, JSON_THROW_ON_ERROR);
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

}
