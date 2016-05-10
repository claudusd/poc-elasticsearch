#!/usr/bin/env bash

docker run -d --name=poc-elasticsearch -p 9200:9200 -p 9300:9300 elasticsearch:2.3.2 
