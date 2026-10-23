<?php
$root=dirname(__DIR__);
$out=$argv[1] ?? $root.'/plg_system_typographe-2.0.7.zip';
if (!is_file($root.'/dependencies/autoload.php')) throw new RuntimeException('Run composer install first.');
$zip=new ZipArchive;
if ($zip->open($out,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) throw new RuntimeException('Cannot create ZIP.');
foreach (['services','src','dependencies','language','media'] as $folder) {
 foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$folder",FilesystemIterator::SKIP_DOTS)) as $file) {
  if (!$file->isFile()) continue;
  $rel=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
  if (preg_match('~/(en-GB|fr-FR)\.plg_system_typographe~',$rel)) continue;
  // Development-only dictionary generators are not used at runtime.
  if (str_starts_with($rel, 'dependencies/org_heigl/hyphenator/src/tools/')) continue;
  if (str_starts_with($rel, 'dependencies/') && str_ends_with($rel, '.php')) {
   $content=file_get_contents($file->getPathname());
   // Preserve original copyrights and licenses; identify MIT explicitly for scanners.
   $notice="\n/** Bundled third-party code. @license MIT License; see the package LICENSE file. */\n";
   if ($rel==='dependencies/autoload.php') $notice.="defined('_JEXEC') or die;\n";
   $content=preg_replace('/^<\?php/', '<?php'.$notice, $content, 1);
   $zip->addFromString($rel,$content);
   continue;
  }
  $zip->addFile($file->getPathname(),$rel);
 }
}
foreach (['typographe.xml','script.php','LICENSE'] as $file) $zip->addFile("$root/$file",$file);
$zip->close();
echo $out."\n";



