<?php


namespace PetFoodDB\Command\Traits;


use Symfony\Component\Filesystem\Filesystem;

trait DBTrait
{

    protected function getDB($dbpath) {

        if (strpos($dbpath, 'sqlite') == 0) {
            $db = new \PDO($dbpath);
        } else {
            $db = new \PDO('sqlite:' . $dbpath);
        }
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $structure = new \NotORM_Structure_Convention(
            $primary = "id",
            $foreign = "id"
        );
        return new \PetFoodDB\Service\ExposedNotORM($db, $structure);
    }

    protected function getDBFilepath($name, $container)
    {
        $filename = $container->get('config')['app.db.dir'] . $name . ".sqlite";
        return $filename;
    }


    protected function dbExists($name, $container) {
        $fs = new Filesystem();
        $filename = $this->getDBFilepath($name, $container);
        if ($fs->exists($filename) && filesize($filename) > 0) {
            return true;
        }
        return false;

    }


    protected function createCatFoodDB($name, $container) {
        $filename = $this->getDBFilepath($name, $container);

        $db = $this->getDB($filename);
        $pdo = $db->getConnection();

        $sqlCommands = [];

        $sqlCommands[] = 'CREATE TABLE "catfood" (
"id" INTEGER PRIMARY KEY  NOT NULL ,
"brand" VARCHAR NOT NULL ,
"flavor" VARCHAR NOT NULL ,
"protein" NUMERIC NOT NULL  DEFAULT (0) ,
"fat" NUMERIC NOT NULL  DEFAULT (0) ,
"fibre" NUMERIC NOT NULL  DEFAULT (0) ,
"moisture" NUMERIC NOT NULL  DEFAULT (0) ,
"ash" NUMERIC NOT NULL  DEFAULT (0) ,
"asin" VARCHAR,
"imageUrl" VARCHAR DEFAULT (null) ,
"source" VARCHAR,
"updated" DATETIME DEFAULT (CURRENT_TIMESTAMP) ,
"ingredients" VARCHAR,
"discontinued" BOOL DEFAULT 0,
"catfood" VARCHAR DEFAULT catfood,
"parserClass" VARCHAR);';

        $sqlCommands[] = 'CREATE TABLE "shop" ("id" INTEGER PRIMARY KEY  NOT NULL  UNIQUE , "amazon" TEXT, "walmart" TEXT, "petsmart" TEXT, "petco" TEXT, "chewy" TEXT);';
        $sqlCommands[] = 'CREATE TABLE "prices" ("id" INTEGER PRIMARY KEY  NOT NULL ,"min" FLOAT DEFAULT (0) ,"max" FLOAT DEFAULT (0) ,"avg" FLOAT DEFAULT (0) ,"url" TEXT,"date" DATETIME);';

        $sqlCommands[] = 'CREATE VIRTUAL TABLE catfood_search using fts4(id, brand, flavor, ingredients, catfood, tokenize="porter");';
        $sqlCommands[] = 'insert into catfood_search(id, brand, flavor, ingredients) select  id, lower(brand), lower(flavor), lower (ingredients) FROM catfood;';
        $sqlCommands[] = "create trigger catfood_insert after insert on catfood
begin
insert into catfood_search(id, brand, flavor, ingredients, catfood) values (new.id, lower(new.brand), lower(new.flavor), lower(new.ingredients), 'catfood');
end;";
        $sqlCommands[] = 'create trigger catfood_update after update on catfood
begin
update catfood_search set brand = lower(new.brand), flavor = lower(new.flavor), ingredients = lower(new.ingredients) where id=new.id;
end;';

        $sqlCommands[] = 'CREATE trigger catfood_delete AFTER DELETE ON catfood BEGIN delete from catfood_search where id=old.id; END';
        $sqlCommands[] = 'CREATE TRIGGER catfood_delete_shop AFTER DELETE ON catfood BEGIN delete from shop where id=old.id; END';

        $sqlCommands[] = 'CREATE TABLE "analysis" (
        "id" INTEGER PRIMARY KEY  NOT NULL ,
"type" VARCHAR NOT NULL ,
"type_count" NUMERIC NOT NULL  DEFAULT (0) ,
"protein_sd" NUMERIC NOT NULL  DEFAULT (0) ,
"fat_sd" NUMERIC NOT NULL  DEFAULT (0) ,
"fibre_sd" NUMERIC NOT NULL  DEFAULT (0) ,
"moisture_sd" NUMERIC NOT NULL  DEFAULT (0) ,
"ash_sd" NUMERIC NOT NULL  DEFAULT (0) ,
"carbohydrates_sd" NUMERIC NOT NULL DEFAULT (0) ,
"calories_sd" NUMERIC NOT NULL DEFAULT (0) ,
"nutrition_rating" NUMERIC NOT NULL  DEFAULT (0) , 
"ingredients_rating" NUMERIC NOT NULL  DEFAULT (0))';
        
        foreach ($sqlCommands as $sql) {
            $pdo->query($sql);
        }

        return true;
    }

}
