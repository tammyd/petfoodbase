app = angular.module("paws", []);

app.directive('pawsScore', function() {
    return {
        restrict: 'AE',
        transclude: 'true',
        scope: {
            nutrition: '@',
            ingredient: '@'

        },
        controller: ['$scope', function($scope) {
            $scope.range = function(count){

                var ratings = [];

                for (var i = 0; i < count; i++) {
                    ratings.push(i)
                }

                return ratings;
            }

            $scope.getNutritionStar = function() {

                return '<i class="fa fa-paw nutrition-paws" aria-hidden="true"></i>';
            };

            $scope.getIngredientStar = function() {
                return '<i class="fa fa-paw ingredients-paws" aria-hidden="true"></i>';
            };
        }],
        template:   '<span ng-repeat="x in range(ingredient)" ng-bind-html="getIngredientStar();"></span>' +
                    '<span ng-repeat="x in range(nutrition)" ng-bind-html="getNutritionStar();"></span>'

    };
});
