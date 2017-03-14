#!/usr/bin/env bash

IN=$1
WIDTH=$2
HEIGHT=$3
OUT="${2}_${1}"
SIZE="$WIDTHx$HEIGHT"
DIR=${PWD##*/}
CMD="convert $IN -trim  -resize ${WIDTH}x${HEIGHT} -background transparent -gravity center -extent ${WIDTH}x${HEIGHT} $OUT"
echo $CMD
eval $CMD

CMD="convert $IN -trim  -background transparent -gravity center $IN"
echo $CMD
eval $CMD

printf "/img/products/$DIR/150_$1\n" | pbcopy
printf "/img/products/$DIhR/$OUT\n"
