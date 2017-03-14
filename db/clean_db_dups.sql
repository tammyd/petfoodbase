delete from catfood where id not in (select min(id) from catfood group by source)

select * from catfood where id not in (select min(id) from catfood group by brand,flavor,protein,fat,fibre,moisture,ash,source)