<?php

declare(strict_types=1);

/*
 * This file is part of Protected Redirect Element for Contao Open Source CMS.
 *
 * (c) bwein.net
 *
 * @license MIT
 */

namespace Bwein\ProtectedRedirectElement\Controller\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FormCaptcha;
use Contao\StringUtil;
use Contao\Template;
use Contao\Widget;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @ContentElement("protectedRedirect", category="links", template="ce_protected_redirect")
 */
class ProtectedRedirectElementController extends AbstractContentElementController
{
    private $scopeMatcher;
    private $translator;

    public function __construct(ScopeMatcher $scopeMatcher, TranslatorInterface $translator)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->translator = $translator;
    }

    /**
     * @param Template|FragmentTemplate $template
     */
    protected function getResponse($template, ContentModel $model, Request $request): Response
    {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            return $this->getBackendWildcard($template, $model);
        }

        $formId = 'ce_protectedRedirect_'.$model->id;
        $captchaWidget = $this->getCaptchaWidget($model);

        // Validate the form
        if ($request->get('FORM_SUBMIT') === $formId) {
            $varSubmitted = $this->validateForm($template, $model, $request, $captchaWidget);

            if (false !== $varSubmitted) {
                $this->doRedirect($model);
            }
        }

        // Default template variables
        $template->submit = StringUtil::specialchars($this->translator->trans('MSC.protectedRedirectUnlock', [], 'contao_default'));
        $template->protectedRedirectPasswordLabel = $this->translator->trans('MSC.protectedRedirectPassword', [], 'contao_default');
        $template->action = $request->getUri();
        $template->formId = $formId;
        $template->captcha = null !== $captchaWidget ? $captchaWidget->parse() : '';
        $template->id = $model->id;

        return $template->getResponse();
    }

    /**
     * @param Template|FragmentTemplate $template
     */
    protected function getBackendWildcard($template, ContentModel $model): Response
    {
        $wilcardTemplate = new BackendTemplate('be_wildcard');
        $wilcardTemplate->title = $template->headline;
        $wilcardTemplate->wildcard .= '### '.$model->protectedRedirectPassword.' > <a href="'.$model->url.'" target="_blank">'.$model->url.'</a> ###';

        return new Response($wilcardTemplate->parse());
    }

    /**
     * @param Template|FragmentTemplate $template
     */
    protected function validateForm($template, ContentModel $model, Request $request, Widget $widget = null): bool
    {
        // Validate the password
        if ($model->protectedRedirectPassword !== $request->get('redirectPassword')) {
            $template->mclass = 'error';
            $template->message = $this->translator->trans('ERR.protectedRedirectInvalidPassword', [], 'contao_default');

            return false;
        }

        // Validate the captcha
        if (null !== $widget) {
            $widget->validate();

            if ($widget->hasErrors()) {
                return false;
            }
        }

        return true;
    }

    private function getCaptchaWidget(ContentModel $model): ?FormCaptcha
    {
        if ((bool) $model->protectedRedirectDisableCaptcha) {
            return null;
        }

        $field = [
            'name' => 'unlock',
            'label' => $this->translator->trans('MSC.securityQuestion', [], 'contao_default'),
            'inputType' => 'captcha',
            'eval' => ['mandatory' => true],
        ];

        return new FormCaptcha(FormCaptcha::getAttributesFromDca($field, $field['name']));
    }

    private function doRedirect(ContentModel $model): void
    {
        if (0 === strpos($model->url, 'mailto:')) {
            throw new RedirectResponseException($model->url, 303);
        }

        $url = Controller::replaceInsertTags($model->url);
        Controller::redirect($url);
    }
}
