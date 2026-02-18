<?php

namespace App\Domain\Agent;

use Illuminate\Support\Facades\Log;

/**
 * Validates and filters section compose output against the section schema.
 * Strips settings keys not in the schema; ensures block types are allowed and count <= max_blocks.
 */
class SectionSchemaValidator
{
    /**
     * @param array<string, mixed> $schema Section schema (settings, blocks, max_blocks)
     * @param array{settings?: array, blocks?: array, block_order?: array} $payload Composed payload
     * @return array{settings: array, blocks: array, block_order: array} Filtered payload
     */
    public function validate(array $schema, array $payload): array
    {
        $allowedSettingIds = $this->allowedSettingIds($schema);
        $allowedBlockTypes = $this->allowedBlockTypes($schema);
        $maxBlocks = $schema['max_blocks'] ?? null;

        $settings = $payload['settings'] ?? [];
        $blocks = $payload['blocks'] ?? [];
        $blockOrder = $payload['block_order'] ?? [];

        $filteredSettings = [];
        $strippedSettings = [];
        foreach ($settings as $id => $value) {
            if (in_array($id, $allowedSettingIds, true)) {
                $filteredSettings[$id] = $value;
            } else {
                $strippedSettings[] = $id;
            }
        }
        if ($strippedSettings !== []) {
            Log::debug('SectionSchemaValidator: stripped settings', ['keys' => $strippedSettings]);
        }

        $filteredBlocks = [];
        $filteredBlockOrder = [];
        $blockCount = 0;
        foreach ($blockOrder as $blockId) {
            $block = $blocks[$blockId] ?? null;
            if ($block === null) {
                continue;
            }
            $type = $block['type'] ?? null;
            if ($type === null || ! in_array($type, $allowedBlockTypes, true)) {
                Log::debug('SectionSchemaValidator: skipped block (invalid type)', ['block_id' => $blockId, 'type' => $type]);
                continue;
            }
            if ($maxBlocks !== null && $blockCount >= $maxBlocks) {
                Log::debug('SectionSchemaValidator: skipped block (max_blocks)', ['block_id' => $blockId, 'max_blocks' => $maxBlocks]);
                continue;
            }
            $filteredBlocks[$blockId] = $block;
            $filteredBlockOrder[] = $blockId;
            $blockCount++;
        }

        return [
            'settings' => $filteredSettings,
            'blocks' => $filteredBlocks,
            'block_order' => $filteredBlockOrder,
        ];
    }

    /** @return list<string> */
    private function allowedSettingIds(array $schema): array
    {
        $ids = [];
        foreach ($schema['settings'] ?? [] as $s) {
            $id = $s['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    /** @return list<string> */
    private function allowedBlockTypes(array $schema): array
    {
        $types = [];
        foreach ($schema['blocks'] ?? [] as $b) {
            $type = $b['type'] ?? null;
            if (is_string($type) && $type !== '') {
                $types[] = $type;
            }
        }
        return $types;
    }
}
