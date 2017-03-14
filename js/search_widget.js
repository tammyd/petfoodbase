(function() {
    var link = document.getElementById('searchLink');
    var input = document.getElementById('searchInput');
    try {
        input.onchange = input.onkeyup = function (event) {
            link.search = '?search=' + encodeURIComponent(input.value);
            if (event.keyCode == 13) {
                link.click();
            }
        };
    } catch (e) {
        //do nothing, there's a typeerror error when the new page loads that annoyed me
    }
})();
