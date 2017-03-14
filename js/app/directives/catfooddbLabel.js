app = angular.module("catfoodDbLabel", []);

app.directive('catfoodDbLabel', function() {
    return {
        restrict: 'AE',
        transclude: 'true',
        template: '<span class="catfooddb-label">CatFoodDBScore&trade;</span>'
    };
});
