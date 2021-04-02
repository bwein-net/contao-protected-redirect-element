<?php

declare(strict_types=1);

/*
 * This file is part of Protected Redirect Element for Contao Open Source CMS.
 *
 * (c) bwein.net
 *
 * @license MIT
 */

$GLOBALS['TL_DCA']['tl_content']['palettes']['protectedRedirect'] = '
    {type_legend},type,headline;
    {protectedRedirect_legend},url,protectedRedirectPassword,protectedRedirectDisableCaptcha;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID;
    {invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['protectedRedirectPassword'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => [
        'tl_class' => 'w50 clr',
        'mandatory' => true,
        'maxlength' => 255,
    ],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['protectedRedirectDisableCaptcha'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''",
];
