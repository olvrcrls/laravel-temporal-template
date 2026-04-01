#!/bin/sh
# wait-for-temporal.sh

set -e

cmd="$@"

until temporal operator cluster health --address temporal:7233; do
  >&2 echo "Temporal cluster is unavailable - sleeping"
  sleep 10
done

# Ensure namespaces exist
# Note: Commented due to other application is using 'default'
temporal operator namespace create --namespace default --address temporal:7233 || true
temporal operator namespace create --namespace laravelTemporal --address temporal:7233 || true

>&2 echo "Temporal is up - executing command"
exec $cmd
