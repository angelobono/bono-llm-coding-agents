<?php

declare(strict_types=1);

namespace Bono\Provider;

/**
 * Interface for LLM (Large Language Model) providers.
 *
 * This interface defines the methods required for generating text
 * and streaming results from an LLM provider.
 */
interface LlmProviderInterface
{
    /**
     * Generates a text response based on the provided prompt and options.
     *
     * @param string $prompt The input prompt for the LLM.
     * @param array $options Optional parameters for the generation.
     * @return string The generated text response.
     */
    public function generate(string $prompt, array $options = []): string;

    /**
     * Generates a stream result based on the provided prompt and options.
     *
     * @param string $prompt The input prompt for the LLM.
     * @param array $options Optional parameters for the generation.
     * @return string The generated stream result.
     */
    public function generateStreamResult(
        string $prompt,
        array $options = []
    ): string;

    /**
     * Generates a stream of data based on the provided prompt and options.
     *
     * This method allows for real-time processing of the generated text,
     * calling the provided callback function with each piece of data.
     *
     * @param string $prompt The input prompt for the LLM.
     * @param callable $onData Callback function to handle each piece of generated data.
     * @param array $options Optional parameters for the generation.
     */
    public function generateStream(
        string $prompt,
        callable $onData,
        array $options = []
    ): void;
}
