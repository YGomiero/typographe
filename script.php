<?php
/**
 * @copyright Copyright (C) 2026 Yann Gomiero
 * @license GNU General Public License version 3 or later; see LICENSE
 */
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;
final class PlgSystemTypographeInstallerScript
{
    public function preflight(string $type, $parent): bool
    {
        if ($type === 'uninstall') return true;
        if (version_compare(PHP_VERSION, '8.3', '<') || Version::MAJOR_VERSION < 6 || !extension_loaded('dom') || !extension_loaded('mbstring')) {
            Factory::getApplication()->enqueueMessage('Typographe requires Joomla 6+, PHP 8.3+, DOM and mbstring. / Typographe nécessite Joomla 6+, PHP 8.3+, DOM et mbstring.', 'error');
            return false;
        }
        return true;
    }

    public function postflight(string $type, $parent): void
    {
        if (!in_array($type, ['install','update'], true)) return;
        // Only remove the obsolete files owned by Typographe 1.x.
        $path = JPATH_PLUGINS . '/system/typographe';
        if (is_file($path . '/typographe.php')) \Joomla\Filesystem\File::delete($path . '/typographe.php');
        if (is_dir($path . '/vendor')) \Joomla\Filesystem\Folder::delete($path . '/vendor');
    }
}
