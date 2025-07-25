<?php

declare(strict_types=1);

namespace Bono\Tests\Unit\Provider;

use Bono\Provider\OllamaProvider;
use Bono\Tests\Mock\HttpStreamMock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function in_array;
use function json_encode;
use function stream_get_wrappers;
use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;

class OllamaProviderTest extends TestCase
{
    protected function setUp(): void
    {
        if (in_array('http', stream_get_wrappers())) {
            stream_wrapper_unregister('http');
        }
        stream_wrapper_register('http', HttpStreamMock::class);
    }

    protected function tearDown(): void
    {
        if (in_array('http', stream_get_wrappers())) {
            stream_wrapper_restore('http');
        }
    }

    #[Test]
    public function generatesResponseWithValidPrompt()
    {
        $provider     = new OllamaProvider('http://localhost:11434/api');
        $mockResponse = json_encode(['response' => 'Hallo Welt']);
        stream_wrapper_unregister('http');
        stream_wrapper_register('http', HttpStreamMock::class);
        HttpStreamMock::$nextResponse = $mockResponse;

        $result = $provider->generate('Sag Hallo');
        $this->assertSame('Hallo Welt', $result);

        stream_wrapper_restore('http');
    }

    #[Test]
    public function returnsEmptyStringIfNoResponseKey()
    {
        $provider     = new OllamaProvider('http://localhost:11434/api');
        $mockResponse = json_encode(['other' => 'value']);
        stream_wrapper_unregister('http');
        stream_wrapper_register('http', HttpStreamMock::class);
        HttpStreamMock::$nextResponse = $mockResponse;

        $result = $provider->generate('Prompt ohne Antwort');
        $this->assertSame('', $result);

        stream_wrapper_restore('http');
    }

    #[Test]
    public function returnsEmptyStringOnInvalidJson()
    {
        $provider     = new OllamaProvider('http://localhost:11434/api');
        $mockResponse = 'Ungültiges JSON';
        stream_wrapper_unregister('http');
        stream_wrapper_register('http', HttpStreamMock::class);
        HttpStreamMock::$nextResponse = $mockResponse;

        $result = $provider->generate('Prompt mit Fehler');
        $this->assertSame('', $result);

        stream_wrapper_restore('http');
    }

    #[Test]
    public function usesDefaultModelIfNoneProvided()
    {
        $provider     = new OllamaProvider('http://localhost:11434/api');
        $mockResponse = json_encode(['response' => 'Default Model']);
        stream_wrapper_unregister('http');
        stream_wrapper_register('http', HttpStreamMock::class);
        HttpStreamMock::$nextResponse = $mockResponse;

        $provider->generate('Prompt ohne Modell');
        $payload = HttpStreamMock::$lastPayload;
        $this->assertSame('llama3.2:3b', $payload['model']);

        stream_wrapper_restore('http');
    }

    #[Test]
    public function usesProvidedModelIfGiven()
    {
        $provider     = new OllamaProvider('http://localhost:11434/api');
        $mockResponse = json_encode(['response' => 'Custom Model']);
        stream_wrapper_unregister('http');
        stream_wrapper_register('http', HttpStreamMock::class);
        HttpStreamMock::$nextResponse = $mockResponse;

        $provider->generate('Prompt mit Modell', ['model' => 'custom-model']);
        $payload = HttpStreamMock::$lastPayload;
        $this->assertSame('custom-model', $payload['model']);

        stream_wrapper_restore('http');
    }
}
