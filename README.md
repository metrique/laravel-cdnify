# laravel-cdnify

## Installation

1. Add the following to the `repositories` section of your composer.json

```
"repositories": [
    {
        "url": "https://github.com/Metrique/laravel-cdnify",
        "type": "git"
    }
],
```

2. Add `"Metrique/laravel-cdnify": "dev-master"` to the require section of your composer.json. 
3. `composer update`
4. Add `Metrique\CDNify\CDNifyServiceProvider::class,` to your list of service providers. in `config/app.php`.
5. `php artisan vendor:publish` to publish the `config/cdnify.php` config file to your application config directory.

## Usage

### Defaults

Defaults can be configured by editing `config/cdnify.php` in your main application direcoty.

### CDN

- `$cdnify->defaults();` Set the settings back to the config defaults.
- `$cdnify->get($path, $elixir = true);` Helper utility combining the path, elixir and toString methods.
- `$cdnify->toString();` Returns the CDN path as a string.
- `$cdnify->cdn();` Returns a CDN path, if roundRobin is set to true then it will roundRobin the list of CDN's
- `$cdnify->path($path);` Set the path to be CDNified.
- `$cdnify->environments($environments);` Set the environments where the path should be CDNified, if null defaults will be used.
- `$cdnify->elixir($bool);` Set whether elixir should be used if available.
- `$cdnify->roundRobin($bool);` Enables round robin on the cdn list.