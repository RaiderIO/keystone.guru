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

# ### Extract data from challenge_mode_runs and ingest it in the database/Opensearch
# SELECT challenge_mode_run_data, *
# FROM `challenge_mode_run_data`
# INNER JOIN `challenge_mode_runs` on challenge_mode_run_data.challenge_mode_run_id = challenge_mode_runs.id
# WHERE dungeon_id IN (
# "114",
# "112",
# "137",
# "111",
# "109",
# "35",
# "20",
# "25"
# )
# SELECT * FROM `challenge_mode_runs` WHERE dungeon_id IN ( "114", "112", "137", "111", "109", "35", "20", "25" )

# # Put the .sql data files in docker-compose/data/
# docker exec -it keystone.guru-db-combatlog /bin/bash
# mysql -u homestead -psecret keystone.guru.combatlog < /tmp/data/challenge_mode_run_data.sql

# # Take all the challenge mode run data, correct it, and insert it into the database
# php artisan challengemoderundata:convert
# php artisan combatlogevent:opensearch

# # or to populate Opensearch while converting
# php artisan challengemoderundata:convert --saveToOpensearch

