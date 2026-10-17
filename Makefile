# Everything runs in Docker: PHP and Composer don't need to be installed.
DC = docker compose
RUN = $(DC) run --rm -T app

.PHONY: up down setup seed test lint format wordpress worker-logs

up: ## Start MySQL, the app (http://localhost:8080), worker, scheduler and Mailpit (http://localhost:8025)
	@test -f .env || (cp .env.example .env && printf "UID=$$(id -u)\nGID=$$(id -g)\n" >> .env)
	$(DC) up -d --build mysql mailpit
	$(RUN) composer install --no-interaction
	$(DC) run --rm -T -w /app/wordpress-plugin app composer install --no-interaction
	@grep -q '^APP_KEY=base64' .env || $(RUN) php artisan key:generate
	$(RUN) php artisan migrate --force
	npm ci && npm run build
	$(DC) up -d app worker scheduler

seed: ## Demo data: admin@example.com / postbag-demo, two lists, a draft campaign
	$(RUN) php artisan db:seed --force

down:
	$(DC) --profile wordpress down

test: ## Laravel (Pest) and WordPress plugin (PHPUnit) tests
	$(RUN) php artisan test
	$(DC) run --rm -T -w /app/wordpress-plugin app vendor/bin/phpunit

lint: ## Pint, PHPStan (level 8) and WordPress coding standards
	$(RUN) vendor/bin/pint --test
	$(RUN) vendor/bin/phpstan analyse --memory-limit=1G
	$(DC) run --rm -T -w /app/wordpress-plugin app vendor/bin/phpcs

format:
	$(RUN) vendor/bin/pint

wordpress: ## Demo WordPress at http://localhost:8081 (admin / admin) with the plugin connected
	$(DC) --profile wordpress up -d wordpress
	@until $(DC) run --rm -T wpcli wp core version >/dev/null 2>&1; do sleep 2; done
	$(DC) run --rm -T wpcli wp core is-installed || $(DC) run --rm -T wpcli wp core install --url=http://localhost:8081 --title="Demo blog" --admin_user=admin --admin_password=admin --admin_email=admin@example.com --skip-email
	$(DC) run --rm -T wpcli wp plugin activate postbag-signup
	@TOKEN=$$($(RUN) php artisan postbag:demo-token) && LIST=$$($(RUN) php artisan postbag:demo-token --list) && \
		$(DC) run --rm -T wpcli wp option update postbag_signup_settings "{\"api_url\":\"http://app:8080\",\"api_token\":\"$$TOKEN\",\"list_id\":\"$$LIST\"}" --format=json
	$(DC) run --rm -T wpcli wp postbag status
	@echo "WordPress: http://localhost:8081/wp-admin (admin / admin). Add the 'Newsletter signup' block to a page."

worker-logs:
	$(DC) logs -f worker
