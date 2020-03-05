![Hooks](https://raw.githubusercontent.com/sierratecnologia/hooks/master/resources/logo.png)

<p align="center">
<a href="https://travis-ci.org/sierratecnologia/hooks"><img src="https://travis-ci.org/sierratecnologia/hooks.svg?branch=master" alt="Build Status"></a>
<a href="https://styleci.io/repos/76883435/shield?style=flat"><img src="https://styleci.io/repos/76883435/shield?style=flat" alt="Build Status"></a>
<a href="https://packagist.org/packages/sierratecnologia/hooks"><img src="https://poser.pugx.org/sierratecnologia/hooks/downloads.svg?format=flat" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/sierratecnologia/hooks"><img src="https://poser.pugx.org/sierratecnologia/hooks/v/stable.svg?format=flat" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/sierratecnologia/hooks"><img src="https://poser.pugx.org/sierratecnologia/hooks/license.svg?format=flat" alt="License"></a>
</p>

Made with ❤️ by [Mark Topper](https://marktopper.com)

# Hooks

Hooks is a extension system for your [Laravel](https://laravel.com) application.

# Installation

Install using composer:

```
composer require sierratecnologia/hooks
```

Then add the service provider to the configuration:
```php
'providers' => [
    Hooks\HooksServiceProvider::class,
],
```

# Packages

Packages can be found on [sierratecnologia.io](https://sierratecnologia.io).

# Integrations

- [Voyager Hooks](https://github.com/sierratecnologia/voyager-hooks) - Hooks supported directly in the Voyager admin panel.
