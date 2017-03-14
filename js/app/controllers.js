

var catFoodControllers = angular.module('catFoodControllers', ['ui.bootstrap']);


catFoodControllers.controller('SearchCtrl',
    ['$scope', '$rootScope', '$location', '$routeParams', '$filter', 'CatFood', 'CatFoodBrands', 'SearchText', 'SearchFilters',
    function($scope, $rootScope, $location, $routeParams, $filter, CatFood, CatFoodBrands, SearchText, SearchFilters) {

        var WET_DRY_MOISTURE_PERCENT = 20;

        $rootScope.$on('SearchText:update', function(text) {
            $scope.doSearch();
        });

        $scope.doSearch = function() {
            $scope.searchData.searchedText  = $scope.getSearchedText();
            $scope.search();
        };

        $scope.pageChanged = function() {
            $scope.pagination.start = ($scope.pagination.currentPage - 1) * $scope.pagination.itemsPerPage;
            $scope.pagination.end = Math.min($scope.pagination.start + $scope.pagination.itemsPerPage, $scope.pagination.totalItems);
        };

        $scope.changeLength = function(product, length) {
            product.ingredientLength = length;
        };

        $scope.enableHiddenSearchWarning = function() {
            $scope.display.showWarning = false;
            $('#search-warning').show();
        };

        $scope.disableHiddenSearchWarning = function() {
            $scope.display.showWarning = false;
            $('#search-warning').hide();
        };


        $scope.search = function () {
            $scope.enableHiddenSearchWarning();
            $rootScope.searchFinished = false;
            var text = SearchText.get() || "";

            $location.search('search', text);
            $scope.searchCleared = false;
            var brands = SearchFilters.getBrandQueryParam();
            if (text.length || brands.length) {
                $rootScope.firstSearchStarted = true;
                $scope.searchData.searchedText = text;
                CatFood.get({search: text, brands: brands}, function(result) {
                    $scope.searchData.apiResults = result.items;
                    var filteredItems = _.filter($scope.searchData.apiResults, $scope.wetDryFilter);

                    $scope.searchData.error = false;
                    $scope.searchData.results = filteredItems;
                    $scope.pagination.totalItems = filteredItems.length;
                    $scope.pagination.currentPage = 1;
                    $scope.initResults();
                    $scope.pageChanged();
                    $rootScope.searchFinished = true;

                }, function(error) {
                    //error happened, zero out the results
                    $scope.searchData.apiResults = [];
                    $scope.searchData.results = [];
                    $scope.searchData.error = true;
                    $rootScope.searchFinished = true;
                });

            } else {
                $scope.searchData.results = [];
                $rootScope.searchFinished = true;

                if ($rootScope.firstSearchStarted) {
                    $scope.display.showWarning = true;
                }
            }
        };

        $scope.wetDryFilter= function(catfood) {
            if (typeof $scope.minMoisture === 'undefined') {
                $scope.minMoisture = WET_DRY_MOISTURE_PERCENT;
            }
            if (typeof $scope.maxMoisture === 'undefined') {
                $scope.maxMoisture = 100;
            }
            var moisture = catfood.percentages.wet.moisture;

            var show = ((moisture >= $scope.minMoisture) && (moisture <= $scope.maxMoisture));

            return show;

        };

        $scope.updateWetDryFilter = function(value, skipUpdate) {
            SearchFilters.setFoodTypeShown(value);
            value = SearchFilters.getFoodTypeShown();
            if (value == 'all') {
                $scope.display.showAllFood = true;
                $scope.display.showDryFoodOnly = false;
                $scope.display.showWetFoodOnly = false;
                $scope.minMoisture = 0;
                $scope.maxMoisture = 100;
            } else if (value == 'wet') {
                //show only wet
                $scope.display.showAllFood = false;
                $scope.display.showDryFoodOnly = false;
                $scope.display.showWetFoodOnly = true;
                $scope.minMoisture = 20;
                $scope.maxMoisture = 100;
            } else if (value == 'dry') {
                //show only dry
                $scope.display.showAllFood = false;
                $scope.display.showDryFoodOnly = true;
                $scope.display.showWetFoodOnly = false;
                $scope.minMoisture = 0;
                $scope.maxMoisture = 20;
            }

            if ($scope.searchData && $scope.searchData.apiResults) {
                $scope.updateSearchFilter();
            }
        };

        $scope.updateSearchFilter = function() {
            var filteredItems = _.filter($scope.searchData.apiResults, $scope.wetDryFilter);

            $scope.searchData.error = false;
            $scope.searchData.results = filteredItems;
            $scope.pagination.totalItems = filteredItems.length;
            $scope.pagination.currentPage = 1;
            $scope.initResults();
            $scope.pageChanged();
            $rootScope.searchFinished = true;
        };

        $scope.initResults = function() {
            angular.forEach($scope.searchData.results, function(value, key) {
                $scope.searchData.results[key].ingredientLength = $scope.data.minIngredientLength;
                if (!$scope.searchData.results[key].imageUrl) {
                    if ($scope.searchData.results[key].percentages.wet.moisture <= 20) {
                        $scope.searchData.results[key].imageUrl = "/img/icons/dryfood2_white.png";
                    } else {
                        $scope.searchData.results[key].imageUrl = "/img/icons/wetfood2_white.png";
                    }
                }
            });
        };

        $scope.navSearch = function() {
            var text = $scope.searchData.navSearchText.trim();
            $location.path("/");
            $scope.searchData.navSearchText = "";
            $scope.updateSearch(text);
        };

        $scope.getSearchedText = function() {
            return SearchText.get();
        };


        $scope.updateSearch = function(text) {
            $scope.searchData = $scope.searchData || {};

            $scope.searchData.searchText = text;
            SearchText.update(text);
        };

        $scope.clearSearch = function() {
            $scope.searchCleared = true;
            $scope.searchData.searchText = "";
            $scope.searchData.results = [];
            $scope.pagination.totalItems = 0;
            $scope.pagination.currentPage = 1;
            $location.search('search', "");
        };
        $scope.toggleDropdown = function($event) {
            $event.preventDefault();
            $event.stopPropagation();
            $scope.display.toggle.isopen = !$scope.display.toggle.isopen;
        };

        $scope.parseSearch = function() {

            var search = $location.search()['search'];
            $scope.parseFilter();

            if ($scope.brandFilter || (search && ($rootScope.searchData))) {
                $scope.updateSearch(search);
            }
        };


        $scope.toggleBrandDropdown = function($event) {
            $event.preventDefault();
            $event.stopPropagation();
            $scope.brand.toggle.isopen = !$scope.brand.toggle.isopen;
        };


        $scope.filterToggle = function(brand) {

            $scope.brandFilter = $scope.brandFilter || [];
            var idx = $scope.brandFilter.indexOf(brand.brand);

            // is currently selected
            if (idx > -1) {
                $scope.brandFilter.splice(idx, 1);
                SearchFilters.removeBrand(brand);
            }

            // is newly selected
            else {
                $scope.brandFilter.push(brand.brand);
                SearchFilters.addBrand(brand);
            }

            var url = $scope.getFilterUrl();
            $location.search('brand', url);

        };

        $scope.clearBrands = function() {

            _.each(SearchFilters.getBrandFilters(), function(brand) {
                SearchFilters.removeBrand(brand);
            });
            $scope.brandFilter = [];

            var url = $scope.getFilterUrl();
            $location.search('brand', url);
        };

        $scope.clearFilter = function() {
            $scope.filter = [];
        };

        $scope.getFilterUrl = function() {
            return SearchFilters.getBrandQueryParam();
        };

        $scope.parseFilter = function() {
            var urlBrands = $location.search()['brand'];
            SearchFilters.clearBrands();

            //if brand in in $rootScope.brandList, then add brand
            var brands = "";
            if (urlBrands) {
                brands = urlBrands.split(',');
            }
            $scope.brandFilter = brands;

            _.each($rootScope.brandList, function(element, index, list) {
                if (_.contains(brands, element.brand)) {
                    SearchFilters.addBrand(element);
                }
            });

        };

        $scope.showResultSuccessMessage = function() {

            var resultSuccess = true;

            if (!$scope.searchFinished) {
                resultSuccess = false;
            }

            if (!$scope.searchData) {
                resultSuccess = false;
            }

            $scope.searchData.searchedText = $scope.searchData.searchedText || "";

            var brands = SearchFilters.getBrandQueryParam();
            if (!$scope.searchData.searchedText.length && !brands.length) {
                resultSuccess = false;
            }

            resultSuccess = resultSuccess && (
                $scope.searchData.results.length > 0 &&
                !$scope.searchData.error
            );

            //console.log("SUCCESS Message? " + resultSuccess);
            return resultSuccess;

        };

        $scope.showResultEmptyMessage = function() {
            var resultEmpty = false;

            var brands = SearchFilters.getBrandQueryParam();

            resultEmpty = $scope.searchData &&
                $scope.searchFinished &&
                !$scope.searchData.error &&
                !$scope.searchCleared &&
                $scope.searchData.results.length == 0 &&
                (brands.length || ($scope.searchData.searchedText.length && $scope.searchData.results.length == 0))

            //console.log("EMPTY Message? " + resultEmpty);

            return resultEmpty;
        };

        $scope.showWarningMessage = function() {
            var resultWarning =  $scope.display.showWarning;

            //console.log("WARNING Message? " + resultWarning);

            return resultWarning;
        };

        $scope.showResultErrorMessage = function() {
            var resultError = false;

            resultError = $scope.searchData &&
                $scope.searchFinished &&
                $scope.searchData.searchedText.length &&
                $scope.searchData.error;

            //console.log("RESULT Message? " + resultError);

            return resultError;
        };

        $scope.showResultInitMessage = function() {
            var resultInit = !(
                $scope.showResultSuccessMessage() ||
                    $scope.showResultEmptyMessage() ||
                    $scope.showResultErrorMessage()
                );

            resultInit = resultInit && !$scope.showSpinner();

            //console.log("INIT Message? " + resultInit);

            return resultInit;
        };

        $scope.showIntroMessage = function() {

            return ($scope.showWarningMessage()) ||
                !($scope.showResultSuccessMessage() ||
                $scope.showResultEmptyMessage() ||
                $scope.showResultErrorMessage()  ||
                $scope.showSpinner() ||
                $scope.hasSearchResults() ||
                $scope.showWarningMessage());
        };

        $scope.showSpinner = function() {
            return !$scope.searchFinished && $scope.firstSearchStarted;
        };

        $scope.showListSearchResults = function() {
            return $scope.display.showList && $scope.searchData.results &&
                $scope.searchData.results.length > 0 &&
                $scope.searchFinished;
        };

        $scope.closeLeftNav = function() {
            $scope.nav.brandNav.open = false;
            $scope.nav.wetdryNav.open = false;
            $scope.nav.popularNav.open = false;
        };

        $scope.hasSearchResults = function() {
            return $scope.searchData &&
                $scope.searchData.results &&
                $scope.searchData.results.length > 0
                && $scope.searchFinished;
        };

        $scope.resetSearch = function() {
            $scope.clearBrands();
            $scope.clearSearch();
            $scope.disableHiddenSearchWarning();
        };

        $scope.init = function() {


            $scope.display = {
                showWet: false,
                showList: true,
                showTable: false,
                toggle: {
                    isopen: false
                },
                filterOpen: true
            };
            $scope.updateWetDryFilter('all', false);

            $scope.parseSearch();

            if ($rootScope.initialized === true) {
                return;
            }

            $scope.filter = [];

            $scope.data = {
                minIngredientLength: 80,
                maxIngredientLength:10000
            };

            $scope.pagination = {
                'currentPage': 1,
                'itemsPerPage': 10,
                'maxSize': 4
            };

            CatFoodBrands.get({}, function(result) {
                $rootScope.brandList = result.items;
            });


            $rootScope.searchFinished = false;
            $rootScope.firstSearchStarted = false;


            if (typeof $rootScope.searchData === 'undefined') {
                $rootScope.searchData = {
                    'results': [],
                    'searchText': "",
                    'navSearchText': "",
                    'error': false
                };
            };

            $rootScope.initialized = true;

        };


        $scope.init();

    }]);


catFoodControllers.controller('NavCtrl', ['$scope', '$location', function ($scope, $location) {
    $scope.isActive = function (viewLocation) {
        return viewLocation === $location.path();
    };
}]);





