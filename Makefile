JS_SRC=src/main/js/live-blogging.js
PHP_LIBS=build/twitteroauth/
PHP_FILES=build/live-blogging.php build/twittercallback.php
IMG_FILES=build/img/add.png build/img/delete.png build/img/icon.png
LANGUAGE_FILES=build/lang/live-blogging-fa_IR.mo build/lang/live-blogging-zh_CN.mo
YUICOMPRESSOR=libs/yuicompressor-2.4.7.jar
JSLINT=libs/jslint4java-2.0.2.jar

all: $(PHP_LIBS) $(PHP_FILES) $(IMG_FILES) $(LANGUAGE_FILES) build/live-blogging.min.js build/readme.txt build/LICENSE

test: cucumber jslint phpcs phpunit

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

build/readme.txt: readme.txt
	cp resources/readme.txt build/readme.txt

jslint:
	java -jar $(JSLINT) $(JS_SRC)

phpcs: $(PHP_FILES)
	phpcs $(PHP_FILES)

phpunit:
	phpunit -c src/test/php/phpunit.xml

cucumber:
	(cd src/test/cucumber && bundle exec cucumber)
