JS_SRC=src/main/js/live-blogging.js
PHP_LIBS=build/twitteroauth/
PHP_FILES=build/live-blogging.php build/twittercallback.php
IMG_FILES=build/img/add.png build/img/delete.png build/img/icon.png
LANGUAGE_FILES=build/lang/live-blogging-fa_IR.mo build/lang/live-blogging-lt_LT.mo build/lang/live-blogging-sk_SK.mo build/lang/live-blogging-zh_CN.mo
YUICOMPRESSOR=libs/yuicompressor-2.4.7.jar
JSLINT=libs/jslint4java-2.0.2.jar
JSTESTDRIVER=libs/JsTestDriver-1.3.5.jar
BROWSER=open

dist: $(PHP_LIBS) $(PHP_FILES) $(IMG_FILES) $(LANGUAGE_FILES) build/live-blogging.min.js build/readme.txt build/LICENSE

clean:
	rm -rf build/*

test: phpunit jstestdriver

phpunit:
	libs/phpunit/phpunit.php -c src/test/php/phpunit.xml

jstestdriver:
	java -jar $(JSTESTDRIVER) --reset --port 9874 --browser $(BROWSER) --tests all

cucumber: dist
	(cd src/test/cucumber && bundle install && bundle exec cucumber)

strict: jslint phpcs

jslint:
	java -jar $(JSLINT) $(JS_SRC)

phpcs: $(PHP_FILES)
	php libs/PHP_CodeSniffer/scripts/phpcs $(PHP_FILES)

build/live-blogging.min.js: build $(JS_SRC)
	java -jar $(YUICOMPRESSOR) -o build/live-blogging.min.js $(JS_SRC)

build/%.php: src/main/php/%.php
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
	svn co http://plugins.svn.wordpress.org/live-blogging/trunk build

pushtowordpress: checkoutsvn dist
	svn ci build

tagwordpress: pushtowordpress
	(test -n "$(WORDPRESS_VERSION)" && svn cp http://plugins.svn.wordpress.org/live-blogging/trunk http://plugins.svn.wordpress.org/live-blogging/tags/$(WORDPRESS_VERSION))
