# contao-multilang-bundle

## Features
- Allow any ``DC_Table`` translation
- Allow any ``DC_File`` translation
- Backend language switcher
- Frontend language switcher

## Configuration
To define a translatable table, use the ``InitializeMultilangConfigListener`` event inside your `listener.yml`:
````yaml
Aznoqmous\ContaoMultilangBundle\EventListener\InitializeMultilangConfigListener:
    tags:
      - { name: kernel.event_listener, event: multilang.initialize_config}
````
````php

class InitializeMultilangConfigListener
{
    public function __invoke(InitializeMultilangConfigEvent $multilang)
    {
        $multilang->setTable('tl_page');
        
        /* See Aznoqmous\ContaoMultilangBundle\EventListener\InitializeMultilangConfigListener for more examples */
    }
}
````

### Defining translatable tables 
Define your translatable tables like so, then run ``contao:migrate`` to add multilang required fields to the table
````php
/* DC_Table */
$multilang->setTable('tl_page');

/* DC_File */
$multilang->setFile('tl_settings');
````
Once you migrated your database for a specific table, your contao list view will be empty because you need to set a language for existing entities.
Go to ``/contao?do=multilang_settings`` to do so

### Defining translatable children
On creating a new translation, translations will be automatically generated for given children tables :
````php
/* DC_Table */
$multilang->setTable('tl_page')
    ->addChildrenTable('tl_page')
    ->addChildrenTable('tl_article')
;
// On creating a tl_page translation, translations will automatically be generated
// for children tl_pages and tl_articles recursively
````

## Language switcher customization
For tables with details page (eg: `tl_news`), ``FrontendMultilangRedirectListener`` can be used
to provide a custom route resolution. See ``Aznoqmous\ContaoMultilangBundle\EventListener\FrontendMultilangRedirectListener`` for reference
````yaml
  Aznoqmous\ContaoMultilangBundle\EventListener\FrontendMultilangRedirectListener:
    tags:
      - { name: kernel.event_listener, event: multilang.frontend_redirect}
````

## MultilangModelTrait

When in frontend `TL_MODE`, the ``MultilangModelTrait`` allows your models to automatically load the right content.

During ``__construct``, your model will try to find a variant fitting the current active language, and if found,
override all its data

All ``find`` calls will automatically query the correct language.