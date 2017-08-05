#!/usr/bin/env bash
docker build -t gone/redis-sync:latest .
docker push gone/redis-sync:latest