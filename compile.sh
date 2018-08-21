#!/usr/bin/env bash
tput setaf 2;
echo "Compiling..."
tput sgr0;
npm run dev -- --env.full true