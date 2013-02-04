Ecocode Minify(v. 0.0.3 beta)
=============

Overview
---------------

Ecocode Minify is a Magento Module, to minify your merged CSS and JS files.
For JS minification its using the google [closure compiler](http://closure-compiler.appspot.com/ "closure compiler") and
for CSS the [YUI Compressor](http://yui.github.com/yuicompressor/ "YUI Compressor"). Both are written in java and are jar files.
In addition this module will give you the opportunity to scan your javascript files for errors and
possible compiling errors. Its recommend to at least scan all of your js files once with this tool.

## Tested with:
* Magento Community Edition 1.7.0.2

## Requirements

* Java

## Features
* Minify and compiles js files on the fly
* Minify css
* Adds a suffix to all merged js/css files to prevent browser cache issues on deployments

## Additional Features

* Admin module to view minify logs
* Admin module to validate js files
* Warmup functionality for JS/CSS cache clear

## Notes

* Minifing is disabled by default. Tomake it active go to System->Configuration->Developer->JavaScript/CSS Settings and
	set "Merge * Files" to "Yes" and then "Minify * Files" to "Yes"
* The log functionality is using mysql table due to prevent logsplitting on multiserver environments
* The warmup functionality will be less effectiv multiserver environments unless you store your merged files in a central place
* A new suffix well be generated when you click the "Flush JavaScript/CSS Cache" button in you magento backend cache control section
* The filesize is included in the merged filename so changing a js/css file will invalidate the involved merged files and new ones will be generated.
* If a file cannot be compiled the the uncompiled merged version will be served

## Installation

#### Using [Modman](https://github.com/colinmollenhour/modman "Modman")

navigate to your magento root folder:

	modman init
	modman clone https://github.com/Fantus/ecocode_minify.git

please note, if you are using modman without the "--copy" flag you have to set 

	dev/template/allow_symlink

to "TRUE" under "System->Configuration->Developer->Template Settings->Allow Symlinks"

if you miss this, the admin modules may not be rendered! 

#### Using Git

Simply clone the repo somewhere and copy the content in your magento root directory excluding the "modman" file and the README.md.


#### Using [Magento Connect](http://www.magentocommerce.com/magento-connect/ "Magento Connect")

comming soon

## Author

* Justus Krapp


## License

Copyright(C) 2013 Ecocode Gbr (http://ecocode.de)

Licensed under the under the MIT License.
Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), 
to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, 
and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, 
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.