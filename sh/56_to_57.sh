#!/usr/bin/env bash
# Performs some upgrades from 5.6 to 5.7 as per https://laravel.com/docs/5.7/upgrade
mkdir -p storage/framework/cache/data
cp storage/framework/cache/.gitignore storage/framework/cache/data/.gitignore
echo "*
!data/
!.gitignore" >> storage/framework/cache/.gitignore