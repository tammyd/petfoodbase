// http://embed.plnkr.co/CMqxIm/preview
angular.module('truncate', [])
    .filter('characters', function() {
        return function(input, chars, breakOnWord) {
            if (isNaN(chars)) return input;
            if (chars <= 0) return '';
            if (input && input.length >= chars) {
                input = input.substring(0, chars);

                if (!breakOnWord) {
                    var lastspace = input.lastIndexOf(' ');
                    //get last space
                    if (lastspace !== -1) {
                        input = input.substr(0, lastspace);
                    }
                } else {
                    while (input.charAt(input.length - 1) == ' ') {
                        input = input.substr(0, input.length - 1);
                    }
                }
                return input + '...';
            }
            return input;
        };
    })
    .filter('words', function() {
        return function(input, words) {
            if (isNaN(words)) return input;
            if (words <= 0) return '';
            if (input) {
                var inputWords = input.split(/\s+/); // split on 1 or more spaces
                if (inputWords.length > words) {
                    input = inputWords.slice(0, words).join(' ') + '...';
                }
            }
            return input;
        };
    }).filter('mixed', function() {
        return function(input, numWords, numChars) {

            if (input) {
                if (isNaN(numChars)) return input;
                if (numChars <= 0) return '';
                if (input && input.length >= numChars) {
                    input = input.substring(0, numChars);
                    input = input + '...';
                }

                var inputWords = input.split(/\s+/);
                if (inputWords.length > numWords) {
                    input = inputWords.slice(0, numWords).join(' ') + '...';
                }

            } // end if input

            return input;
        } // end return
    });