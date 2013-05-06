Live Blogging for WordPress
===========================

[![Build Status](https://travis-ci.org/cnorthwood/liveblogging.png?branch=master)](https://travis-ci.org/cnorthwood/liveblogging)

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
* pngcrush (OSX/Linux only - not needed on Windows)

To run the Live Blogging for WordPress test suite:

* Ruby
* Bundler
* Firefox
* Vagrant

Using the development version of it
-----------------------------------

    make dist
    cd sandbox
    vagrant up

This will start a VM at http://192.168.20.10 with a blank WordPress blog for you to experiment with. This
must be running for the Cucumber tests to run. The database is wiped on every load of the sandbox. The username and
password for the WordPress admin is admin and cucumber.

Alternatively, you can run

    make dist

And use the build/ folder in your own WordPress development instance.

Running the tests
-----------------

    make test

Ensuring code quality
---------------------

    make strict

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
