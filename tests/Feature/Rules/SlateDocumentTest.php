<?php

use App\Rules\SlateDocument;
use Illuminate\Support\Facades\Validator;

function makeValidator(mixed $value): \Illuminate\Validation\Validator
{
    return Validator::make(
        ['body' => $value],
        ['body' => ['required', new SlateDocument]],
    );
}

function validParagraph(string $text = 'Hello world'): array
{
    return [
        'type' => 'paragraph',
        'children' => [['text' => $text]],
    ];
}

// --- Valid documents ---

test('valid minimal paragraph document passes', function () {
    $document = [validParagraph()];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('valid document with multiple paragraphs passes', function () {
    $document = [
        validParagraph('First paragraph'),
        validParagraph('Second paragraph'),
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('valid document with heading passes', function () {
    $document = [
        [
            'type' => 'heading-one',
            'children' => [['text' => 'My Title']],
        ],
        validParagraph(),
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('valid document with heading-two passes', function () {
    $document = [
        [
            'type' => 'heading-two',
            'children' => [['text' => 'Subtitle']],
        ],
        validParagraph(),
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('valid document with blockquote passes', function () {
    $document = [
        [
            'type' => 'blockquote',
            'children' => [['text' => 'A wise quote']],
        ],
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('valid document with bulleted list passes', function () {
    $document = [
        [
            'type' => 'bulleted-list',
            'children' => [
                [
                    'type' => 'list-item',
                    'children' => [['text' => 'Item one']],
                ],
                [
                    'type' => 'list-item',
                    'children' => [['text' => 'Item two']],
                ],
            ],
        ],
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('valid document with numbered list passes', function () {
    $document = [
        [
            'type' => 'numbered-list',
            'children' => [
                [
                    'type' => 'list-item',
                    'children' => [['text' => 'First']],
                ],
            ],
        ],
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('valid document with text marks passes', function () {
    $document = [
        [
            'type' => 'paragraph',
            'children' => [
                ['text' => 'Normal text '],
                ['text' => 'bold text', 'bold' => true],
                ['text' => ' and '],
                ['text' => 'italic text', 'italic' => true],
                ['text' => ' and '],
                ['text' => 'underlined', 'underline' => true],
                ['text' => ' and '],
                ['text' => 'code', 'code' => true],
            ],
        ],
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('valid document with combined marks passes', function () {
    $document = [
        [
            'type' => 'paragraph',
            'children' => [
                ['text' => 'bold and italic', 'bold' => true, 'italic' => true],
            ],
        ],
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('valid document with image void element passes', function () {
    $document = [
        [
            'type' => 'image',
            'src' => 'https://example.com/image.jpg',
            'children' => [['text' => '']],
        ],
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('valid document with video void element passes', function () {
    $document = [
        [
            'type' => 'video',
            'src' => 'https://example.com/video.mp4',
            'children' => [['text' => '']],
        ],
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('valid document with document-embed void element passes', function () {
    $document = [
        [
            'type' => 'document-embed',
            'src' => 'https://example.com/file.pdf',
            'children' => [['text' => '']],
        ],
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('valid document with heading-three passes', function () {
    $document = [
        [
            'type' => 'heading-three',
            'children' => [['text' => 'Small heading']],
        ],
        validParagraph(),
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

// --- Invalid documents ---

test('non-array value fails', function () {
    expect(makeValidator('not an array')->fails())->toBeTrue();
});

test('non-array value string fails', function () {
    expect(makeValidator('hello')->fails())->toBeTrue();
});

test('integer value fails', function () {
    expect(makeValidator(42)->fails())->toBeTrue();
});

test('null value fails', function () {
    expect(makeValidator(null)->fails())->toBeTrue();
});

test('empty array fails', function () {
    expect(makeValidator([])->fails())->toBeTrue();
});

test('block missing type fails', function () {
    $document = [
        ['children' => [['text' => 'No type']]],
    ];

    expect(makeValidator($document)->fails())->toBeTrue();
});

test('block missing children fails', function () {
    $document = [
        ['type' => 'paragraph'],
    ];

    expect(makeValidator($document)->fails())->toBeTrue();
});

test('unknown block type fails', function () {
    $document = [
        [
            'type' => 'unknown-block',
            'children' => [['text' => 'Content']],
        ],
    ];

    expect(makeValidator($document)->fails())->toBeTrue();
});

test('void element missing src fails', function () {
    $document = [
        [
            'type' => 'image',
            'children' => [['text' => '']],
        ],
    ];

    expect(makeValidator($document)->fails())->toBeTrue();
});

test('video void element missing src fails', function () {
    $document = [
        [
            'type' => 'video',
            'children' => [['text' => '']],
        ],
    ];

    expect(makeValidator($document)->fails())->toBeTrue();
});

test('document exceeding max depth fails', function () {
    // MAX_DEPTH is 5. Build a structure where the deepest node exceeds depth 5.
    // 6 blockquote wraps + text = text at depth 6, which exceeds MAX_DEPTH
    $deep = ['text' => 'Too deep'];
    for ($i = 0; $i < 6; $i++) {
        $deep = [
            'type' => 'blockquote',
            'children' => [$deep],
        ];
    }

    $document = [$deep];

    expect(makeValidator($document)->fails())->toBeTrue();
});

test('document at exactly max depth passes', function () {
    // MAX_DEPTH is 5. Build a structure where the deepest node is at depth 5.
    // depth 0: blockquote -> depth 1: blockquote -> depth 2: blockquote ->
    // depth 3: blockquote -> depth 4: blockquote -> depth 5: text
    $node = ['text' => 'At max depth'];
    for ($i = 0; $i < 5; $i++) {
        $node = [
            'type' => 'blockquote',
            'children' => [$node],
        ];
    }

    $document = [$node];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('document exceeding max blocks fails', function () {
    // Create 101 blocks, exceeding the max of 100
    $document = [];
    for ($i = 0; $i < 101; $i++) {
        $document[] = validParagraph("Paragraph {$i}");
    }

    expect(makeValidator($document)->fails())->toBeTrue();
});

test('document at exactly max blocks passes', function () {
    $document = [];
    for ($i = 0; $i < 100; $i++) {
        $document[] = validParagraph("Paragraph {$i}");
    }

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('block with non-array children fails', function () {
    $document = [
        [
            'type' => 'paragraph',
            'children' => 'not an array',
        ],
    ];

    expect(makeValidator($document)->fails())->toBeTrue();
});

test('block with empty children passes', function () {
    // Slate.js allows elements with empty children arrays in valid states
    $document = [
        [
            'type' => 'paragraph',
            'children' => [],
        ],
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('json string document is decoded and validated', function () {
    $document = json_encode([validParagraph()]);

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('invalid json string fails', function () {
    expect(makeValidator('{invalid json')->fails())->toBeTrue();
});

test('text node with unsupported property fails', function () {
    $document = [
        [
            'type' => 'paragraph',
            'children' => [
                ['text' => 'Styled text', 'strikethrough' => true],
            ],
        ],
    ];

    expect(makeValidator($document)->fails())->toBeTrue();
});

test('mark with non-boolean value fails', function () {
    $document = [
        [
            'type' => 'paragraph',
            'children' => [
                ['text' => 'Styled text', 'bold' => 'yes'],
            ],
        ],
    ];

    expect(makeValidator($document)->fails())->toBeTrue();
});

// --- Mention validation ---

test('valid mention node passes', function () {
    $document = [
        [
            'type' => 'paragraph',
            'children' => [
                ['text' => 'Hello '],
                [
                    'type' => 'mention',
                    'userId' => 1,
                    'username' => 'johndoe',
                    'children' => [['text' => '']],
                ],
                ['text' => ' welcome!'],
            ],
        ],
    ];

    expect(makeValidator($document)->passes())->toBeTrue();
});

test('mention node missing userId fails', function () {
    $document = [
        [
            'type' => 'paragraph',
            'children' => [
                [
                    'type' => 'mention',
                    'username' => 'johndoe',
                    'children' => [['text' => '']],
                ],
            ],
        ],
    ];

    expect(makeValidator($document)->fails())->toBeTrue();
});

test('mention node with non-integer userId fails', function () {
    $document = [
        [
            'type' => 'paragraph',
            'children' => [
                [
                    'type' => 'mention',
                    'userId' => 'not-an-int',
                    'username' => 'johndoe',
                    'children' => [['text' => '']],
                ],
            ],
        ],
    ];

    expect(makeValidator($document)->fails())->toBeTrue();
});

test('mention node missing username fails', function () {
    $document = [
        [
            'type' => 'paragraph',
            'children' => [
                [
                    'type' => 'mention',
                    'userId' => 1,
                    'children' => [['text' => '']],
                ],
            ],
        ],
    ];

    expect(makeValidator($document)->fails())->toBeTrue();
});

test('mention node with non-string username fails', function () {
    $document = [
        [
            'type' => 'paragraph',
            'children' => [
                [
                    'type' => 'mention',
                    'userId' => 1,
                    'username' => 123,
                    'children' => [['text' => '']],
                ],
            ],
        ],
    ];

    expect(makeValidator($document)->fails())->toBeTrue();
});

test('mention node with invalid children fails', function () {
    $document = [
        [
            'type' => 'paragraph',
            'children' => [
                [
                    'type' => 'mention',
                    'userId' => 1,
                    'username' => 'johndoe',
                    'children' => [['text' => 'non-empty']],
                ],
            ],
        ],
    ];

    expect(makeValidator($document)->fails())->toBeTrue();
});
