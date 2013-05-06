JS_SRC=src/main/js/live-blogging.js
PHP_LIBS=libs/twitteroauth/
PHP_FILES=build/live-blogging.php build/twittercallback.php build/LiveBlogging.php \
          build/LiveBlogging/Admin/MetaBox.php build/LiveBlogging/Admin/MetaBox/Chatbox.php build/LiveBlogging/Admin/MetaBox/Enable.php build/LiveBlogging/Admin/MetaBox/QuickUpload.php build/LiveBlogging/Admin/MetaBox/Select.php \
          build/LiveBlogging/Admin/Page/MeteorStatus.php build/LiveBlogging/Admin/Page/Migrate.php build/LiveBlogging/Admin/Page/Options.php \
          build/LiveBlogging/Legacy.php build/LiveBlogging/LiveBlog.php build/LiveBlogging/LiveBlogEntry.php build/LiveBlogging/Twitter.php \
          build/LiveBlogging/Setting.php build/LiveBlogging/Setting/Comments.php build/LiveBlogging/Setting/ContentHooks.php build/LiveBlogging/Setting/DateStyle.php build/LiveBlogging/Setting/MeteorController.php build/LiveBlogging/Setting/MeteorControllerPort.php build/LiveBlogging/Setting/MeteorNamespace.php build/LiveBlogging/Setting/MeteorSubscriber.php build/LiveBlogging/Setting/PostStyle.php build/LiveBlogging/Setting/TimedUpdateFrequency.php build/LiveBlogging/Setting/Twitter.php build/LiveBlogging/Setting/TwitterComments.php build/LiveBlogging/Setting/UpdateMethod.php build/LiveBlogging/Setting/UpdateStyle.php \
          build/LiveBlogging/Updater/Ajax.php build/LiveBlogging/Updater/Meteor.php build/LiveBlogging/Updater/Timed.php
IMG_FILES=build/img/add.png build/img/delete.png build/img/icon.png
LANGUAGE_FILES=build/lang/live-blogging-fa_IR.mo build/lang/live-blogging-lt_LT.mo build/lang/live-blogging-sk_SK.mo build/lang/live-blogging-zh_CN.mo
YUICOMPRESSOR=libs/yuicompressor-2.4.7.jar
JSLINT=libs/jslint4java-2.0.2.jar
JSTESTDRIVER=libs/JsTestDriver-1.3.5.jar
BROWSER=open
PHPCS_VERSION=1.4.5

dist: $(PHP_LIBS) $(PHP_FILES) $(IMG_FILES) $(LANGUAGE_FILES) build/live-blogging.min.js build/readme.txt build/LICENSE

clean:
	rm -rf build/*

test: phpunit jstestdriver

libs/phpunit.phar:
	(cd libs && wget http://pear.phpunit.de/get/phpunit.phar)
	chmod +x libs/phpunit.phar

phpunit: libs/phpunit.phar
	libs/phpunit.phar -c src/test/php/phpunit.xml

jstestdriver:
	java -jar $(JSTESTDRIVER) --reset --port 9874 --browser $(BROWSER) --tests all

cucumber: sandbox
	(cd src/test/cucumber && bundle && bundle exec cucumber)

sandbox: dist
	(cd sandbox && vagrant up)

strict: jslint phpcs

jslint:
	java -jar $(JSLINT) $(JS_SRC)

libs/PHP_CodeSniffer-%/scripts/phpcs:
	(cd libs && wget http://download.pear.php.net/package/PHP_CodeSniffer-$(PHPCS_VERSION).tgz && tar zxf PHP_CodeSniffer-$(PHPCS_VERSION).tgz)

libs/PHP_CodeSniffer-%/CodeSniffer/Standards/WordPress/ruleset.xml:
	(cd libs/PHP_CodeSniffer-$(PHPCS_VERSION)/CodeSniffer/Standards/ && wget https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/archive/master.tar.gz && tar zxf master.tar.gz && mv WordPress-Coding-Standards-master WordPress)

phpcs: libs/PHP_CodeSniffer-$(PHPCS_VERSION)/scripts/phpcs libs/PHP_CodeSniffer-$(PHPCS_VERSION)/CodeSniffer/Standards/WordPress/ruleset.xml $(PHP_FILES)
	php libs/PHP_CodeSniffer-$(PHPCS_VERSION)/scripts/phpcs --standard=WordPress $(PHP_FILES)

build/live-blogging.min.js: build $(JS_SRC)
	java -jar $(YUICOMPRESSOR) -o build/live-blogging.min.js $(JS_SRC)

build/%.php: src/main/php/%.php
	mkdir -p `dirname $@`
	cp $< $@

build/lang/%.mo: resources/lang/%.po
	test -d build/lang/ || mkdir build/lang/
	msgfmt -o $@ $<

build/twitteroauth/: src/main/php/twitteroauth/
	cp -r src/main/php/twitteroauth build/twitteroauth

build/img/%.png: resources/img/%.png
	test -d build/img/ || mkdir build/img/
	mkdir -p build/img/
	(test `uname -o` = "Msys" && libs/pngcrush.exe $< $@) || pngcrush $< $@
	touch $@

build/LICENSE: LICENSE
	cp LICENSE build/LICENSE

build/readme.txt: resources/readme.txt
	cp resources/readme.txt build/readme.txt

checkoutsvn:
	rm -rf build
	svn co http://plugins.svn.wordpress.org/live-blogging/trunk build

pushtowordpress: checkoutsvn dist
	svn ci build

tagwordpress: pushtowordpress
	(test -n "$(WORDPRESS_VERSION)" && svn cp http://plugins.svn.wordpress.org/live-blogging/trunk http://plugins.svn.wordpress.org/live-blogging/tags/$(WORDPRESS_VERSION))
