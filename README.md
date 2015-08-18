# Happyr Loco Bundle

[WIP]

## Install

Install the bundle with `composer require happyr/loco-bundle`

``` yaml
# /app/config/config.yml
happyr_loco:
  locales: ['en','sv','fr','es']
  projects:
    navigation:
      api_key: 'foobar' 
    
```


``` yaml
# /app/config/routing_dev.yml
_happyr_loco:
    resource: '@HappyrLocoBundle/Resources/config/routing_dev.yml'
    
```