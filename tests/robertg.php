<?php
defined('_JEXEC') || define('_JEXEC', 1);
require dirname(__DIR__) . '/src/Service/Typography.php';
use Yann\Plugin\System\Typographe\Service\Typography;
$samples = [
    'Un petit exercice',
    '<p>S’écartant de la route, Alban s’est installé à l’ombre des chênes, sous la roche de Tra Castel, au milieu des buis aux petites feuilles si joliment vernissées, bien calé sur un épais tapis de mousse.</p>',
    '<p>En vue de (re)photographier des stylos à ajouter aux articles existants et à ceux à venir, je me suis lancé dans un petit exercice.</p><p>Il y a quelques années, j’avais fabriqué un petit support dont</p>',
];
$checks=0;
$check=static function(bool $ok, string $name) use (&$checks): void {
    if (!$ok) throw new RuntimeException($name);
    $checks++;
};
foreach ($samples as $index=>$original) {
    $service=new Typography(['hyphen'=>1]);
    $corrected=$index ? $service->fix($original) : $service->fixPlain($original);
    $check($corrected!==$original, 'Hyphenation enabled produces a suggestion');
    $check(str_replace("\u{00ad}",'',$corrected)===$original, 'Only discretionary hyphens added');
    $check($service->fix($corrected)===$corrected, 'Hyphenation remains idempotent');
    $check((new Typography(['hyphen'=>0]))->fix($original)===$original, 'Hyphenation disabled preserves each example');
    $check((new Typography)->fix($original)===$original, 'Hyphenation is disabled by default');
}
$original='<p>Un exercice...</p>{loadmoduleid 12}<hr id="system-readmore"><p data-label="exercice">Autre exercice...</p><pre>exercice...</pre>';
$corrected=(new Typography(['hyphen'=>1]))->fix($original);
$check(str_contains($corrected,'{loadmoduleid 12}'),'Module insertion preserved');
$check(str_contains($corrected,'<hr id="system-readmore">'),'Readmore preserved');
$check(str_contains($corrected,'data-label="exercice"'),'Attributes preserved');
$check(str_contains($corrected,'<pre>exercice...</pre>'),'Protected source preserved');
echo "$checks RobertG engine regression checks passed\n";
