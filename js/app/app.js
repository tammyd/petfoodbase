'use strict';
$(document).ready(function() {

    $('#catfoodInfo a').click(function (e) {
        e.preventDefault();
        $(this).tab('show');
    });

    
});

//https://github.com/twbs/bootstrap/issues/9013#issuecomment-23590508
$(document).on('click','.navbar-collapse.in',function(e) {
    if( $(e.target).is('a') ) {
        $(this).collapse('hide');
    }
});

$(document).on('click', '.dropdown-menu.dropdown-menu-form', function(e) {
    e.stopPropagation();
});



var catfoodApp = angular.module('catfoodApp', [
    'ngRoute',
    'ngSanitize',
    'catFoodControllers',
    'catFoodServices',
    'catFoodFilters',
    'truncate',
    'sort',
    'catfoodDbLabel',
    'searchSuccessText',
    'paws'
]);

catfoodApp.config(['$routeProvider', '$locationProvider',
    function($routeProvider, $locationProvider) {
        $locationProvider.html5Mode(true);
        $locationProvider.hashPrefix('!');
        $routeProvider.
            when('/', {
                templateUrl: '/templates/partials/search.html.twig',
                controller: 'SearchCtrl',
                reloadOnSearch: false
            }).
            when('/search', {
                templateUrl: '/templates/partials/search.html.twig',
                controller: 'SearchCtrl',
                reloadOnSearch: false
            })
    }]
);


$('.angular-dropdown').click(function(e) {
    e.preventDefault();
    e.stopPropagation();

    return false;
});


