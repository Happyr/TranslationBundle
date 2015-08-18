# Happyr Loco Bundle

[WIP]

## Install

Install the bundle with `composer require happyr/loco-bundle`

``` yaml
# /app/config/config.yml
happyr_loco:
  locales: ['en','sv','fr','es']
  projects:
    messages:
      api_key: 'foobar' 
    navigation:
      api_key: 'bazbar' 

# or..
happyr_loco:
  locales: ['en','sv','fr','es']
  dimensions: ['messages', 'navigation']
  projects:
    acme:
      api_key: 'foobar'  
    
```


``` yaml
# /app/config/routing_dev.yml
_happyr_loco:
    resource: '@HappyrLocoBundle/Resources/config/routing_dev.yml'
    
```

# Credits

This bundle is both inspired by and is using some of the code from [Jokicode.com](http://jolicode.com/blog/translation-workflow-with-symfony2)
and from Cliff Odijk's (@cmodijk) [LocoBundle](https://github.com/JCID/JcidLocoBundle).