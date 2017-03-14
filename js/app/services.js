var catFoodServices = angular.module('catFoodServices', ['ngResource']);

catFoodServices.factory('CatFood', ['$resource',
    function($resource){
        return $resource('data/catfood/:search', {}, {
            query: {
                method:'GET',
                isArray:true,
                cache: true
            }

        });
    }]
);


catFoodServices.factory('CatFoodBrands', ['$resource',
    function($resource){
        return $resource('data/catfood/brands', {}, {
            query: {
                method:'GET',
                isArray:true,
                cache: true
            }

        });
    }]
);

catFoodServices.factory('SearchText', ['$rootScope', function ($rootScope) {

    var searchText = "";

    return {
        update: function(text) {
            searchText = text;
            $rootScope.$emit("SearchText:update", text);
        },
        get: function() { return searchText; }
    };
}
]);


catFoodServices.factory('SearchFilters',  ['$rootScope', function ($rootScope) {

    var brandFilters = [];
    var wetDryDisplay = 0;

    return {
        toggleBrand: function(brand) {
            if (_.indexOf(brandFilters, brand)==-1) {
                brandFilters.push(brand);
            } else {
                brandFilters = _.without(brandFilters, brand);
            }

            return brandFilters;
        },
        addBrand: function(brand) {
            if (_.indexOf(brandFilters, brand)==-1) {
                brandFilters.push(brand);
            }
        },
        removeBrand: function(brand) {
            brandFilters = _.without(brandFilters, brand);
        },
        clearBrands: function() { brandFilters = []; },
        getBrandFilters: function() { return brandFilters; },
        getBrandQueryParam: function() {
            var param = "";
            _.each(brandFilters, function(element, index, list) {
                if (param.length > 0) {
                    param += ",";
                }
                param += element.brand
            });

            return param;

        },
        length: function() {
            return brandFilters.length;
        },
        setFoodTypeShown: function(value) {
            if (value == 0 || value == 'all') {
                wetDryDisplay = 'all';
            } else if (value == 1 || value == 'wet' ) {
                wetDryDisplay = 'wet';
            } else if (value == 2 || value == 'dry' )  {
                wetDryDisplay = 'dry';
            } else {
                wetDryDisplay = 'all';
            }

        },
        getFoodTypeShown: function() {
            return wetDryDisplay;
        }



    }


}]);