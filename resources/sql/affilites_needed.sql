SELECT catfood.id, brand, flavor, source, asin, chewy  FROM catfood left join shop on catfood.id = shop.id  where asin is null or chewy is null


