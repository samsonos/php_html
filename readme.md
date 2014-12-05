#HTML markup generator for SamsonPHP

This module is commonly used and designed for front-end developers who want to use all power of MVC approach
and all features of SamsonPHP framework but must get as output simple combined static html markup(.htm, .html) files
without any PHP server code. This task also called as creating HTML markup.

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
as pure generated HTML ouptput and stores them as ```.html``` files in default module cache folder(by default ```www/cache/html/```).

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


Developed by [SamsonOS](http://samsonos.com/)
