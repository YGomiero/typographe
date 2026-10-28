<?php
defined('_JEXEC') || define('_JEXEC', 1);
require dirname(__DIR__) . '/src/Service/Typography.php';
use Yann\Plugin\System\Typographe\Service\Typography;
$count=0;
function check(bool $condition, string $name): void { global $count; if (!$condition) throw new RuntimeException($name); echo 'PASS '.$name."\n"; $count++; }
$s=new Typography;
$sample='<p class="x" data-label="test...">Il dit "bonjour" : c\'est bien...</p>';
$fixed=$s->fix($sample);
check(str_contains($fixed,'…') && str_contains($fixed,'c’est') && str_contains($fixed,'«'), 'French punctuation');
check(str_contains($fixed,'<p class="x" data-label="test...">'), 'HTML attributes unchanged');
check($s->fix($fixed)===$fixed,'Idempotent French');
foreach (['pre','code','script','style','textarea','svg','math','iframe'] as $tag) {
    $html="<$tag>test... &amp; \"raw\"</$tag>";
    check($s->fix($html)===$html,"Protected $tag");
}
foreach (['<div data-typographe-ignore><p>test...</p></div>','<span class="notypo">test...</span>','<!-- test... -->','{loadmoduleid 12}','https://example.test/a...b?x=1&y=2','test.name@example.test'] as $html) check($s->fix($html)===$html,'Protected fragment '.$html);
check($s->fix('<div data-typographe-ignore><b>test...</b> end...</div> normal...')==='<div data-typographe-ignore><b>test...</b> end...</div> normal…','Nested exclusion');
check($s->fix('<p>test...</p><hr id="system-readmore"><p>suite...</p>')==='<p>test…</p><hr id="system-readmore"><p>suite…</p>','Readmore preservation');
$en=$s->fix('"Hello"! It\'s fine...', 'en-GB');
check(str_contains($en,'“Hello”') && !str_contains($en,"\u{00A0}!") && str_contains($en,'It’s'), 'English language');
check($s->fix($en,'en-GB')===$en,'Idempotent English');
check($s->fix('')==='','Empty');
check($s->fix("\xFF") === "\xFF",'Invalid UTF8 preserved');
check((new Typography(array_fill_keys(array_keys(Typography::RULES),0)))->fix($sample)===$sample,'All rules disabled');
check($s->fix('<a href="https://x.test?a=1&amp;b=2" title="a > b...">test...</a>')==='<a href="https://x.test?a=1&amp;b=2" title="a > b...">test…</a>','Quoted tag greater-than');
foreach (['en-GB','fr-FR'] as $lang) {
  $ini=parse_ini_file(dirname(__DIR__)."/language/$lang/plg_system_typographe.ini");
  check(is_array($ini) && count($ini)===53, 'Translations '.$lang);
}
check($s->fixPlain('Arts & culture...')==='Arts & culture…','Plain title ampersand');
check($s->fix('<pre>test...')==='<pre>test...','Unclosed protected tag');
echo "$count tests passed\n";



$decomposed="Cafe\u{0301}";
check($s->fix($decomposed)==='Café','Unicode NFC composition');
check((new Typography(['unicodenormalization'=>0]))->fix($decomposed)===$decomposed,'Unicode option disabled');
check($s->fix('<code>'.$decomposed.'</code>')==='<code>'.$decomposed.'</code>','Unicode protected code');
check($s->fix('<p title="'.$decomposed.'">'.$decomposed.'</p>')==='<p title="'.$decomposed.'">Café</p>','Unicode preserves attributes');
check($s->fix('Bonjour ! Test : oui ; non ?', 'fr-FR')==="Bonjour\u{202f}! Test\u{00a0}: oui\u{202f}; non\u{202f}?",'French punctuation spacing');
check($s->fix('Hello ! Test : yes ; no ?', 'en-GB')==='Hello! Test: yes; no?','English punctuation spacing');
check($s->fix('Bonjour ! Test : oui ; non ?', 'fr-CA')==='Bonjour! Test: oui; non?','Canadian French spacing');
check((new Typography(['frenchnobreakspace'=>0]))->fix('Hello !','en-GB')==='Hello !','Historical disabled spacing setting retained');
check((new Typography(['setlocale'=>'en_US']))->fix('Hello !')==='Hello!','Configured fallback locale');
check($s->fix($s->fix($decomposed.' !','fr-FR'),'fr-FR')===$s->fix($decomposed.' !','fr-FR'),'New rules idempotent');
echo "$count total tests passed\n";
