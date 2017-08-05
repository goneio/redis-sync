#!/usr/bin/env bash
docker build -t gone/redis-sync:armhf .
docker push gone/redis-sync:armhf