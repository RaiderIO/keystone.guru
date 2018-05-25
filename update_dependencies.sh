#!/usr/bin/env bash
echo "Updating npm..."
npm update
echo "Updating composer..."
composer update
echo "Regenerating IDE Helper..."
./ide_helper_regen.sh
echo "Refreshing autoload..."
./refresh_autoload.sh