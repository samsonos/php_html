#HTML markup generator for SamsonPHP

This module is commonly used and designed for front-end developers who want to use all power of MVC approach
and all features of SamsonPHP framework but must get as output simple combined static html markup(.htm, .html) files
without any PHP server code. This task also called as creating HTML markup.

> Module also automatically gathers all generated localized static HTML markup web-application versions to a ZIP archive
 that you can immediately send to your customer.

[![Latest Stable Version](https://poser.pugx.org/samsonos/php_html/v/stable.svg)](https://packagist.org/packages/samsonos/php_html) 
[![Build Status](https://travis-ci.org/samsonos/php_html.png)](https://travis-ci.org/samsonos/php_html)
[![Coverage Status](https://img.shields.io/coveralls/samsonos/php_html.svg)](https://coveralls.io/r/samsonos/php_html?branch=master)
[![Code Climate](https://codeclimate.com/github/samsonos/php_html/badges/gpa.svg)](https://codeclimate.com/github/samsonos/php_html) 
[![Total Downloads](https://poser.pugx.org/samsonos/php_html/downloads.svg)](https://packagist.org/packages/samsonos/php_html)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/samsonos/php_html/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/samsonos/php_html/?branch=master)

##Generating HTML markup from your project
To generate all your projects HTML markup pages you should visit ```http://domain.com/html``` page.
By default module is configured to put all HTML markup files into ```__SAMSON_PUBLIC_PATH.'out/```(by default ```www/out/```)
folder.

Module automatically scans all your module controllers and their actions and call all them to get their actual output
as pure generated HTML output and stores them as ```.html``` files in default module cache folder(by default ```www/cache/html/```).

## Internalization support(i18n)
If you web-application uses [SamsonPHP i18n module](http://github.com/samsonos/php_i18n) all controller actions output would be
automatically generated for all supported locales. 

For example if have set two locales ```en-English``` and ```ru-Russian```, and have set that default locale is ```Russian-ru```
then module will generate ```www/out/def``` folder and build all html files for default Russian locale, and also create ```www/out/en```
folder and put English html files there.

## Resources
Module automatically finds your combined ```javascript``` and ```css``` resource files and puts them to your html version root folder as:
* ```index.js``` for javascript file
* ```style.css``` for css file
> All external javascript and css links are left as they are

Module will rewrite all CSS ```url(...)``` to meet new structure,

## What about images and other static resources?
If you were using ```<?php path()?>``` directive in your images in view files, than all pathes would be rewritten automatically
for you to meet new structure. All other resources would be copied to ```[module]/[path]```.

## How to use?
We perefer Composer, how to use it via composer.json:
```json
"require-dev": {
    "samsonos/php_html": "*",
```

##Summary

This module is an automatic converter from SamsonPHP web-application project to a combined finalize static HTML web-application.

Developed by [SamsonOS](http://samsonos.com/)
