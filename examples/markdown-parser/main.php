<?php

function parseMarkdown(string $markdown): string {
    $lines = explode("\n", $markdown);
    $html = [];
    $inCodeBlock = false;
    $inList = false;
    $listType = '';
    $inBlockquote = false;

    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '```')) {
            if ($inCodeBlock) {
                $html[] = '</code></pre>';
                $inCodeBlock = false;
            } else {
                $lang = trim(substr(trim($line), 3));
                $html[] = '<pre><code' . ($lang ? ' class="language-' . $lang . '"' : '') . '>';
                $inCodeBlock = true;
            }
            continue;
        }

        if ($inCodeBlock) {
            $html[] = htmlspecialchars($line);
            continue;
        }

        if ($inList && !preg_match('/^\s*[-*]\s/', $line) && !preg_match('/^\s*\d+\.\s/', $line) && trim($line) !== '') {
            $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
            $inList = false;
        }

        if ($inBlockquote && !str_starts_with($line, '>')) {
            $html[] = '</blockquote>';
            $inBlockquote = false;
        }

        if (trim($line) === '') {
            if ($inList) {
                $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                $inList = false;
            }
            continue;
        }

        if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $matches)) {
            $level = strlen($matches[1]);
            $text = parseInline($matches[2]);
            $html[] = "<h$level>$text</h$level>";
            continue;
        }

        if (preg_match('/^[-*_]{3,}$/', trim($line))) {
            $html[] = '<hr>';
            continue;
        }

        if (str_starts_with($line, '>')) {
            if (!$inBlockquote) {
                $html[] = '<blockquote>';
                $inBlockquote = true;
            }
            $content = trim(substr($line, 1));
            $html[] = '<p>' . parseInline($content) . '</p>';
            continue;
        }

        if (preg_match('/^\s*[-*]\s+(.+)$/', $line, $matches)) {
            if (!$inList || $listType !== 'ul') {
                if ($inList) $html[] = '</ol>';
                $html[] = '<ul>';
                $inList = true;
                $listType = 'ul';
            }
            $html[] = '<li>' . parseInline($matches[1]) . '</li>';
            continue;
        }

        if (preg_match('/^\s*\d+\.\s+(.+)$/', $line, $matches)) {
            if (!$inList || $listType !== 'ol') {
                if ($inList) $html[] = '</ul>';
                $html[] = '<ol>';
                $inList = true;
                $listType = 'ol';
            }
            $html[] = '<li>' . parseInline($matches[1]) . '</li>';
            continue;
        }

        $html[] = '<p>' . parseInline($line) . '</p>';
    }

    if ($inCodeBlock) $html[] = '</code></pre>';
    if ($inList) $html[] = ($listType === 'ul' ? '</ul>' : '</ol>');
    if ($inBlockquote) $html[] = '</blockquote>';

    return implode("\n", $html);
}

function parseInline(string $text): string {
    $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);

    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);

    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);

    $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $text);

    return $text;
}


$markdown = <<<'MD'
# Markdown Parser

This is a **bold** statement with *italic* and ***bold italic*** text.

## Features

- Headings (h1-h6)
- **Bold** and *italic*
- [Links](https://example.com)
- `inline code`

### Ordered Lists

1. First item
2. Second item
3. Third item

## Code Blocks

```php
function hello() {
    echo "Hello, World!";
}
```

## Blockquotes

> This is a quote.
> It can span multiple lines.

---

## Images

![Alt text](image.png)

That's all, folks!
MD;

$html = parseMarkdown($markdown);
echo $html . "\n";


echo "\nInline parsing:\n";
$tests = [
    ['**bold**', '<strong>bold</strong>'],
    ['*italic*', '<em>italic</em>'],
    ['***both***', '<strong><em>both</em></strong>'],
    ['`code`', '<code>code</code>'],
    ['[link](url)', '<a href="url">link</a>'],
    ['![img](src)', '<img src="src" alt="img">'],
];

foreach ($tests as $test) {
    $result = parseInline($test[0]);
    $match = $result === $test[1];
    echo "  " . str_pad($test[0], 20) . " -> " . $result . ($match ? '' : ' (EXPECTED: ' . $test[1] . ')') . "\n";
}
