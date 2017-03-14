(function() {
    var links = document.getElementsByClassName('amazon-product');

    _.forEach(links, function(link) {
        link.onclick = function (event) {

            //console.log("Amazon product click: " +  link.dataset.type  + " : " + link.dataset.label);

            ga('send', 'event', 'Amazon Click', link.data.type, link.data.label);
        };
    });

})();
