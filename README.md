RValinTranslationBundle
=============

RValinTranslationBundle provide an easy way to update translation in symfony (2 -> 4).  
It allow user to update translation directly from the page.

### Warning

This bundle update your translations files.  
For this reason, I recommend to use it only in dev or to use [lexikTranslationBundle](https://github.com/lexik/LexikTranslationBundle).

Installation
------------

1) Use [Composer](https://getcomposer.org/) to download the library
```
composer require rvalin/translation-bundle
```

2) Then add the RValinTranslationBundle to your application kernel:

```php
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new RValin\MigrationBundle\RValinTranslationBundle(),
        // ...
    );
}
```

3) Then update your config (optional)

Default config:
```
r_valin_translation:
    dumpers_config: []
    updaters: ['file']
    role: 'ROLE_UPDATE_TRANSLATION'
    allowed_domains: []
    allowed_bundles: []
```

This config will use the default config of Symfony to generate translations files.  
If you want to customize the dumb set the config you want with dumpers_config.

Exemple for yml files.

```
r_valin_translation:
    updaters: ['lexik_translation'] 
```

If you don't use Symfony default translator
-----

### LexikTranslationBundle

If you use [lexikTranslationBundle](https://github.com/lexik/LexikTranslationBundle) you just need to update the configuration to make it work :

```
r_valin_translation:
    dumpers_config: 
        yml:
            as_tree: true
```

### Other Translator

