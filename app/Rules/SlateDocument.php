<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SlateDocument implements ValidationRule
{
    private const MAX_DEPTH = 5;

    private const MAX_BLOCKS = 100;

    /** @var list<string> */
    private const ALLOWED_BLOCK_TYPES = [
        'paragraph',
        'heading-one',
        'heading-two',
        'heading-three',
        'bulleted-list',
        'numbered-list',
        'list-item',
        'blockquote',
        'image',
        'video',
        'document-embed',
    ];

    /** @var list<string> */
    private const VOID_TYPES = [
        'image',
        'video',
        'document-embed',
    ];

    /** @var list<string> */
    private const ALLOWED_MARKS = [
        'bold',
        'italic',
        'underline',
        'code',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            $fail('The :attribute must be a valid Slate document.');

            return;
        }

        if (count($value) === 0) {
            $fail('The :attribute must contain at least one block.');

            return;
        }

        if (count($value) > self::MAX_BLOCKS) {
            $fail('The :attribute must not exceed '.self::MAX_BLOCKS.' blocks.');

            return;
        }

        foreach ($value as $node) {
            if (! $this->validateNode($node, 0, $fail)) {
                return;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function validateNode(mixed $node, int $depth, Closure $fail): bool
    {
        if ($depth > self::MAX_DEPTH) {
            $fail('The :attribute exceeds the maximum nesting depth of '.self::MAX_DEPTH.'.');

            return false;
        }

        if (! is_array($node)) {
            $fail('The :attribute contains an invalid node.');

            return false;
        }

        // Text node
        if (array_key_exists('text', $node)) {
            return $this->validateTextNode($node, $fail);
        }

        // Element node
        if (! isset($node['type'])) {
            $fail('The :attribute contains a node without a type.');

            return false;
        }

        if (! in_array($node['type'], self::ALLOWED_BLOCK_TYPES, true)) {
            $fail('The :attribute contains an unsupported block type: '.$node['type'].'.');

            return false;
        }

        if (! isset($node['children']) || ! is_array($node['children'])) {
            $fail('The :attribute contains a block without children.');

            return false;
        }

        // Void elements must have required attributes
        if (in_array($node['type'], self::VOID_TYPES, true)) {
            if (empty($node['src'])) {
                $fail('The :attribute contains a '.$node['type'].' without a src attribute.');

                return false;
            }
        }

        foreach ($node['children'] as $child) {
            if (! $this->validateNode($child, $depth + 1, $fail)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function validateTextNode(array $node, Closure $fail): bool
    {
        if (! is_string($node['text'])) {
            $fail('The :attribute contains a text node with invalid text.');

            return false;
        }

        $allowedKeys = array_merge(['text'], self::ALLOWED_MARKS);

        foreach (array_keys($node) as $key) {
            if (! in_array($key, $allowedKeys, true)) {
                $fail('The :attribute contains an unsupported text property: '.$key.'.');

                return false;
            }
        }

        foreach (self::ALLOWED_MARKS as $mark) {
            if (isset($node[$mark]) && ! is_bool($node[$mark])) {
                $fail('The :attribute contains a mark that is not a boolean: '.$mark.'.');

                return false;
            }
        }

        return true;
    }
}
