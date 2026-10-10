<?php

namespace App\Docs;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * A fenced code block, ```php title="layout.php": in the frame of the code of the tutorials, with the title in
 * its title bar, for Prism to highlight.
 */
class CodeBlockRenderer implements NodeRendererInterface
{
    /** What Prism calls the languages of the documentation. */
    private const LANGUAGES = [
        'shell' => 'bash',
        'sh' => 'bash',
        'html' => 'markup',
        'blade' => 'markup',
        'xml' => 'markup',
        'text' => 'none',
        '' => 'none',
    ];

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        /** @var FencedCode $node */
        $info = trim($node->getInfo() ?? '');
        $language = strtolower(strtok($info, " \t") ?: '');
        $language = self::LANGUAGES[$language] ?? $language;
        $title = preg_match('/title="([^"]*)"/', $info, $match) ? $match[1] : strtoupper($language === 'none' ? '' : $language);

        $code = htmlspecialchars(rtrim($node->getLiteral(), "\n"), ENT_NOQUOTES);
        $lines = $language === 'none' ? '' : ' line-numbers';

        return '<div class="code-window doc-code"><div class="chrome"><i></i><i></i><i></i>'
            .($title === '' ? '' : '<span>'.htmlspecialchars($title).'</span>')
            .'</div><pre class="language-'.$language.$lines.'"><code class="language-'.$language.'">'.$code.'</code></pre></div>'."\n";
    }
}
