history -s 'php artisan combatlog:extractdata tmp'
history -s 'php artisan combatlog:splitchallengemode tmp'
history -s 'php artisan combatlog:splitzonechange tmp'
history -s 'php artisan combatlog:outputcombatlogroutejson tmp/'
history -s 'php artisan combatlog:outputresultevents'
history -s 'php artisan combatlog:ingestcombatlogroutejson tmp/'
history -s 'php artisan challengemoderundata:convert'
history -s 'php artisan environment:update'
history -s 'php artisan handlebars:refresh'
history -s 'php artisan localization:sync en_US '
history -s 'php artisan mapicon:generateitemicons'
history -s 'php artisan mapping:save'
history -s 'php artisan mdt:importmapping '
history -s 'php artisan wowhead:fetchdisplayids '
history -s 'php artisan wowhead:fetchhealth '
history -s 'php artisan wowhead:fetchmissingspellicons'
history -s 'php artisan wowhead:fetchspelldata '
history -s 'php artisan wowhead:refreshdisplayids'
history -s 'php artisan l5-swagger:generate --all && php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"'
history -s './vendor/bin/phpunit -c phpunit.xml'
history -s './refresh_db_seed.sh'

# Prevent composer complaining about this if you got it symlinked
git config --global --add safe.directory /var/www/vendor/nnoggie/mythicdungeontools
git config --global --add safe.directory /var/www/vendor/nnoggie/mdt-legacy
git config --global --add safe.directory /var/www/vendor/wotuu/keystone.guru.deployer

# # Take combat logs, extract their data, ingest them in the database and then in Opensearch:
# php artisan combatlog:extractdata tmp
# # One combat log can contain multiple runs, split them up
# php artisan combatlog:splitchallengemode tmp
# # Convert to ARC-able .json bodies
# php artisan combatlog:outputcombatlogroutejson tmp
# # Create routes from these combat logs (push through ARC, and create ChallengeModeRunData objects from it)
# php artisan combatlog:ingestcombatlogroutejson tmp
# # Convert ChallengeModeRunData to CombatLogEvents and insert them into Database
# php artisan challengemoderundata:convert
# # Insert the combat log events into Opensearch
# php artisan combatlogevent:opensearch
