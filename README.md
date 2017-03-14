# Cat Chowder

A php & AngularJs applications based on [Slim-Skeleton](https://github.com/codeguy/Slim-Skeleton) by Josh Lockhart,
 but with considerable customizations.

It also uses Sensio Labs' [Twig](http://twig.sensiolabs.org) template library, and [Bootstrap 3](http://getbootstrap.com)

## Requirements

[Composer](http://getcomposer.org/)

[node.js](http://nodejs.org/)

[Grunt](http://gruntjs.com)

## Install Composer

If you have not installed Composer, do that now. I prefer to install Composer globally in `/usr/local/bin`, but you may also install Composer locally in your current working directory. For this tutorial, I assume you have installed Composer locally.

<http://getcomposer.org/doc/00-intro.md#installation>

## Install node.js

Visit <http://nodejs.org/> and click the big green "Install" button.

## Install Grunt

`npm install -g grunt-cli`


## Install the Application

After cloning the app, cd into your newly created application

`cd [my-app-name]`

And run the following commands

`npm install`

`composer install`

`grunt build`

You'll want to:
* Point your virtual host document root to your new application's `public/` directory.
* Ensure `logs/` and `templates/cache` are web writeable.

## Config

Copy config/config.php.dist to config/config.php and update it with the needed values. You'll need your own amazon keys!

## Provided Grunt commands

`grunt`
Watches the less directory for changes, compiles less when any files in the less directory or subdirectory are modified and concats all javascript.


# Deployment

* is done by capistrano
* install it via a gem
* ```bundle install```
* ```cap staging deploy:setup_config```
* login to staging server and create /var/www/catchowder/.env file
* ```cap staging deploy```
* repeat for production when ready!

# To Change the domain's IP

* Update the IP in the appropriate deployment/env/[env].rb
* ssh into the box owned by the IP and update /var/www/catchowder/current/.env

# ToDo

## Link Building

* http://openlinkprofiler.org

What links to petfoodratings.org? https://en.wikipedia.org/wiki/Pet_food  has an external 404 link

## Pages

* ing

## Blog Post Ideas

* Amazon Cat Food Best Sellers
* High Calorie Cat Food
* Montmorillonite Clay In Cat Food
    * has high search volume, low competition
    
## Pages

* Ingredients Glossary
    'cat food ingredients' has good search volume

