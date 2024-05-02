<?php

namespace Aznoqmous\ContaoMultilangBundle\EventListener;

use Aznoqmous\ContaoMultilangBundle\Event\InitializeMultilangConfigEvent;
use Aznoqmous\ContaoMultilangBundle\Multilang\BackendMultilang;
use Aznoqmous\ContaoMultilangBundle\Multilang\DcaFileMultilang;
use Aznoqmous\ContaoMultilangBundle\Multilang\Multilang;
use Contao\Controller;
use Contao\Input;
use Contao\Message;
use PhpParser\Node\Expr\AssignOp\Mul;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class InitializeSystemListener
{
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function __invoke(): void
    {

        $this->dispatcher->dispatch(new InitializeMultilangConfigEvent(), "multilang.initialize_config");

        if(!Multilang::getActiveLanguage()){
            Controller::loadLanguageFile("default");
            Message::addInfo($GLOBALS['TL_LANG']['multilang']['noLanguagesSet']);
            return;
        }

        if(TL_MODE == "BE"){

            if(!in_array(Input::get('act'), explode(',', "editAll,copy,paste,delete"))){
                BackendMultilang::handleCurrentBackendLanguage();
            }

            /* Add multilang_lang field on editAll action */
            if($_POST['all_fields'] && \Aznoqmous\ContaoMultilangBundle\Multilang\BackendMultilang::isActiveTableMultilang()) {
                $_POST['all_fields'][] = 'multilang_lang';
            }
        }
        else {
            /* Redirect /fr to /fr/ */
            $pathInfo = Request::createFromGlobals()->getPathInfo();
            $pathInfo = preg_replace("/^\//", "", $pathInfo);
            if(in_array($pathInfo, Multilang::getLanguageKeys())){
                Multilang::redirect("/$pathInfo/");
            }
        }

        DcaFileMultilang::loadConfig();
    }
}
