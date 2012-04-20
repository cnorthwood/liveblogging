Live Blogging for WordPress
===========================

http://cnorthwood.github.com/liveblogging/
http://wordpress.org/extend/plugins/live-blogging/

Using it
--------

Don't use this, just grab a .zip from http://wordpress.org/extend/plugins/live-blogging/ instead.

Development Prerequisites
-------------------------

To 'build' Live Blogging for WordPress:

* Make
* Java
* gettext
* pngcrush

To run the Live Blogging for WordPress test suite:

* Ruby
* phpunit
* Bundler
* Firefox
* phpcs

Using the development version of it
-----------------------------------

    mkdir build
    make

This will put a 'build' of Live Blogging in the build directory, ready to be
popped into your WordPress plugins directory.

Preparing WordPress for Cucumber tests
--------------------------------------

    cd src/test/cucumber
    bundle

You will also need to set up a WordPress install with some base configuration
for Cucumber to run against. More info to come.

Running the tests
-----------------

    make test

Ensuring code quality
---------------------

    make lint

Contributing
------------

Pull requests would be nice at https://github.com/cnorthwood/liveblogging/

Releasing to WordPress
----------------------

To just update the trunk release:

    make pushtowordpress

To tag a new release:

    make tagwordpress WORDPRESS_VERSION=TEST

This of course assumes you have permission to commit to the WordPress SVN repo.
