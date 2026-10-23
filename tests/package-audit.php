<?php
// php tests/package-audit.php /path/to/installable.zip
// Independent package evidence for JED review; does not suppress checker rules.
$root = dirname(__DIR__);
$zip = new ZipArchive();
if ($zip->open($argv[1] ?? '') !== true) throw new RuntimeException('Cannot open package');
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void {
    if (!$ok) throw new RuntimeException($message);
    $checks++;
};
$manifest = simplexml_load_string($zip->getFromName('typographe.xml'));
$check(str_contains((string) $manifest->license, 'GNU General Public License'), 'GPL license declaration missing');
$check($zip->getFromName('LICENSE') === file_get_contents($root . '/LICENSE'), 'First-party license changed');
$packages = json_decode(file_get_contents($root . '/composer.lock'), true)['packages'];
foreach ($packages as $package) {
    $check($package['license'] === ['MIT'], 'Review packaging notice for changed dependency license');
    $path = 'dependencies/' . $package['name'] . '/LICENSE';
    $check($zip->getFromName($path) === file_get_contents($root . '/' . $path), 'Missing or changed license: ' . $path);
}
$check($zip->getFromName('dependencies/composer/LICENSE') === file_get_contents($root . '/dependencies/composer/LICENSE'), 'Composer license changed');
$dependencyFiles = 0;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $path = $zip->getNameIndex($i);
    if (!str_starts_with($path, 'dependencies/') || str_ends_with($path, '/')) continue;
    $check(!str_starts_with($path, 'dependencies/org_heigl/hyphenator/src/tools/'), 'Development tool included');
    $original = file_get_contents($root . '/' . $path);
    $expected = $original;
    if (str_ends_with($path, '.php')) {
        $notice = "\n/** Bundled third-party code. @license MIT License; see the package LICENSE file. */\n";
        if ($path === 'dependencies/autoload.php') $notice .= "defined('_JEXEC') or die;\n";
        $expected = preg_replace('/^<\?php/', '<?php' . $notice, $original, 1);
    }
    $check($zip->getFromName($path) === $expected, 'Unexpected dependency modification: ' . $path);
    $dependencyFiles++;
}
$keys = [];
foreach (['en-GB', 'fr-FR'] as $locale) {
    $ini = $zip->getFromName('language/' . $locale . '/plg_system_typographe.ini');
    $strings = parse_ini_string($ini);
    $check(is_array($strings), 'Invalid INI: ' . $locale);
    $check(!empty($strings['PLG_SYSTEM_TYPOGRAPHE_ERROR']), 'Missing error translation: ' . $locale);
    $keys[$locale] = array_keys($strings);
    sort($keys[$locale]);
}
$check($keys['en-GB'] === $keys['fr-FR'], 'Translation keys differ');
echo json_encode(['checks' => $checks, 'dependency_files_compared' => $dependencyFiles,
    'translation_keys_per_language' => count($keys['en-GB']), 'result' => 'PASS'], JSON_PRETTY_PRINT) . PHP_EOL;
