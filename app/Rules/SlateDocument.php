<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SlateDocument implements ValidationRule
{
    private const MAX_DEPTH = 5;

    private const MAX_BLOCKS = 100;

    /** @var list<string> */
    private const BLOCK_TYPES = [
        'paragraph',
        'heading-one',
        'heading-two',
        'heading-three',
        'bulleted-list',
        'numbered-list',
        'list-item',
        'blockquote',
    ];

    /** @var list<string> */
    private const VOID_TYPES = [
        'image',
        'video',
        'document-embed',
    ];

    /** @var list<string> */
    private const INLINE_TYPES = [
        'link',
    ];

    /** @var list<string> */
    private const MARK_KEYS = [
        'bold',
        'italic',
        'underline',
        'code',
    ];

    private int $blockCount = 0;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $this->blockCount = 0;

        if (! is_array($value)) {
            $fail('The :attribute must be a valid Slate document.');

            return;
        }

        if (count($value) === 0) {
            $fail('The :attribute must not be empty.');

            return;
        }

        foreach ($value as $node) {
            if (! $this->isValidNode($node, 0)) {
                $fail('The :attribute contains an invalid Slate document structure.');

                return;
            }
        }
    }

    private function isValidNode(mixed $node, int $depth): bool
    {
        if (! is_array($node)) {
            return false;
        }

        // Text node
        if (array_key_exists('text', $node)) {
            return $this->isValidTextNode($node);
        }

        // Element node — must have type and children
        if (! isset($node['type']) || ! is_string($node['type'])) {
            return false;
        }

        if (! isset($node['children']) || ! is_array($node['children'])) {
            return false;
        }

        $type = $node['type'];

        // Void elements
        if (in_array($type, self::VOID_TYPES, true)) {
            return $this->isValidVoidElement($node);
        }

        // Inline elements
        if (in_array($type, self::INLINE_TYPES, true)) {
            return $this->isValidInlineElement($node, $depth);
        }

        // Block elements
        if (! in_array($type, self::BLOCK_TYPES, true)) {
            return false;
        }

        $this->blockCount++;
        if ($this->blockCount > self::MAX_BLOCKS) {
            return false;
        }

        if ($depth > self::MAX_DEPTH) {
            return false;
        }

        foreach ($node['children'] as $child) {
            if (! $this->isValidNode($child, $depth + 1)) {
                return false;
            }
        }

        return true;
    }

    private function isValidTextNode(mixed $node): bool
    {
        if (! is_string($node['text'])) {
            return false;
        }

        // Check that any mark keys are booleans
        foreach (self::MARK_KEYS as $mark) {
            if (array_key_exists($mark, $node) && ! is_bool($node[$mark])) {
                return false;
            }
        }

        return true;
    }

    private function isValidVoidElement(array $node): bool
    {
        if (! isset($node['url']) || ! is_string($node['url'])) {
            return false;
        }

        // Void elements must have exactly one child: an empty text node
        if (count($node['children']) !== 1) {
            return false;
        }

        $child = $node['children'][0];

        if (! is_array($child) || ! array_key_exists('text', $child)) {
            return false;
        }

        if ($child['text'] !== '') {
            return false;
        }

        return true;
    }

    private function isValidInlineElement(array $node, int $depth): bool
    {
        $type = $node['type'];

        if ($type === 'link') {
            if (! isset($node['url']) || ! is_string($node['url'])) {
                return false;
            }

            // Link children must be text nodes
            foreach ($node['children'] as $child) {
                if (! is_array($child) || ! array_key_exists('text', $child)) {
                    return false;
                }

                if (! $this->isValidTextNode($child)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
