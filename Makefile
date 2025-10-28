.PHONY: up down composer console migrate

up:
	docker compose up -d --build

down:
	docker compose down -v --remove-orphans

composer:
	docker compose exec php-fpm composer install --no-interaction

console:
	docker compose exec php-fpm php bin/console $(cmd)

migrate:
	docker compose exec php-fpm php bin/console doctrine:migrations:migrate -n
