<?php
$root=dirname(__DIR__);
$out=$argv[1] ?? $root.'/plg_system_typographe-2.0.6.zip';
if (!is_file($root.'/dependencies/autoload.php')) throw new RuntimeException('Run composer install first.');
$zip=new ZipArchive;
if ($zip->open($out,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) throw new RuntimeException('Cannot create ZIP.');
foreach (['services','src','dependencies','language','media'] as $folder) {
 foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$folder",FilesystemIterator::SKIP_DOTS)) as $file) {
  if (!$file->isFile()) continue;
  $rel=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
  if (preg_match('~/(en-GB|fr-FR)\.plg_system_typographe~',$rel)) continue;
  $zip->addFile($file->getPathname(),$rel);
 }
}
foreach (['typographe.xml','script.php','LICENSE'] as $file) $zip->addFile("$root/$file",$file);
$zip->close();
echo $out."\n";

