# Happyr Loco Bundle

This bundle helps you to integrate with the Loco translation service. The key features of this bundle is: 

* Easy to download all translations from https://localise.biz
* Support for multiple projects
* Create new translation assets by the WebProfiler toolbar

## Install

Install the bundle with `composer require happyr/loco-bundle`

## Configure

If you have one Loco project per domain you may configure the bundle like this: 
``` yaml
# /app/config/config.yml
happyr_loco:
  locales: ['en','sv','fr','es']
  projects:
    messages:
      api_key: 'foobar' 
    navigation:
      api_key: 'bazbar' 

```


If you just doing one project and have tags for all your translation domains you may use this configuration:
``` yaml
happyr_loco:
  locales: ['en','sv','fr','es']
  dimensions: ['messages', 'navigation']
  projects:
    acme:
      api_key: 'foobar'  
    
```

You do also need to configure a development route. 
``` yaml
# /app/config/routing_dev.yml
_happyr_loco:
    resource: '@HappyrLocoBundle/Resources/config/routing_dev.yml'
    
```

# Credits

This bundle is both inspired by and is using some of the code from [Jokicode.com](http://jolicode.com/blog/translation-workflow-with-symfony2)
and from Cliff Odijk's (@cmodijk) [LocoBundle](https://github.com/JCID/JcidLocoBundle).