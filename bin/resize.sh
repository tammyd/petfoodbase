#!/usr/bin/env bash

RED='\033[0;31m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

printf "$PURPLE convert $2 -resize 150x150\> 150_$2$NC\n";

printf "$RED/img/products/$1/150_$2$NC\n";


CREATE TRIGGER catfood_update after update on catfood
begin
update catfood_search set brand = lower(new.brand), flavor = lower(new.flavor), ingredients = lower(new.ingredients) where id=new.id;
end

CREATE TRIGGER timestamp_update after update on catfood
for each row WHEN new.updated < old.updated
begin
update catfood set updated = current_timestamp where id=new.id;
end

Triton 6.8L EFI V-10
