<?php
defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Database\DatabaseInterface;
use Yann\Plugin\System\Typographe\Extension\Typographe;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, static function (Container $container) {
            $app = Factory::getApplication();
            $config = (array) PluginHelper::getPlugin('system', 'typographe');
            // Joomla boots system providers on every request. Public GET requests
            // receive an inert shell: neither our editing class nor JoliTypo loads.
            if ($app->isClient('api') || ($app->isClient('site') && $app->getInput()->getMethod() !== 'POST')) {
                return new class($config) extends CMSPlugin implements SubscriberInterface {
                    protected $allowLegacyListeners = false;
                    public static function getSubscribedEvents(): array { return []; }
                };
            }
            $plugin = new Typographe($config);
            $plugin->setApplication($app);
            $plugin->setDatabase($container->get(DatabaseInterface::class));
            return $plugin;
        });
    }
};
