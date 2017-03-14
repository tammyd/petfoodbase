#!/usr/bin/env bash

IN=$1
OUT=$2
#TMP2="working2.png"
#CMD="convert $1 -trim cups-chicken-salmon.png working.png"
CMD="convert $IN -trim  -resize 200x133 -background transparent -gravity center -extent 200x133 $OUT"
echo $CMD
eval $CMD