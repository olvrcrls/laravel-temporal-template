#!/bin/bash
# temporal-worker.sh

if php artisan temporal:work --workers=2; then
    echo "Temporal workers are up"
else
    echo "Project is not set up. Please build image"
    exit 1
fi
