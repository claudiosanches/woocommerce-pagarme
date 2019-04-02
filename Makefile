list:
	@echo ""
	@echo "Commands | Description"
	@echo "------------------------------------------------------------------"
	@echo "list     | Show this list"
	@echo "prepare  | Setup WordPress, Woocommerce and Woocommerce-Pagar.me"
	@echo "up       | Create and start containers"

up:
	docker-compose up -d

wait-for-wordpress:
	sleep 20

wp-install:
	docker-compose exec wordpress wp core install --allow-root \
	--url=woopagarme \
	--title=Pagar.me --admin_user=pagarme \
	--admin_email=pagarme@pagar.me \
	--admin_password=wordpress \
	--path=/var/www/html \
	&& docker-compose exec wordpress wp core update --allow-root

wp-setup:
	docker-compose exec wordpress wp plugin install woocommerce \
	woocommerce-extra-checkout-fields-for-brazil --activate --allow-root \
	&& docker-compose exec wordpress wp plugin activate woocommerce-pagarme --allow-root \
	&& docker-compose exec wordpress wp plugin install wordpress-importer --activate --allow-root \
	&& docker-compose exec wordpress wp theme install storefront --activate --allow-root \
	&& docker-compose exec wordpress wp language core install pt_BR --allow-root \
	&& docker-compose exec wordpress wp site switch-language pt_BR --allow-root \
	&& docker-compose exec wordpress wp language plugin install --all pt_BR --allow-root \
	&& docker-compose exec wordpress wp rewrite structure '/%postname%/' --allow-root

wc-setup:
	docker-compose exec wordpress wp option update woocommerce_default_country "BR" --allow-root \
	&& docker-compose exec wordpress wp option update woocommerce_currency "BRL" --allow-root \
	&& docker-compose exec wordpress wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip --allow-root \
	&& docker-compose exec wordpress wp wc tool run install_pages --allow-root --user=1

wcp-setup:
	docker-compose exec wordpress php wp-content/plugins/woocommerce-pagarme/bin/bash.php

prepare: up wait-for-wordpress wp-install wp-setup wc-setup wcp-setup

composer-install:
	docker-compose run composer install

lint: composer-install
	docker-compose run composer ./bin/lint.sh

down:
	docker-compose down
