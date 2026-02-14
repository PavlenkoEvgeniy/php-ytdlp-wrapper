php-cs-fixer-check:
	./vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes

php-cs-fixer-fix:
	./vendor/bin/php-cs-fixer fix --allow-risky=yes

phpstan:
	./vendor/bin/phpstan analyse --memory-limit=256M

psalm:
	./vendor/bin/psalm --config=psalm.xml.dist --show-info=true

peck:
	./vendor/bin/peck --config=peck.json

test:
	./vendor/bin/phpunit --colors=always
