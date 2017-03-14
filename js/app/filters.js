var catFoodFilters = angular.module('catFoodFilters', []);

catFoodFilters.filter('offset', function() {
    return function(input, start) {
        input = input || "";

        start = parseInt(start, 10);
        return input.slice(start);
    };
});

