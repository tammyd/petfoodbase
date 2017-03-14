// https://www.christianheilmann.com/2015/12/25/detecting-adblock-without-an-extra-http-overhead/ 
(function(){
    var test = document.createElement('div');
    test.innerHTML = '&nbsp;';
    test.className = 'adsbox';
    document.body.appendChild(test);
    window.setTimeout(function() {
        if (test.offsetHeight === 0) {
            document.body.classList.add('adblock');

            var ads = document.getElementsByClassName("ad");
            _.each(ads, function(e) {
                e.className += " ad-blocked";

            });

            var replacements = document.getElementsByClassName("ad-replacement");
            _.each(replacements, function(e) {
                e.className += " ad-replacement-shown";
            });

            ga('send', 'event', 'Ads', 'Blocked', "Yes", {
                nonInteraction: true
            });
        } else {
            ga('send', 'event', 'Ads', 'Blocked', "No", {
                nonInteraction: true
            });
        }
        test.remove();
    }, 100);
})();
