JS_SRC=src/main/js/live-blogging.js
JS_TESTS=
PHP_LIBS=build/twitteroauth/
PHP_FILES=build/live-blogging.php build/twittercallback.php
IMG_FILES=build/img/add.png build/img/delete.png build/img/icon.png
LANGUAGE_FILES=build/lang/live-blogging-fa_IR.mo build/lang/live-blogging-zh_CN.mo build/lang/live-blogging-lt_LT.mo
YUICOMPRESSOR=libs/yuicompressor-2.4.7.jar
JSLINT=libs/jslint4java-2.0.2.jar

dist: $(PHP_LIBS) $(PHP_FILES) $(IMG_FILES) $(LANGUAGE_FILES) build/live-blogging.min.js build/readme.txt build/LICENSE

test: all cucumber phpunit

strict: jslint phpcs

lint: strict test dist

clean:
	rm -rf build/*

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
	pngcrush $< $@
	touch $@

build/LICENSE: LICENSE
	cp LICENSE build/LICENSE

build/readme.txt: resources/readme.txt
	cp resources/readme.txt build/readme.txt

jslint:
	java -jar $(JSLINT) $(JS_SRC)

jstest: build/qunit/TestRunner.html $(JS_TESTS)

build/qunit/TestRunner.html: src/test/js/TestRunner.html
	test -d build/qunit/ || mkdir build/qunit/
	cp src/test/js/TestRunner.html build/qunit/TestRunner.html

build/qunit/%.js: src/test/js/%.js
	cp $< $@

phpcs: $(PHP_FILES)
	phpcs $(PHP_FILES)

phpunit:
	phpunit -c src/test/php/phpunit.xml

cucumber: dist jstest
	(cd src/test/cucumber && bundle exec cucumber)

checkoutsvn:
	svn co http://plugins.svn.wordpress.org/live-blogging/trunk build

pushtowordpress: checkoutsvn all
	svn ci build

tagwordpress: pushtowordpress
	(test -n "$(WORDPRESS_VERSION)" && svn cp http://plugins.svn.wordpress.org/live-blogging/trunk http://plugins.svn.wordpress.org/live-blogging/tags/$(WORDPRESS_VERSION))
