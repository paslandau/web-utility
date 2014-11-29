#WebUtility
[![Build Status](https://travis-ci.org/paslandau/WebUtility.svg?branch=master)](https://travis-ci.org/paslandau/WebUtility)

Library to extend PHP core functions by common web related functions

##Description
[todo]

##Requirements

- PHP >= 5.5

##Installation

The recommended way to install WebUtility is through [Composer](http://getcomposer.org/).

    curl -sS https://getcomposer.org/installer | php

Next, update your project's composer.json file to include WebUtility:

    {
        "repositories": [
            {
                "type": "git",
                "url": "https://github.com/paslandau/WebUtility.git"
            }
        ],
        "require": {
             "paslandau/WebUtility": "~0"
        }
    }

After installing, you need to require Composer's autoloader:
```php

require 'vendor/autoload.php';
```