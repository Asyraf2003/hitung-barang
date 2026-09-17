APP_NAME ?= hitung-barang-cpanel
APP_DIR_NAME ?= hitung-barang
PUBLIC_DIR_NAME ?= public_html
DEPLOY_DIR ?= deploy-package
ENV_FILE ?= .env.production
SITE_URL ?=

.PHONY: deploy

deploy:
	@APP_NAME="$(APP_NAME)" \
	APP_DIR_NAME="$(APP_DIR_NAME)" \
	PUBLIC_DIR_NAME="$(PUBLIC_DIR_NAME)" \
	DEPLOY_DIR="$(DEPLOY_DIR)" \
	ENV_FILE="$(ENV_FILE)" \
	SITE_URL="$(SITE_URL)" \
	bash scripts/build-cpanel-package.sh
