#!/usr/bin/env bash

IN=$1
NAME=$2
OUT=$2

wget $IN -O ${NAME}.jpg
convert ${NAME}.jpg ${NAME}.png
rm ${NAME}.jpg
./clean.sh ${NAME}.png 150 150

