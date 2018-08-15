<?php

$GLOBALS['TL_DCA']['tl_content']['palettes']['protectedRedirect'] = '
    {type_legend},type,headline;
    {protectedRedirect_legend},url,protectedRedirectPassword,protectedRedirectDisableCaptcha;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID;
    {invisible_legend:hide},invisible,start,stop';


$GLOBALS['TL_DCA']['tl_content']['fields']['protectedRedirectPassword'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_content']['protectedRedirectPassword'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => array(
        'tl_class' => 'w50 clr',
        'mandatory' => true,
        'maxlength' => 255,
    ),
    'sql' => "varchar(255) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_content']['fields']['protectedRedirectDisableCaptcha'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_content']['protectedRedirectDisableCaptcha'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => array('tl_class' => 'w50 m12'),
    'sql' => "char(1) NOT NULL default ''",
);
