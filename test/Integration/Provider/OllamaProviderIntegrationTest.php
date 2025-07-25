<?php

declare(strict_types=1);

namespace Bono\Tests\Integration\Provider;

use Bono\Provider\OllamaProvider;
use PHPUnit\Framework\TestCase;

class OllamaProviderIntegrationTest extends TestCase
{
    public function testGenerateReturnsResponseFromOllama()
    {
        $provider = new OllamaProvider('http://localhost:11434/api');
        $prompt   = 'Sag Hallo';
        $result   = $provider->generate($prompt);

        // Prüfe, ob eine nicht-leere Antwort zurückkommt
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateWithCustomModel()
    {
        $provider = new OllamaProvider('http://localhost:11434/api');
        $prompt   = 'Sag Hallo mit custom model';
        $options  = ['model' => 'llama3.2:3b'];
        $result   = $provider->generate($prompt, $options);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }
}
