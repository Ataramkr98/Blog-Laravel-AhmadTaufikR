<?php

namespace App\Support;

use App\Models\Glossary;
use DOMDocument;
use DOMNode;
use DOMText;
use Illuminate\Support\Collection;

class GlossaryHighlighter
{
    /**
     * @param  Collection<int, Glossary>  $glossaries
     */
    public function highlight(string $html, Collection $glossaries): string
    {
        if ($glossaries->isEmpty() || trim($html) === '') {
            return $html;
        }

        $terms = $glossaries
            ->sortByDesc(fn ($glossary) => mb_strlen($glossary->term))
            ->mapWithKeys(fn ($glossary) => [mb_strtolower($glossary->term) => $glossary->id]);

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="article-content-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('article-content-root');
        if (! $root) {
            return $html;
        }

        $textNodes = [];
        $this->collectTextNodes($root, $textNodes);

        foreach ($textNodes as $textNode) {
            $this->replaceTerms($document, $textNode, $terms);
        }

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return $result;
    }

    /**
     * @param  list<DOMText>  $nodes
     */
    private function collectTextNodes(DOMNode $node, array &$nodes): void
    {
        if ($node instanceof DOMText) {
            if (trim($node->nodeValue) !== '') {
                $nodes[] = $node;
            }

            return;
        }

        if (in_array($node->nodeName, ['a', 'button', 'code', 'pre', 'script', 'style'], true)) {
            return;
        }

        foreach ($node->childNodes as $child) {
            $this->collectTextNodes($child, $nodes);
        }
    }

    /**
     * @param  Collection<string, int>  $terms
     */
    private function replaceTerms(DOMDocument $document, DOMText $textNode, Collection $terms): void
    {
        $pattern = '/('.$terms->keys()
            ->map(fn (string $term) => preg_quote($term, '/'))
            ->implode('|').')/iu';
        $parts = preg_split($pattern, $textNode->nodeValue, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (! is_array($parts) || count($parts) === 1) {
            return;
        }

        $fragment = $document->createDocumentFragment();

        foreach ($parts as $part) {
            $glossaryId = $terms->get(mb_strtolower($part));

            if ($glossaryId) {
                $button = $document->createElement('button');
                $button->setAttribute('type', 'button');
                $button->setAttribute('data-glossary-term', '');
                $button->setAttribute('data-glossary-id', (string) $glossaryId);
                $button->setAttribute('class', 'rounded bg-emerald-50 px-1 font-semibold text-emerald-800 underline decoration-dotted underline-offset-4');
                $button->appendChild($document->createTextNode($part));
                $fragment->appendChild($button);
            } else {
                $fragment->appendChild($document->createTextNode($part));
            }
        }

        $textNode->parentNode?->replaceChild($fragment, $textNode);
    }
}
