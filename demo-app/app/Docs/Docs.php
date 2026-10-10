<?php

namespace App\Docs;

use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;

/**
 * The documentation: the markdown files of the repository, rendered the way Docusaurus does. A file has a
 * front matter (id, title, sidebar_label), headings with anchors, tables, and code blocks with a title.
 */
class Docs
{
    /** @var array<string, array{title: string, label: string}> */
    private array $meta = [];

    public function directory(): ?string
    {
        foreach (config('docs.paths') as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return list<string> the names of the pages, in the order of the sidebar
     */
    public function slugs(): array
    {
        return array_merge(...array_values(config('docs.sidebar')));
    }

    public function exists(string $slug): bool
    {
        return in_array($slug, $this->slugs(), true) && is_file($this->file($slug));
    }

    /**
     * @return array<string, list<array{slug: string, label: string}>> the groups of the sidebar
     */
    public function sidebar(): array
    {
        $sidebar = [];

        foreach (config('docs.sidebar') as $group => $slugs) {
            foreach ($slugs as $slug) {
                if ($this->exists($slug)) {
                    $sidebar[$group][] = ['slug' => $slug, 'label' => $this->meta($slug)['label']];
                }
            }
        }

        return $sidebar;
    }

    /**
     * @return array{slug: string, title: string, heading: string, group: string, html: string, toc: list<array{id: string, text: string, level: int}>, previous: ?array, next: ?array, edit: string}
     */
    public function page(string $slug): array
    {
        $meta = $this->meta($slug);
        $result = $this->converter()->convert($this->body($slug));
        $slugs = array_values(array_filter($this->slugs(), fn (string $other) => $this->exists($other)));
        $index = array_search($slug, $slugs, true);
        $link = fn (?string $other) => $other === null ? null : ['slug' => $other, 'label' => $this->meta($other)['label']];

        return [
            'slug' => $slug,
            'title' => $meta['title'],
            // Without the emoji some titles start with.
            'heading' => trim(preg_replace('/^[^\p{L}\p{N}]+/u', '', $meta['title'])),
            'group' => collect(config('docs.sidebar'))->search(fn (array $slugs) => in_array($slug, $slugs, true)),
            'html' => (string) $result,
            'toc' => $result->getDocument()->data->get('toc'),
            'previous' => $link($slugs[$index - 1] ?? null),
            'next' => $link($slugs[$index + 1] ?? null),
            'edit' => config('docs.edit_url').$slug.'.md',
        ];
    }

    private function file(string $slug): string
    {
        return $this->directory().'/'.$slug.'.md';
    }

    /**
     * @return array{title: string, label: string}
     */
    private function meta(string $slug): array
    {
        if (! isset($this->meta[$slug])) {
            $front = $this->split($slug)[0];
            $title = $front['title'] ?? Str::headline($slug);

            $this->meta[$slug] = ['title' => $title, 'label' => $front['sidebar_label'] ?? $title];
        }

        return $this->meta[$slug];
    }

    private function body(string $slug): string
    {
        return $this->split($slug)[1];
    }

    /**
     * The front matter (key: value lines, with optional quotes) and the markdown of a file.
     *
     * @return array{0: array<string, string>, 1: string}
     */
    private function split(string $slug): array
    {
        $source = str_replace("\r\n", "\n", (string) file_get_contents($this->file($slug)));

        if (! preg_match('/\A---\n(.*?)\n---\n/s', $source, $match)) {
            return [[], $source];
        }

        $front = [];
        foreach (explode("\n", $match[1]) as $line) {
            if (preg_match('/^([\w-]+):\s*(.*)$/', $line, $pair)) {
                $front[$pair[1]] = trim($pair[2], " \"'");
            }
        }

        return [$front, substr($source, strlen($match[0]))];
    }

    private function converter(): MarkdownConverter
    {
        $environment = new Environment(['html_input' => 'allow', 'allow_unsafe_links' => false]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addRenderer(FencedCode::class, new CodeBlockRenderer());
        $environment->addEventListener(DocumentParsedEvent::class, $this->prepare(...));

        return new MarkdownConverter($environment);
    }

    /**
     * Gives the headings their anchors, collects the table of contents and points the links between the pages to
     * the pages of the site.
     */
    private function prepare(DocumentParsedEvent $event): void
    {
        $document = $event->getDocument();
        $used = [];
        $toc = [];

        foreach ($document->iterator() as $node) {
            if ($node instanceof Heading) {
                $text = $this->text($node);
                $id = Str::slug($text) ?: 'section';
                $used[$id] = ($used[$id] ?? -1) + 1;
                $id .= $used[$id] > 0 ? '-'.$used[$id] : '';

                $node->data->set('attributes/id', $id);

                if ($node->getLevel() === 2 || $node->getLevel() === 3) {
                    $toc[] = ['id' => $id, 'text' => $text, 'level' => $node->getLevel()];
                }
            }

            if ($node instanceof Link) {
                $this->link($node);
            }
        }

        $document->data->set('toc', $toc);
    }

    private function text(Node $node): string
    {
        $text = '';

        foreach ($node->iterator() as $child) {
            if ($child instanceof Text || $child instanceof Code) {
                $text .= $child->getLiteral();
            }
        }

        return trim($text);
    }

    private function link(Link $link): void
    {
        $url = $link->getUrl();

        // "pagelets", "pagelets.md" and "pagelets#errors": a page of the documentation.
        if (preg_match('/^([a-z_]+)(?:\.md)?(#.*)?$/', $url, $match) && in_array($match[1], $this->slugs(), true)) {
            $link->setUrl('/docs/'.$match[1].($match[2] ?? ''));

            return;
        }

        if (preg_match('#^https?://#', $url)) {
            $link->data->set('attributes/target', '_blank');
            $link->data->set('attributes/rel', 'noopener');
        }
    }
}
