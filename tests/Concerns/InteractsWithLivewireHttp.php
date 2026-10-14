<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;
use RuntimeException;

trait InteractsWithLivewireHttp
{
    protected function componentSnapshot(TestResponse $page, string $componentName): string
    {
        preg_match_all('/wire:snapshot="([^"]+)"/', $page->getContent(), $matches);

        foreach ($matches[1] as $encodedSnapshot) {
            $snapshot = json_decode(
                html_entity_decode($encodedSnapshot, ENT_QUOTES | ENT_HTML5),
                true,
                flags: JSON_THROW_ON_ERROR,
            );

            if (($snapshot['memo']['name'] ?? null) === $componentName) {
                return json_encode($snapshot, JSON_THROW_ON_ERROR);
            }
        }

        throw new RuntimeException("Livewire component snapshot [{$componentName}] was not found.");
    }

    protected function updateLivewireComponent(string $snapshot, array $updates = [], array $calls = []): TestResponse
    {
        $response = $this->withHeader('X-Livewire', true)->postJson(route('default-livewire.update'), [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => $updates,
                'calls' => array_map(
                    fn (array $call) => [
                        'method' => $call[0],
                        'params' => $call[1] ?? [],
                        'path' => '',
                    ],
                    $calls,
                ),
            ]],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(substr($response->getContent(), 0, 1000));
        }

        return $response;
    }

    protected function updatedSnapshot(TestResponse $response): string
    {
        $snapshot = $response->json('components.0.snapshot');

        if (! is_string($snapshot)) {
            throw new RuntimeException('The Livewire update response did not include a component snapshot.');
        }

        return $snapshot;
    }
}
