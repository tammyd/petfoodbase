
//Adapted from http://plnkr.co/edit/P4cAm2AUGG36nejSjOpY?p=preview

app = angular.module("sort", []);

app.directive("sort", function() {
    return {
        restrict: 'A',
        transclude: true,
        template :
            '<a ng-click="onClick()">'+
                '<span ng-transclude></span>'+
                '<i class="fa sort-icon"  ng-class="{\'fa-sort-desc\' : order === by && !reverse,  \'fa-sort-asc\' : order===by && reverse, \'invisible\' : order!==by}"></i>'+
                '</a>',
        scope: {
            order: '=',
            by: '=',
            reverse : '='
        },
        link: function(scope, element, attrs) {
            scope.onClick = function () {
                if( scope.order === scope.by ) {
                    scope.reverse = !scope.reverse
                } else {
                    scope.by = scope.order ;
                    scope.reverse = false;
                }
            }
        }
    }
});

app.directive("sorting", function() {
    return {
        restrict: 'AE',
        transclude: false,
        replace: true,
        template :
            '<button type="button" class="{{ class }}" ng-click="onClick()">'+
            '{{text}}'+
            '<i class="fa sort-icon"  ng-class="{\'fa-sort-desc\' : order === by && !reverse,  \'fa-sort-asc\' : order===by && reverse, \'invisible\' : order!==by}"></i>'+
            '</button>',
        scope: {
            order: '=',
            by: '=',
            reverse : '='
        },
        link: function(scope, element, attrs) {
            scope.class = attrs.class;
            scope.text = attrs.text;
            scope.onClick = function () {
                if( scope.order === scope.by ) {
                    scope.reverse = !scope.reverse
                } else {
                    scope.by = scope.order ;
                    scope.reverse = false;
                }
            }
        }
    }
});