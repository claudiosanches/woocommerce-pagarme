list:
	@echo ""
	@echo "Commands | Description"
	@echo "------------------------------------------------------------------"
	@echo "list     | Show this list"
	@echo "prepare  | Setup WordPress, Woocommerce and Woocommerce-Pagar.me"
	@echo "up       | Create and start containers"

up: company-setup
	docker-compose up -d

wait-for-wordpress:
	sleep 20

wp-install:
	docker-compose exec woopagarme wp core install --allow-root \
	--url=woopagarme \
	--title=Pagar.me --admin_user=pagarme \
	--admin_email=pagarme@pagar.me \
	--admin_password=wordpress \
	--path=/var/www/html \
	&& docker-compose exec woopagarme wp core update --allow-root \
	&& docker-compose exec woopagarme touch /var/www/html/wp-content/uploads/wc-logs \
	&& docker-compose exec woopagarme chown www-data:www-data -R /var/www/html/wp-content/uploads/wc-logs

wp-setup:
	docker-compose exec woopagarme wp plugin install woocommerce --version=3.7.0 \
	woocommerce-extra-checkout-fields-for-brazil --activate --allow-root \
	&& docker-compose exec woopagarme wp plugin activate woocommerce-pagarme --allow-root \
	&& docker-compose exec woopagarme wp plugin install wordpress-importer --activate --allow-root \
	&& docker-compose exec woopagarme wp theme install storefront --activate --allow-root \
	&& docker-compose exec woopagarme wp language core install pt_BR --allow-root \
	&& docker-compose exec woopagarme wp site switch-language pt_BR --allow-root \
	&& docker-compose exec woopagarme wp language plugin install --all pt_BR --allow-root \
	&& docker-compose exec woopagarme wp rewrite structure '/%postname%/' --allow-root

wc-setup:
	docker-compose exec woopagarme wp option update woocommerce_default_country "BR" --allow-root \
	&& docker-compose exec woopagarme wp option update woocommerce_currency "BRL" --allow-root \
	&& docker-compose exec woopagarme wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip --allow-root \
	&& docker-compose exec woopagarme wp wc tool run install_pages --allow-root --user=1

wcp-setup:
	docker-compose exec woopagarme php wp-content/plugins/woocommerce-pagarme/bin/bash.php

prepare: up wait-for-wordpress wp-install wp-setup wc-setup wcp-setup

composer-install:
	docker-compose run composer install

lint-php: composer-install
	docker-compose run composer ./bin/lint.sh

lint-js:
	docker-compose run node bash -c 'npm install -g grunt-cli && npm install && grunt jshint'

test-e2e:
	docker-compose run node bash -c \
	'npm install && npx cypress install && npx cypress run'

company-setup:
	touch .env.local
	docker-compose run composer php ./bin/setup-company-temporary.php

down:
	docker-compose down
