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
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FormCaptcha;
use Contao\Input;
use Contao\Widget;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsContentElement('protectedRedirect', category: 'links', template: 'ce_protected_redirect')]
class ProtectedRedirectElementController extends AbstractContentElementController
{
    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TranslatorInterface $translator,
        private readonly InsertTagParser $insertTagParser,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            return $this->getBackendWildcard($template, $model);
        }

        $formId = 'ce_protectedRedirect_'.$model->id;
        $captchaWidget = $this->getCaptchaWidget($model);

        // Validate the form
        if ($request->request->get('FORM_SUBMIT') === $formId && $this->validateForm($template, $model, $captchaWidget)) {
            $this->doRedirect($model);
        }

        // Default template variables
        $template->submit = $this->translator->trans('MSC.protectedRedirectUnlock', [], 'contao_default');
        $template->protectedRedirectPasswordLabel = $this->translator->trans('MSC.protectedRedirectPassword', [], 'contao_default');
        $template->action = $request->getUri();
        $template->formId = $formId;
        $template->captcha = null !== $captchaWidget ? $captchaWidget->parse() : '';
        $template->id = $model->id;
        $template->requestToken = $this->csrfTokenManager->getDefaultTokenValue();

        return $template->getResponse();
    }

    protected function getBackendWildcard(FragmentTemplate $template, ContentModel $model): Response
    {
        // The wildcard is output as plain text (it is escaped in the Twig template)
        $wildcardTemplate = new BackendTemplate('be_wildcard');
        $wildcardTemplate->title = $template->headline;
        $wildcardTemplate->wildcard = '### '.$model->protectedRedirectPassword.' > '.$model->url.' ###';

        return new Response($wildcardTemplate->parse());
    }

    protected function validateForm(FragmentTemplate $template, ContentModel $model, Widget|null $widget = null): bool
    {
        // Input::post() applies the same input encoding as the back end form did
        // when the password was saved (encoded in Contao 5, raw in Contao 6)
        if (!hash_equals((string) $model->protectedRedirectPassword, (string) Input::post('redirectPassword'))) {
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

    private function getCaptchaWidget(ContentModel $model): FormCaptcha|null
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
        if (str_starts_with((string) $model->url, 'mailto:')) {
            throw new RedirectResponseException($model->url, 303);
        }

        // Controller::redirect() makes relative URLs absolute and decodes "&amp;"
        Controller::redirect($this->insertTagParser->replaceInline((string) $model->url));
    }
}
