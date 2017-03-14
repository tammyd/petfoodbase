#!/usr/bin/env bash

RED='\033[0;31m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color
DIR=${PWD##*/}

#CMD="convert $1 -bordercolor none -border 50 temp_$1"
#printf "$PURPLE$CMD $NC\n";
#eval $CMD

#CMD="convert temp_$1 -trim +repage $1"
#printf "$PURPLE$CMD $NC\n";
#eval $CMD

CMD="rm temp_$1"
printf "$PURPLE$CMD $NC\n";
eval $CMD

CMD="convert $1 -resize 800x800\> 800_$1"
eval $CMD
printf "$PURPLE$CMD $NC\n";

CMD="convert $1 -shave 5x5\> 800_$1"
eval $CMD
printf "$PURPLE$CMD $NC\n";

CMD="mv 800_$1 $1"
eval $CMD
printf "$PURPLE$CMD $NC\n";

CMD="convert $1 -resize 150x150\> 150_$1"
eval $CMD
printf "$PURPLE$CMD $NC\n";


printf "/img/products/$DIR/150_$1\n" | pbcopy
printf "/img/products/$DIR/150_$1\n"

#pad rose: image with transparency as test image:
#convert rose: -bordercolor none -border 50 rose_a.png

#trim transparency
#convert rose_a.png -trim +repage rose_a_trim.png
