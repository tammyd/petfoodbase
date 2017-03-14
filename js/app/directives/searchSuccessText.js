app = angular.module("searchSuccessText", []);

app.directive('filterDisplayString', ['SearchFilters', function(SearchFilters) {

    return {
        restrict: 'A',
        require: [],
        controller: ['$scope', function($scope) {
            $scope.getFilterDisplayString = function() {
                var text = "";
                var foodType = SearchFilters.getFoodTypeShown();
                var productType = "";
                if (foodType == 'wet') {
                    productType = "<strong>wet food</strong> products";
                } else if (foodType == 'dry') {
                    productType = "<strong>dry food</strong> products";
                } else {
                    productType = "products";
                }

                if (SearchFilters.length()) {

                    text = _.reduce(SearchFilters.getBrandFilters(), function(output, object, index, list) {
                        if (index == list.length-1 && list.length > 1) {
                            return output + " </b>and<b> " + object.name;
                        } else if (index > 0) {
                            return output + ", " + object.name;
                        } else {
                            return object.name;
                        }
                    }, "");

                    text = "Results limited to " + productType + " by <b>" + text + ".</b>";
                } else if (foodType != 'all') {
                    text = "Results limited to " + productType + ".";
                }

                return text;
            }
        }],
        template: '<span ng-bind-html="getFilterDisplayString()"></span>'
    }
}]);

app.directive('searchCountTextWithoutText', function() {

    return {
        restrict: 'A',
        require: ['^pagination', '^searchData', '^display'],
        controller: ['$scope', function($scope) {

        }],
        template: '<span ng-show="display.showList">{{ pagination.start + 1 }} - {{ pagination.end }} of </span>{{ searchData.results.length }} results.'


    }

});


app.directive('searchCountTextWithText', function() {

    return {
        restrict: 'A',
        require: ['^pagination', '^searchData', '^display'],
        controller: ['$scope', function($scope) {
        }],
        template: '<span ng-show="display.showList">{{ pagination.start + 1 }} - {{ pagination.end }} of </span>' +
            '{{ searchData.results.length }} results for "<strong>{{ searchData.searchedText }}</strong>".'


    }

});


app.directive('searchSuccessText', function() {

    return {
        restrict: 'A',
        require: ['^pagination', '^searchData', '^display'],
        controller: ['$scope', function($scope) {
            $scope.hasSearchText = function(searchData) {
                if (searchData.searchedText) {
                    return searchData.searchedText.length > 0;
                } else {
                    return false;
                }
            }
        }],
        template:
            '<span ng-show="hasSearchText(searchData)"><span search-count-text-with-text></span></span>' +
                '<span ng-show="!hasSearchText(searchData)"><span search-count-text-without-text></span></span> <br/>' +
                '<span filter-display-string></span>'


    }
});