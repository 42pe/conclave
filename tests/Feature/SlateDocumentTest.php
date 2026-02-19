<?php

use App\Rules\SlateDocument;
use Illuminate\Support\Facades\Validator;

function validatesSlateDocument(mixed $value): bool
{
    return Validator::make(
        ['body' => $value],
        ['body' => [new SlateDocument]],
    )->passes();
}

// --- Valid documents ---

test('valid simple document with one paragraph', function () {
    expect(validatesSlateDocument([
        ['type' => 'paragraph', 'children' => [['text' => 'Hello world']]],
    ]))->toBeTrue();
});

test('valid document with multiple block types', function () {
    expect(validatesSlateDocument([
        ['type' => 'heading-one', 'children' => [['text' => 'Title']]],
        ['type' => 'paragraph', 'children' => [['text' => 'Some text']]],
        ['type' => 'blockquote', 'children' => [['text' => 'A quote']]],
    ]))->toBeTrue();
});

test('valid document with text marks', function () {
    expect(validatesSlateDocument([
        ['type' => 'paragraph', 'children' => [
            ['text' => 'Bold text', 'bold' => true],
            ['text' => ' and '],
            ['text' => 'italic', 'italic' => true],
            ['text' => ' and '],
            ['text' => 'underline', 'underline' => true],
            ['text' => ' and '],
            ['text' => 'code', 'code' => true],
        ]],
    ]))->toBeTrue();
});

test('valid document with nested lists', function () {
    expect(validatesSlateDocument([
        ['type' => 'bulleted-list', 'children' => [
            ['type' => 'list-item', 'children' => [['text' => 'Item one']]],
            ['type' => 'list-item', 'children' => [['text' => 'Item two']]],
        ]],
        ['type' => 'numbered-list', 'children' => [
            ['type' => 'list-item', 'children' => [['text' => 'First']]],
        ]],
    ]))->toBeTrue();
});

test('valid void element (image)', function () {
    expect(validatesSlateDocument([
        ['type' => 'image', 'url' => 'https://example.com/img.jpg', 'children' => [['text' => '']]],
    ]))->toBeTrue();
});

test('valid void element (video)', function () {
    expect(validatesSlateDocument([
        ['type' => 'video', 'url' => 'https://example.com/vid.mp4', 'children' => [['text' => '']]],
    ]))->toBeTrue();
});

test('valid void element (document-embed)', function () {
    expect(validatesSlateDocument([
        ['type' => 'document-embed', 'url' => 'https://example.com/doc.pdf', 'children' => [['text' => '']]],
    ]))->toBeTrue();
});

test('valid link inline element', function () {
    expect(validatesSlateDocument([
        ['type' => 'paragraph', 'children' => [
            ['text' => 'Visit '],
            ['type' => 'link', 'url' => 'https://example.com', 'children' => [['text' => 'here']]],
        ]],
    ]))->toBeTrue();
});

test('valid mixed content document', function () {
    expect(validatesSlateDocument([
        ['type' => 'heading-one', 'children' => [['text' => 'Welcome']]],
        ['type' => 'paragraph', 'children' => [['text' => 'Hello ', 'bold' => true], ['text' => 'world']]],
        ['type' => 'image', 'url' => 'https://example.com/img.jpg', 'children' => [['text' => '']]],
        ['type' => 'bulleted-list', 'children' => [
            ['type' => 'list-item', 'children' => [['text' => 'Point one']]],
        ]],
        ['type' => 'blockquote', 'children' => [['text' => 'A quote']]],
    ]))->toBeTrue();
});

test('valid deeply nested but within limit', function () {
    // 5 levels of nesting — should be accepted
    expect(validatesSlateDocument([
        ['type' => 'blockquote', 'children' => [
            ['type' => 'blockquote', 'children' => [
                ['type' => 'blockquote', 'children' => [
                    ['type' => 'blockquote', 'children' => [
                        ['type' => 'paragraph', 'children' => [['text' => 'Deep']]],
                    ]],
                ]],
            ]],
        ]],
    ]))->toBeTrue();
});

// --- Invalid documents ---

test('invalid: not an array (string)', function () {
    expect(validatesSlateDocument('hello'))->toBeFalse();
});

test('invalid: not an array (null)', function () {
    expect(validatesSlateDocument(null))->toBeFalse();
});

test('invalid: empty array', function () {
    expect(validatesSlateDocument([]))->toBeFalse();
});

test('invalid: node missing type', function () {
    expect(validatesSlateDocument([
        ['children' => [['text' => 'No type']]],
    ]))->toBeFalse();
});

test('invalid: node missing children', function () {
    expect(validatesSlateDocument([
        ['type' => 'paragraph'],
    ]))->toBeFalse();
});

test('invalid: unknown block type', function () {
    expect(validatesSlateDocument([
        ['type' => 'unknown-type', 'children' => [['text' => 'Content']]],
    ]))->toBeFalse();
});

test('invalid: text node missing text key', function () {
    expect(validatesSlateDocument([
        ['type' => 'paragraph', 'children' => [['bold' => true]]],
    ]))->toBeFalse();
});

test('invalid: void element missing url', function () {
    expect(validatesSlateDocument([
        ['type' => 'image', 'children' => [['text' => '']]],
    ]))->toBeFalse();
});

test('invalid: void element with non-empty text', function () {
    expect(validatesSlateDocument([
        ['type' => 'image', 'url' => 'https://example.com/img.jpg', 'children' => [['text' => 'not empty']]],
    ]))->toBeFalse();
});

test('invalid: exceeds max depth', function () {
    // 7 levels deep — should fail (max is 5)
    expect(validatesSlateDocument([
        ['type' => 'blockquote', 'children' => [
            ['type' => 'blockquote', 'children' => [
                ['type' => 'blockquote', 'children' => [
                    ['type' => 'blockquote', 'children' => [
                        ['type' => 'blockquote', 'children' => [
                            ['type' => 'blockquote', 'children' => [
                                ['type' => 'paragraph', 'children' => [['text' => 'Too deep']]],
                            ]],
                        ]],
                    ]],
                ]],
            ]],
        ]],
    ]))->toBeFalse();
});

test('invalid: exceeds max block count', function () {
    $blocks = [];
    for ($i = 0; $i < 101; $i++) {
        $blocks[] = ['type' => 'paragraph', 'children' => [['text' => "Block $i"]]];
    }
    expect(validatesSlateDocument($blocks))->toBeFalse();
});

// --- Null text handling (ConvertEmptyStringsToNull middleware) ---

test('valid void element with null text child (middleware converts empty to null)', function () {
    expect(validatesSlateDocument([
        ['type' => 'image', 'url' => 'https://example.com/img.jpg', 'children' => [['text' => null]]],
    ]))->toBeTrue();
});

test('valid paragraph with null text (middleware converts empty to null)', function () {
    expect(validatesSlateDocument([
        ['type' => 'paragraph', 'children' => [['text' => null]]],
    ]))->toBeTrue();
});

test('valid document with image and trailing empty paragraph (null text)', function () {
    expect(validatesSlateDocument([
        ['type' => 'paragraph', 'children' => [['text' => 'Some text']]],
        ['type' => 'image', 'url' => 'https://example.com/img.jpg', 'children' => [['text' => null]]],
        ['type' => 'paragraph', 'children' => [['text' => null]]],
    ]))->toBeTrue();
});

test('invalid: link missing url', function () {
    expect(validatesSlateDocument([
        ['type' => 'paragraph', 'children' => [
            ['type' => 'link', 'children' => [['text' => 'broken link']]],
        ]],
    ]))->toBeFalse();
});
