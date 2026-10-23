<?php
namespace Yann\Plugin\System\Typographe\Service;

use JoliTypo\Fixer;

/** Changes text only: the original HTML tags and attributes are never serialized. */
final class Typography
{
    public const RULES = ['unicodenormalization' => 'UnicodeNormalization', 'smartquotes' => 'SmartQuotes', 'curlyquote' => 'CurlyQuote',
        'ellipsis' => 'Ellipsis', 'dimension' => 'Dimension', 'unit' => 'Unit',
        'dash' => 'Dash', 'frenchnobreakspace' => 'SpaceBeforePunctuation',
        'nospacebeforecomma' => 'NoSpaceBeforeComma', 'hyphen' => 'Hyphen', 'trademark' => 'Trademark'];

    public function __construct(private array $options = []) {}

    public function fixPlain(string $text, string $language = '*'): string
    {
        return html_entity_decode($this->fix(htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'), $language), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function fix(string $html, string $language = '*'): string
    {
        if ($html === '' || !mb_check_encoding($html, 'UTF-8')) {
            return $html;
        }
        $locale = str_replace('-', '_', $language === '*' || $language === ''
            ? ($this->options['setlocale'] ?? 'fr_FR') : $language);
        $rules = [];
        foreach (self::RULES as $key => $rule) {
            // Discretionary hyphens are opt-in for new installations.
            // Keep the historical spacing option key so upgrades preserve user choices.
            if ($this->options[$key] ?? ($key === 'hyphen' ? 0 : 1)) {
                $rules[] = $rule;
            }
        }
        if (!$rules) {
            return $html;
        }
        require_once dirname(__DIR__, 2) . '/dependencies/autoload.php';
        $fixer = new Fixer($rules);
        $fixer->setLocale($locale);
        // Match raw-text/protected elements as a whole, before splitting HTML tokens.
        $pattern = '~(<!--.*?-->|<(pre|code|script|style|textarea|svg|math|iframe)\b[^>]*>.*?</\2\s*>|<![^>]*>|</?[a-zA-Z][a-zA-Z0-9:-]*\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>)~si';
        if (preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE) === false) {
            throw new \RuntimeException('Unable to tokenize content.');
        }
        $output = ''; $offset = 0; $stack = [];
        foreach ($matches[0] as [$tag, $position]) {
            $text = substr($html, $offset, $position - $offset);
            $output .= $this->protected($stack) ? $text : $this->text($text, $fixer);
            $output .= $tag;
            $offset = $position + strlen($tag);
            if (preg_match('~^</([a-z0-9:-]+)~i', $tag, $closing)) {
                while ($stack) {
                    $last = array_pop($stack);
                    if ($last['tag'] === strtolower($closing[1])) { break; }
                }
            } elseif (preg_match('~^<([a-z0-9:-]+)\b~i', $tag, $opening)
                && !preg_match('~</|/\s*>$~', $tag)
                && !in_array(strtolower($opening[1]), ['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr'], true)) {
                $stack[] = ['tag' => strtolower($opening[1]), 'protected' => in_array(strtolower($opening[1]), ['pre','code','script','style','textarea','svg','math','iframe'], true)
                    || (bool) preg_match('~\bdata-typographe-ignore(?:\s|=|>)|\bclass\s*=\s*["\x27][^"\x27]*\bnotypo\b~i', $tag)];
            }
        }
        $tail = substr($html, $offset);
        return $output . ($this->protected($stack) ? $tail : $this->text($tail, $fixer));
    }

    private function protected(array $stack): bool
    {
        return in_array(true, array_column($stack, 'protected'), true);
    }

    private function text(string $text, Fixer $fixer): string
    {
        // Preserve Joomla shortcodes, URLs and e-mail addresses byte for byte.
        $parts = preg_split('~(\{[^{}]*\}|(?:https?://|www\.)[^\s<>]+|[\w.+-]+@[\w.-]+\.[a-z]{2,})~iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) { return $text; }
        foreach ($parts as $index => &$source) {
                if ($index % 2) { continue; }
                $decoded = html_entity_decode($source, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $fixed = $fixer->fixString($decoded);
                $source = $fixed === $decoded ? $source : htmlspecialchars($fixed, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        }
        return implode('', $parts);
    }
}
