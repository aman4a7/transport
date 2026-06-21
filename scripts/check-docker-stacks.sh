#!/usr/bin/env bash
# Warns if both the Sail dev stack and the production stack appear to be running.
set -e

SAIL_RUNNING=$(docker ps --filter "name=laravel.test" --format '{{.Names}}' | wc -l)
PROD_RUNNING=$(docker ps --filter "name=transport-php" --format '{{.Names}}' | wc -l)

if [ "$SAIL_RUNNING" -gt 0 ] && [ "$PROD_RUNNING" -gt 0 ]; then
  echo "WARNING: Both the Sail dev stack and the production stack appear to be running."
  echo "    They share default ports 54320 (PostgreSQL) and 63790 (Redis) and will conflict."
  echo "    Stop one before starting the other. See docs/adr/0006-dual-docker-stack-dev-vs-production.md"
  exit 1
fi

echo "No Docker stack conflict detected."
