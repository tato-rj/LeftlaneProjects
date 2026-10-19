<?php

namespace Tests\Feature\Quickreads;

use Illuminate\Foundation\Testing\TestCase;
use Tests\CreatesApplication;

class PrivacyPolicyTest extends TestCase
{
    use CreatesApplication;

    public function test_public_policy_works_with_stale_disabled_configuration(): void
    {
        $this->app->instance('env', 'production');
        config(['quickreads_privacy.published' => false]);

        $this->get('/quickreads/privacy')->assertOk()->assertSee('Quick Reads Privacy Policy');
    }

    public function test_public_policy_needs_no_publication_configuration_or_cookies(): void
    {
        $this->app->instance('env', 'production');
        config(['quickreads_privacy' => null]);

        $response = $this->get('/quickreads/privacy')->assertOk()
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertDontSee('noindex, nofollow')
            ->assertSee('Quick Reads Privacy Policy')
            ->assertSee('Quick Reads does not receive your Apple Account password')
            ->assertSee('Guests do not send reading-activity reports to us');

        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_policy_is_public_stateless_and_has_no_active_external_resources(): void
    {
        $this->app->instance('env', 'production');

        $response = $this->get('/quickreads/privacy')->assertOk()
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'self'; img-src 'self'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");

        $this->assertSame([], $response->headers->getCookies());
        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(0, $xpath->query('//script | //iframe | //object | //embed | //form')->length);
        foreach ($xpath->query('//link[@href] | //img[@src]') as $resource) {
            $url = $resource->getAttribute($resource->hasAttribute('src') ? 'src' : 'href');
            $this->assertMatchesRegularExpression('~^/(?!/)~', $url);
        }
        $this->assertSame(0, $xpath->query('//meta[@name="robots"]')->length);
    }

    public function test_policy_head_request_is_public_and_section_links_have_targets_without_javascript(): void
    {
        $this->head('/quickreads/privacy')->assertOk()->assertContent('');

        $document = new \DOMDocument();
        @$document->loadHTML($this->get('/quickreads/privacy')->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//h1')->length);
        foreach ($xpath->query('//a[starts-with(@href, "#")]') as $link) {
            $id = substr($link->getAttribute('href'), 1);
            $this->assertSame(1, $xpath->query('//*[@id="'.$id.'"]')->length, 'Missing or duplicate anchor: '.$id);
        }
    }
}
