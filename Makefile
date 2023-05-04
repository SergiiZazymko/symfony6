#!make

ifneq (,$(wildcard ./.env))
    include .env
    export
endif

ENV := ${APP_ENV}

ifndef ENV
$(error The ENV variable is missing.)
endif
 
ifeq ($(filter $(ENV),test dev stag prod),)
$(error The ENV variable is invalid.)
endif
 
ifeq (,$(filter $(ENV),test dev))
COMPOSE_FILE_PATH := -f docker-compose.yml
endif

IMAGE := judzhin/vido

help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

build: ## Build or rebuild services without cache when building the image
	$(info Make: Building "$(ENV)" environment images.)
	@TAG=$(TAG) docker-compose build --no-cache
	@#make -s clean

start: ## Builds, (re)creates, starts, and attaches to containers for a service in the background
	$(info Make: Starting "$(ENV)" environment containers.)
	@TAG=$(TAG) docker-compose $(COMPOSE_FILE_PATH) up -d

stop: ## Stop running containers without removing them
	$(info Make: Stopping "$(ENV)" environment containers.)
	@docker-compose stop

down: ## Stops containers and removes containers, networks, volumes, and images created by `up`
	$(info Make: Stopping and removing "$(ENV)" environment containers, networks, and volumes.)
	@docker-compose down --remove-orphans

clear: ## Stops containers and removes containers, networks, volumes with static informations
	$(info Make: Stopping and removing "$(ENV)" environment containers, networks, and volumes with data.)
	@docker-compose down -v --remove-orphans

recreate: ## Recreate containers
	$(info Make: Recreateing "$(ENV)" environment containers.)
	@docker-compose up -d --build --force-recreate --no-deps

restart: ## Stop and start containers
	$(info Make: Restarting "$(ENV)" environment containers.)
	@make -s stop
	@make -s start

push: ## Pushing image to hub
	$(info Make: Pushing "$(TAG)" tagged image.)
	@docker push $(IMAGE):$(TAG)

pull: ## Pulling image from hub
	$(info Make: Pulling "$(TAG)" tagged image.)
	@docker pull $(IMAGE):$(TAG)

clean: ## Remove unused data without prompt for confirmation
	@docker system prune --volumes --force

login: ## Login to Docker Hub.
	$(info Make: Login to Docker Hub.)
	@docker login -u $(DOCKER_USER) -p $(DOCKER_PASS)

cli: ## Run CLI
	$(info Make: Run CLI)
	@docker-compose exec -uroot php-fpm bash

up: ## Up the project
	$(info Make: Init the project.)
	@make -s stop
	@make -s start

start_worker:
	$(info Make: Start queue worker.)
	@docker-compose exec -uroot php-fpm symfony run -d --watch=config,src,templates,vendor symfony console messenger:consume async

show_logs:
	$(info Make: Show symfony logs.)
	@docker-compose exec -uroot php-fpm symfony run symfony server:log

server_status:
	$(info Make: Swow server status (including queues).)
	@docker-compose exec -uroot php-fpm symfony server:status

show_filed_messages:
	$(info Make: Swow filed messages.)
	@docker-compose exec -uroot php-fpm symfony console messenger:failed:show

retry_filed_messages:
	$(info Make: Retry filed messages.)
	@docker-compose exec -uroot php-fpm symfony console messenger:failed:retry