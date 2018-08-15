<?php

/*
 * This file is part of Protected Redirect Element for Contao Open Source CMS.
 *
 * (c) bwein.net
 *
 * @license MIT
 */

namespace Bwein\ProtectedRedirectElement\Element;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\Controller;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\Environment;
use Contao\FormCaptcha;
use Contao\Input;
use Contao\StringUtil;
use Contao\Widget;

/**
 * Class ProtectedRedirectElement.
 */
class ProtectedRedirectElement extends ContentElement
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'ce_protected_redirect';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE === 'BE') {
            /** @var BackendTemplate|object $objTemplate */
            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### '.$this->protectedRedirectPassword.' > '.$this->url.' ###';
            $objTemplate->title = $this->headline;

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    protected function compile()
    {
        $strFormId = 'ce_protectedRedirect_'.$this->id;
        $captchaWidget = $this->getCaptchaWidget();

        // Validate the form
        if (Input::post('FORM_SUBMIT') === $strFormId) {
            $varSubmitted = $this->validateForm($captchaWidget);

            if (false !== $varSubmitted) {
                $this->doRedirect();
            }
        }

        // Default template variables
        $this->Template->submit = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['protectedRedirectUnlock']);
        $this->Template->protectedRedirectPasswordLabel = $GLOBALS['TL_LANG']['MSC']['protectedRedirectPassword'];
        $this->Template->action = Environment::get('indexFreeRequest');
        $this->Template->formId = $strFormId;
        $this->Template->captcha = null !== $captchaWidget ? $captchaWidget->parse() : '';
        $this->Template->id = $this->id;
    }

    /**
     * @param Widget|null $objWidget
     *
     * @return bool
     */
    protected function validateForm(Widget $objWidget = null)
    {
        // Validate the password
        if ($this->protectedRedirectPassword !== Input::post('redirectPassword')) {
            $this->Template->mclass = 'error';
            $this->Template->message = $GLOBALS['TL_LANG']['ERR']['protectedRedirectInvalidPassword'];

            return false;
        }

        // Validate the captcha
        if (null !== $objWidget) {
            $objWidget->validate();

            if ($objWidget->hasErrors()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return FormCaptcha|null
     */
    private function getCaptchaWidget()
    {
        if ($this->protectedRedirectDisableCaptcha) {
            return null;
        }

        $arrField = [
            'name' => 'unlock',
            'label' => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
            'inputType' => 'captcha',
            'eval' => ['mandatory' => true],
        ];

        /* @var Widget $objWidget */
        return new FormCaptcha(FormCaptcha::getAttributesFromDca($arrField, $arrField['name']));
    }

    private function doRedirect()
    {
        if ('mailto:' === substr($this->url, 0, 7)) {
            throw new RedirectResponseException($this->url, 303);
        }

        $url = Controller::replaceInsertTags($this->url);
        Controller::redirect($url);
    }
}
