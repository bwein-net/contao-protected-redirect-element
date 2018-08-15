<?php

/*
 * This file is part of Protected Redirect Element for Contao Open Source CMS.
 *
 * (c) bwein.net
 *
 * @license MIT
 */

namespace Bwein\ProtectedRedirectElement\ContaoManager;

use Bwein\ProtectedRedirectElement\BweinProtectedRedirectElementBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(BweinProtectedRedirectElementBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
