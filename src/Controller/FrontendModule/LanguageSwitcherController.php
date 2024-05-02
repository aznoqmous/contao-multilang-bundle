<?php

namespace Aznoqmous\ContaoMultilangBundle\Controller\FrontendModule;

use Aznoqmous\ContaoMultilangBundle\Event\FrontendMultilangRedirectEvent;
use Aznoqmous\ContaoMultilangBundle\Multilang\Multilang;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\Input;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;

/**
 * @FrontendModule(LanguageSwitcherController::TYPE, category="multilang", template="mod_language_switcher")
 */
class LanguageSwitcherController extends AbstractFrontendModuleController{

    public const TYPE = "language_switcher";

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $selectedLang = Input::post('lang_select');
        if($selectedLang && $selectedLang != Multilang::getActiveLanguageKey())
            $this->redirectToLanguage($selectedLang);
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        $template->languages = Multilang::getEnabledLanguages();
        $template->activeLanguage = Multilang::getActiveLanguage();
        $template->switcherLayout = $model->switcherLayout;
        return $template->getResponse();
    }

    protected function redirectToLanguage($langKey)
    {
        Controller::redirect($this->getLanguageUrl($langKey));
    }

    protected function getLanguageUrl($langKey){
        $targetLanguage = Multilang::getLanguageByKey($langKey);
        $event = new FrontendMultilangRedirectEvent($targetLanguage);
        $this->dispatcher->dispatch($event, "multilang.frontend_redirect");
        return $event->targetUrl;
    }

}
