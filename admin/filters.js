app.filter('slice', function() {

	return function(data, offset, limit) {
		if (!(data instanceof Array)) {
			return data;
		}
		return data.slice(offset).slice(0, limit);
	}

});

app.filter('toArray', function () {
    return function (obj) {
        if (!(obj instanceof Object)) {
            return obj;
        }

        return Object.keys(obj).map(function (key) {
            return Object.defineProperty(obj[key], '$key', {__proto__: null, value: key});
        });
    }
});

app.filter('ucf', function() {
  return function(word) {
    return word.substring(0,1).toUpperCase() + word.slice(1).replace('_', ' ');
  }
});


app.filter('toNum', function() {
  return function(input) {
    return parseInt(input, 10);
  }
});

app.filter('truncate', function () {
	return function (input, chars, breakOnWord) {
		if (isNaN(chars)) return input;
		if (chars <= 0) return '';
		if (input && input.length > chars) {
			input = input.substring(0, chars);

			if (!breakOnWord) {
				var lastspace = input.lastIndexOf(' ');
				//get last space
				if (lastspace !== -1) {
					input = input.substr(0, lastspace);
				}
			}else{
				while(input.charAt(input.length-1) === ' '){
					input = input.substr(0, input.length -1);
				}
			}
			return input + '...';
		}
		return input;
	};
})

.filter('inArray', function($filter){
	return function(list, sourceArray, negative){
		if(sourceArray){
			return $filter("filter")(list, function(listItem){
				if (negative) { 
					return sourceArray.indexOf(listItem) === -1;
				} else { 	
					return sourceArray.indexOf(listItem) != -1;
				}
			});
		} else {
			return list;
		}
	};
});
