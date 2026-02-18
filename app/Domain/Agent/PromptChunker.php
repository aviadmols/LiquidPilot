<?php

namespace App\Domain\Agent;

/**
 * Splits large text/JSON by estimated token size (strlen/4 heuristic).
 * Returns array of chunks with optional metadata for map-reduce.
 *
 * Inputs: Full text, max tokens per chunk (default 2000).
 * Outputs: list of ['text' => string, 'index' => int, 'total' => int].
 * Side effects: none.
 */
class PromptChunker
{
    private int $charsPerChunk;

    public function __construct(int $maxTokensPerChunk = 2000)
    {
        $this->charsPerChunk = $maxTokensPerChunk * 4;
    }

    /**
     * @return list<array{text: string, index: int, total: int}>
     */
    public function chunk(string $fullText): array
    {
        if ($fullText === '') {
            return [];
        }
        $len = strlen($fullText);
        if ($len <= $this->charsPerChunk) {
            return [['text' => $fullText, 'index' => 0, 'total' => 1]];
        }

        $chunks = [];
        $offset = 0;
        $index = 0;
        while ($offset < $len) {
            $slice = substr($fullText, $offset, $this->charsPerChunk);
            $chunks[] = ['text' => $slice, 'index' => $index, 'total' => 0];
            $offset += strlen($slice);
            $index++;
        }
        $total = count($chunks);
        foreach ($chunks as $i => $c) {
            $chunks[$i]['total'] = $total;
        }
        return $chunks;
    }
}
