app.directive('login', function($requests, AuthenticationService) {
	return {
		restrict: 'A',
		templateUrl:'login.html',
		scope: true,
		link: function(scope, element, attribs) { 
			scope.auth = AuthenticationService;
			scope.login  = { 
				user: '', password: ''
			};
			scope.do_login = function() { 
				element.find('input, textarea, select').trigger('input').trigger('change').trigger('keydown'); //have to do this to update model from autofill
				scope.auth.login(scope.login.user, scope.login.password);
			}
			scope.auth.checkLogin();
		}
	}
});

app.directive('logout', function(AuthenticationService) {
	return {
		restrict: 'A',
		templateUrl:'logout.html',
		scope: true,
		link: function(scope, element, attribs) { 
			scope.auth = AuthenticationService;
			scope.logout = function() { 
				scope.auth.logout();
			}
			//scope.$watch(AuthenticationService.logged_in, function() { 
			//	scope.logged_in = AuthenticationService.logged_in;
			//});
		}
	}
});

app.directive('navLink', function($data, AuthenticationService, $location) {
	return {
		restrict: 'A',
		scope: {
			action:'@',
			restricted:'@?',
		},
		transclude:true,
		replace:true,
		template:"<li ng-class='{disabled:!allowed, active: action == checkPath()}'><a ng-href='{{ allowed ? \"#/\"+action : \"\"}}' ng-disabled='action_access[action]' ng-transclude></a></li>",
		link: function(scope, element, attribs) { 
			scope.allowed = ! scope.restricted || AuthenticationService.user_type == scope.restricted;
			scope.checkPath = function() {
				var path = $location.path().replace(/^\//, ""); //([^\/]*).*$/, "$1");
				return path;
			}
		}
	}
});

app.directive('header', function($requests, $sce, breadcrumbs) {
	return {
		restrict: 'A',
		templateUrl:'header.html',
		scope: {
			title:'@',
			buttons:'=',
			extraHeader:'='
		},
		link: function(scope,element, attribs) {
			scope.breadcrumbs = breadcrumbs;
			scope.safeExtraHeader = $sce.trustAsHtml(scope.extraHeader);
			scope.doAction = function(action, index) {
				b = $(element.find('.btn')[index]);
				b.prepend("<span class='processing-spinner glyphicon glyphicon-refresh spin'></span> ");
				action();
			}
		}
	}
});

app.directive('actionButton', function() {
	return {
		restrict: 'A',
		scope: {
			actionButton:'='
		},
		link: function(scope,element, attribs) {
			element.bind('click', function(e) {
				attribs.$set('disabled', true);
				element.prepend("<span class='processing-spinner glyphicon glyphicon-refresh spin'></span> ");
			})

			scope.$watch('actionButton', function(n, o) {
				attribs.$set('disabled', null);
				$(element).find('.processing-spinner').remove();
			});

		}
	}
});

app.directive('collectionChooser', function($requests, $data, $timeout) {
	return {
		restrict: 'A',
		templateUrl:'collectionChooser.html',
		scope: {
			model:'=?',
			callback:'&?selectCollection',
			clear:'@'
		},
		link: function(scope,element, attribs) {	
			scope.data = $data;
			scope.value = '';
		
			scope.selectCollection = function() { 
				scope.model = scope.value.id;
				if (scope.callback) { 
					scope.callback({collection:scope.value});
				}
				if (scope.clear) { 
					$timeout(function() { 
						scope.value = '';
					});
				}
			}
			
			scope.checkBlank = function(value) {
				if (value == '') {
					scope.model = null;
					if (scope.callback) { 
						scope.callback();
					}
				}
			}

			scope.$watch('model', function() {
				if (angular.isDefined(scope.model) && scope.model != '') { 
					angular.forEach($data.collections, function(v,k) { 
						if (!scope.value && v.id == scope.model) { 
							scope.value = v;
						}
					});
				}
			});
		}
	}
});

app.directive('featuredDocs', function($requests) {
	return {
		restrict: 'A',
		templateUrl:'featuredDocs.html',
		scope: {
			documents:'=',
			limitCollectionId:'@',
		},
		link: function(scope,element, attribs) {
			scope.addFeaturedDoc = function(doc) { 
				var skip = 0;
				angular.forEach(scope.documents, function(v) { 
					if (v.DOCID == doc.id) {
						skip = 1;
					}
				});
				if (!skip) { 
					scope.documents.push({DOCID:doc.id, DESCRIPTION: doc.label, THUMBNAIL: doc.THUMBNAIL, TITLE: doc.label, DOC_ORDER:''});
				}
			}
			
			scope.removeFeaturedDoc = function(index) { 
				scope.documents.splice(index, 1);
			}
		}
	}
});
		
app.directive('itemSearch', function($requests, $search, $sce, $data, $download) {
	return {
		restrict: 'A',
		templateUrl:'itemSearch.html',
		scope: {
			docLimit:'@',
			selectAction:'=',
			nonDigitizedDefault:'@',
			limitCollectionId:'@',
			embedded: '=',
			page:'=?',
			count: '=?',
			itemType: '@itemSearch',
			searchType: '@?'
		},
		link: function(scope,element, attribs) {
			scope.search = $search;
			scope.data = $data;
			scope.options = {};
			scope.items = [];
			scope.selected = null;
			scope.digitized = 0;
			scope.isDoc = false;
			scope.static = {
				'location': true,
				'publisher': true,
				'organization': true,
				'description': true,
				'title': true,
				'collection_name': true,
				'date_range': true,
			};
			var action = '';
			var searchType = '';
			if (scope.itemType == 'document') {
				action =  'fetchDocuments';
				scope.filters = ['author', 'description', 'format', 'generation', 'keyword', 'location', 'publisher', 'producer', 'program', 'quality', 'subject', 'title'];
				scope.isDoc = true;
				if ( scope.searchType) {
					searchType = scope.searchType;
					$search.resetSearch(searchType);
				} else if (scope.embedded) {
					searchType = 'colRecordOpts';
				} else {
					searchType = 'recordOpts';
				}
			} else {
				action = 'fetchCollections';
				scope.filters = ['collection_name', 'date_range', 'description', 'keyword', 'organization', 'subject'];
				scope.isDoc = false;
				searchType = 'collectionOpts';
			}
			scope.options = $search[searchType];
      if (scope.limitCollectionId != '' && scope.limitCollectionId != 'new') { 
	      scope.options.collection = scope.limitCollectionId;
      }

      scope.fetchItems = function() {
      	$search.fetchItems(searchType).then(function(res){
      		scope.items = res;
      	})
      }

			scope.selectCollection = function(collection) { 
				if (collection) { 
					scope.options.collection = collection.id;
				} else { 
					scope.options.collection = '';
				}
			}

			scope.addFilter = function() {
				scope.options.filters.push({type: '', value: ''});
			}

			scope.fetchList = function(field, value) {
				return $requests.fetch('fetchList', {field: field, value: value, limit: 10})
					.then(function(response){
						var results = response.items;
						return results;
					})
			}

			scope.exportItems = function() { 
				var params = $search.buildSearch(searchType);
				delete params.limit;
				delete params.page;
				$requests.fetch('exportRecordsSearch', params).then(function(results) { 
					$download.downloadFile(results.filename+'.csv', 'text/csv', results.file);
				});
			}

			scope.$watch('options', function(o, n) {
				if (scope.searchoptions.$valid) {
					scope.fetchItems();
				}
			}, true);
		}
	}
});

app.directive('collectionSelect', function($location, $search) {
	return {
		restrict: 'A',
		template:'<span collection-chooser select-collection="selectCollection(collection)" clear="1"/>',
		scope: {},
		link: function(scope,element, attribs) {
			scope.selectCollection = function(collection) { 
				if (collection) { 
					$search.resetSearch('collectionOpts');
					$location.path('/collections/'+collection.id);
				}
			}
		}
	}
});

app.directive('documentSelect', function($location, $requests, $search) {
	return {
		restrict: 'A',
		template:'<input type="text" ng-model="filter" typeahead-append-to-body="true" autocomplete="off" typeahead-editable="false" typeahead-on-select="selectDocument($item)" typeahead="doc.label for doc in fetchDocuments($viewValue)" class="form-control" placeholder="Find by Title/Call #/ID" />',
		scope: true,
		link: function(scope,element, attribs) {
			scope.filter = '';

			scope.selectDocument= function(document) { 
				if (document) { 
					$search.resetSearch('recordOpts');
					$location.path('/documents/'+document.id);
					scope.filter = '';
				}
			}

			scope.fetchDocuments = function(value) { 
				return $requests.fetch('fetchDocuments', {filter:value, limit:10, page: 1, nonDigitized:true, titleOnly: true})
					.then(function(results) { 
						return results.docs;
					})
			}
		}
	}
});

app.directive('formGroup', function($requests, $location) {
	return {
		restrict: 'A',
		scope: {
			label:'@',
			model:'=',
			inputType:'@?',
			options:'='
		},
		templateUrl: 'formGroup.html',
		link: function(scope, element) {
			scope.location = $location;
			scope.type = scope.inputType || 'text';
			scope.label_width = 2;
			scope.field_width = 10;
			scope.invalidHtml = false;

			if (! element.hasClass('row')) {
				scope.label_width = 4;
				scope.field_width = 8;
				element.addClass('col-xs-6');
			}

			scope.addItem = function() {
				if (scope.model) {
					scope.model = scope.model.replace("New "+scope.options.field+': ', '');
				}
			}

			scope.fetchList = function(field, value, limit) {
				//limit = limit;
				return $requests.fetch('fetchList', {field: field, value: value, limit: limit})
					.then(function(response){
						var results = response.items;
						if (scope.options.editable && results[0] != value && value != ' ') {
							results.unshift({item: 'New '+field+': '+value});
						}
						return results;
					})
			}

			scope.checkValue= function(value) {
				var input = element.find('input');
				if (! scope.model && input && input.val()) {
					input.val('');
				}
			}

			if (scope.inputType == 'richtext' && scope.model != '') {
				var clearWatch = scope.$watch(function() { return $('#htmltest').html(); }, function(html) {
					if (html != 'Loading...') {
					scope.invalidHtml = html == '';
					if (! scope.invalidHtml) { clearWatch(); }
					}
				});
			}
		}
	}
});

app.directive('callNumber', function(){
	return {
		restrict: 'A',
		scope: {
			model:'=',
		},
		templateUrl: 'callNumber.html',
		link: function(scope, element) {
			scope.callNumber = '';
			scope.desc = '';
			scope.subjects = [
				['AFR','Africa'],
				['CMA','Comomis Antepasados'],
				['CAA','Comunicacion Aztlan Arts'],
				['CAP','Comunicacion Aztlan Politics'],
				['CD','Compact Disc and DVD'],
				['CV','Chuy Varela'],
				['FI','Freedom is a Constant Struggle'],
				['JG/LS','Judy Gerber and Laurie Simms'],
				['JH','Pajaro Latino'],
				['KN','Kiilu Nyasha'],
				['KP','General Materials'],
				['LA','Latin America'],
				['MAJ','Mumia Abu Jamal'],
				['NI','Nothing is More Precious Than'],
				['PM','Prison Movement'],
				['POE','Poetry'],
				['PR','Paul Robeson'],
				['RD','Real Dragon'],
				['RFW','Robert F. Williams'],
				['RP','Reflecciones de La Raza'],
				['SS','Sue Supriano'],
				['V','Video all formats'],
				['WP','Wild Poppies'],
				['DOC','Document'],
			];

			scope.$watch('model', function() {
				// console.log(scope.model)
				if (scope.model && scope.model != '') {
					var pieces = scope.model.split(/ +(.*)/);
					scope.callNumber = pieces[0] || '';
					scope.desc = pieces[1] || '';				
				}
	
			})
			scope.$watchCollection('[callNumber, desc]', function () {
				scope.model = scope.callNumber + ' '+scope.desc;
			})
		}

	}
})

app.directive('messages', function($messages) {
	return {
		restrict: 'A',
		scope: true,
		template: "<alert class='message' type='{{message.type}}' close='close($index)' ng-repeat='message in messageService.messages'><div ng-bind-html='message.message'/></div>",
		link: function(scope, element, attribs) { 
			scope.messageService = $messages;
			scope.close = $messages.deleteMessage;
		}
	}
})

app.directive('fileUpload', function($upload, $messages, $requests) {
	return {
		restrict: 'A',
		scope: {
			filetype: '@',
			action: '@',
			currentUrl: '=',
			itemId: '@',
			type: '@',
		},
		templateUrl: 'fileUpload.html',
		link: function(scope, element, attribs) {
			scope.file = '';
			scope.image_URI = '';
			scope.bad_file = 0;
			scope.processing = 0;

			scope.clearFile = function() { 
				scope.image_URI = '';
				scope.bad_file = 0;
				scope.file = '';
				scope.processing = 0;
			}

			scope.onFileSelect = function($files) { 
				scope.clearFile();
				scope.file = $files[0];
				if (! scope.file.type || ! scope.file.type.match(/^image\/.*/i)) { 
					scope.bad_file = 'The file is not an image';
				} else if (scope.file.size > 8388608) { 
					scope.bad_file = 'The file is too big';
				} else {
					var fileReader = new FileReader();
					fileReader.readAsDataURL(scope.file);
					fileReader.onload = function(e) {
						scope.image_URI = e.target.result;
						scope.image_data = e.target.result.replace(/^[^;]+;base64,/, '');	
						scope.$apply();
					}
				}
			}
			
			scope.uploadFile = function() { 
				scope.processing = 1;
				if(!scope.file.name) { return; }
				$requests.write('uploadFile', {type:scope.type, ext: scope.file.name.split('.').pop(), id:scope.itemId, data:scope.image_data}).then(function(results) { 
					scope.currentUrl = results+'?'+ Date.now();
					$messages.addMessage("Thumbnail updated");
					scope.clearFile();
					//$messages.addMessage(results);
				})
			}

		}
	}
});

app.directive('tagger', function($requests, $data) {
	return {
		restrict: 'A',
		scope: {
			model: '=',
			type: '@',
			isEditable: '=editable'
		},
		templateUrl: 'tagger.html',
		link: function(scope, element, attribs) { 
			scope.data = $data;
			scope.selected = '';
			scope.removeItem = function(i) { 
				scope.model.splice(i, 1);
			}
			scope.addItem = function() {
				if (scope.selected) {
					scope.selected = scope.selected.replace("New "+scope.type+': ', '');
					if ($.inArray(scope.selected, scope.model) == -1) {
						scope.model.push(scope.selected);
					}
					scope.selected = '';
				}
			}

			scope.fetchList = function(field, value, limit) {
				limit = limit || 8;
				return $requests.fetch('fetchList', {field: field, value: value, limit: limit})
					.then(function(response){
						var results = response.items;
						if (scope.isEditable && results[0] != value) {
							results.unshift({item:'New '+field+': '+value});
						}
						return results;
					})
			}

		}
	}
});;

app.directive('fileImport', function($upload, $messages, $requests) {
	return {
		restrict: 'A',
		scope: {
			filetype: '@',
			action: '@',
		},
		templateUrl: 'fileImport.html',
		link: function(scope, element, attribs) { 
			scope.loaded = 0;
			scope.file = '';
			scope.data = '';
			scope.badFile = 0;
			scope.success_count = 0;
			scope.processing = 0;

			scope.uploadFile = function() {
				scope.processing = 1;
				$requests.write(scope.action, scope.data).then(function(results) { 
					scope.processing = 0;
					scope.success_count= results.count;
					//$messages.addMessage(results);
				});
			}

			scope.onFileSelect = function($files) {
				scope.badFile = 0;
				scope.success_count = 0;
				scope.processing = 0;
				scope.loaded = 0;

				scope.file = $files[0];
				if (scope.file.type && scope.file.type != 'text/'+scope.filetype.toLowerCase()) { 
					scope.badFile = 1;
				} else { 
					var fileReader = new FileReader();
					fileReader.readAsDataURL(scope.file);
					fileReader.onload = function(e) {
						scope.data = e.target.result.replace(/^[^;]+;base64,/, '');	
						scope.loaded = 1;
						scope.$apply();
					}
				}
			}
		}
	}	
});

app.directive('resize', ['$window', function($window) {
  return {
    link: function(scope) {
      angular.element($window).on('resize', function(e) {
        scope.$broadcast('windowResize');
      });
    }
  };
}]);

app.directive('content', ['$timeout', function(timeout) {
	return {
		link: function(scope) {
			var resize = function() {
				var height = $(window).height() - $('#header').outerHeight(true) - 21;
				if ($('#footer')) { 
					height -= $('#footer').outerHeight(true); 
				}
        $('.content-body').height(height);
			}	

			scope.$on('windowResize', resize);
			timeout(resize, 0);
		}
	}
}]);

app.directive('input', function () {
  return {
    restrict: 'E',
    require: '?ngModel',
    link: function (scope, elem, attrs, ctrl) {
      if (! ctrl || ! attrs.type || attrs.type.toLowerCase() !== 'number') {return; }
      ctrl.$formatters.push(function (value) {
      	value = ctrl.$modelValue;
        return value ? parseFloat(value, 10) : null;
      });
    }
  };
});

app.directive('autoheight', function() {
	return {
		restrict: 'A',
		link: function(scope, elem, attrs) {
			var e = $(elem);
			e.parentsUntil('.content-body').addClass('scroll-container');
			e.addClass('scroll-container');
		}
	}
})

app.directive('typeaheadFocus', function () {
  return {
    require: 'ngModel',
    link: function (scope, element, attr, ngModel) {

      //trigger the popup on 'click' because 'focus'
      //is also triggered after the item selection
      if ($.inArray(scope.options.field, ['program']) == -1) {
	      element.bind('click', function () {
	        var viewValue = ngModel.$viewValue;

	        //restore to null value so that the typeahead can detect a change
	        if (ngModel.$viewValue == ' ') {
	          ngModel.$setViewValue(null);
	        }

	        //force trigger the popup
	        ngModel.$setViewValue(' ');

	        //set the actual value in case there was already a value in the input
	        ngModel.$setViewValue(viewValue || ' ');
	      });
    	}
      //compare function that treats the empty space as a match
      scope.emptyOrMatch = function (actual, expected) {
        if (expected == ' ') {
          return true;
        }
        return actual.indexOf(expected) > -1;
      };
    }
  };
});