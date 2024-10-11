SHELL=/bin/bash

.DEFAULT_GOAL := help

PHP_RUN := docker compose run -T --rm php-cli
ARGS ?= $(shell read -p "Additional arguments ([enter] for none): " args; echo $$args)

## setup: 	Setup the project for the first time
.PHONY: setup
setup: config up
	docker run -it -w=/opt/project --mount src="$(shell pwd)",target=/opt/project,type=bind node:alpine npm install
	dev/composer install
	dev/console doctrine:migrations:migrate --no-interaction --allow-no-migration

## build: 	Build the docker containers
.PHONY: build
build:
	docker compose build --pull

## config: 	Copy the config.dist files (if needed)
.PHONY: config
config:
  # copy -n does not overwrite the target if it exists, but exits with an error, so || true ignores the error
#	cp -n config/packages/parameters.yaml.dist config/packages/parameters.yaml || true
	cp -n .env.dist .env || true

## up: 		Runs local environment
.PHONY: up
up:
	docker compose up -d

## destroy: 	Destroys the project
.PHONY: destroy
destroy:
	docker compose down --remove-orphans -v

.PHONY: php
php:
	${PHP_RUN} $(ARGS)

# make sure you installed https://github.com/nektos/act
.PHONY: act
act:
	act -P ubuntu-latest=nektos/act-environments-ubuntu:18.04 $(ARGS)

## console:	Runs bin/console through docker
.PHONY: console
console:
	${PHP_RUN} php bin/console $(ARGS) --ansi

## composer:      Runs composer through docker
.PHONY: composer
composer:
	${PHP_RUN} composer $(ARGS) --ansi

## deploy:	Deploy the code to remote environments
.PHONY: deploy
deploy:
	./deploy

## publish:	Build and publish the containers to github registry
publish:
	docker compose build --pull
	docker compose push

## release:	Checkout trunk, get most recent version, create new (minor) tag, push trunk and tags
.PHONY: release
release:
	git checkout trunk
	git pull
	dev/bump-tag.sh
	git push origin trunk && git push --tags

## branch:	Checkout trunk, get most recent version, create new branch based on trunk
.PHONY: branch
branch:
	git checkout trunk
	git pull
	@read -p "Enter Branch Name: " branchName; \
    git checkout -b $$branchName

## help:		Print this message
.PHONY: help
help: Makefile
	@sed -n 's/^##//p' $<

## analyse:       Runs PHPSTAN (you can also use analyse-and-generate-baseline to update the baseline file).
.PHONY: analyze analyse
analyze: analyse
analyse:
	${PHP_RUN} php -d memory_limit=-1 vendor/bin/phpstan analyse $(ARGS)

## analyse-and-generate-baseline:       Runs PHPSTAN and generates a baseline
.PHONY: analyze-and-generate-baseline analyse-and-generate-baseline
analyze-and-generate-baseline: analyse-and-generate-baseline
analyse-and-generate-baseline:
	${PHP_RUN} php -d memory_limit=-1 vendor/bin/phpstan analyse --generate-baseline

## tests:		Runs all tests through docker (with optional arguments)
.PHONY: tests
tests: analyse unit integration linting

## linting:		Runs the linting through docker
.PHONY: linting
linting: lint-composer lint-yaml lint-container

## lint-composer:		Runs the lint-composer through docker
.PHONY: lint-composer
lint-composer:
	dev/composer validate --strict

## lint-yaml:		Runs the lint-yaml through docker
.PHONY: lint-yaml
lint-yaml:
	dev/console lint:yaml config src --parse-tags

## lint-container:		Runs the lint-container through docker
.PHONY: lint-container
lint-container:
	dev/console lint:container

## unit:		Runs the unit tests through docker (with optional arguments)
.PHONY: unit
unit:
	${PHP_RUN} php vendor/bin/phpunit --configuration unit.xml $(ARGS)

## integration:		Runs the integration tests through docker (with optional arguments)
.PHONY: integration
integration: database-integration

## database-integration:		Runs the database integration tests through docker (with optional arguments)
.PHONY: database-integration
database-integration:
	${PHP_RUN} php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=test
	${PHP_RUN} php vendor/bin/phpunit --configuration database-integration.xml $(ARGS)

## cs-fix:	PHP CodeStyle fix
.PHONY: cs-fix
cs-fix:
	${PHP_RUN} php -d memory_limit=-1 vendor/bin/php-cs-fixer fix --verbose --diff $(ARGS)

.PHONY: node
node:
	docker run -it -w=/opt/project --mount src="$(shell pwd)",target=/opt/project,type=bind node:alpine $(ARGS)

.PHONY: watch
watch:
	dev/node npm run watch

## update-ansible-roles: Installs ansible roles
.PHONY: update-ansible-roles
update-ansible-roles:
	ansible-galaxy install -f -r ansible/ansible-galaxy.yml