<?php

namespace Aznoqmous\ContaoMultilangBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Aznoqmous\ContaoMultilangBundle\ContaoMultilangBundle;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Contao\NewsBundle\ContaoNewsBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

class Plugin implements BundlePluginInterface, ConfigPluginInterface, RoutingPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        $classes = [ContaoCoreBundle::class];

        if(class_exists(ContaoNewsBundle::class)) $classes[] = ContaoNewsBundle::class;

        return [
            BundleConfig::create(ContaoMultilangBundle::class)
                ->setLoadAfter($classes)
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig)
    {
        $loader->load('@ContaoMultilangBundle/Resources/config/parameters.yml');
        $loader->load('@ContaoMultilangBundle/Resources/config/services.yml');
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        $file = "@ContaoMultilangBundle/Resources/config/routing.yml";
        return $resolver->resolve($file)->load($file);
    }
}
