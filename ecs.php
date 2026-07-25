<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withConfiguredRule(HeaderCommentFixer::class, [
        'header' => "This file is part of Protected Redirect Element for Contao Open Source CMS.\n\n(c) bwein.net\n\n@license MIT",
    ]);
