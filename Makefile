.PHONY: php-cs-fixer-check php-cs-fixer-fix phpstan psalm peck lint test

php-cs-fixer-check:
	./vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes --allow-unsupported-php-version=yes

php-cs-fixer-fix:
	./vendor/bin/php-cs-fixer fix --allow-risky=yes --allow-unsupported-php-version=yes

phpstan:
	./vendor/bin/phpstan analyse --memory-limit=256M

psalm:
	./vendor/bin/psalm --config=psalm.xml.dist

peck:
	./vendor/bin/peck --config=peck.json

lint: php-cs-fixer-check phpstan psalm peck

test:
	./vendor/bin/phpunit --colors=always
