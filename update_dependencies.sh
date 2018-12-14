#!/usr/bin/env bash
tput setaf 2;
echo "Updating npm..."
tput sgr0;
npm update

tput setaf 2;
echo "Installing npm packages..."
tput sgr0;
npm install

tput setaf 2;
echo "Fixing vulnerabilities..."
tput sgr0;
npm audit fix

tput setaf 2;
echo "Updating composer..."
tput sgr0;
composer update

./ide_helper_regen.sh

./refresh_autoload.sh

./compile.sh $1