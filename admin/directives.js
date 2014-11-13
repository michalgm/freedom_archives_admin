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

app.directive('navLink', function($data, AuthenticationService) {
	return {
		restrict: 'A',
		scope: {
			action:'@',
			restricted:'@?',
		},
		transclude:true,
		replace:true,
		template:"<li ng-class='{disabled:!allowed}'><a ng-href='{{ allowed ? \"#/\"+action : \"\"}}' ng-disabled='action_access[action]' ng-transclude></a></li>",
		link: function(scope, element, attribs) { 
			scope.allowed = ! scope.restricted || AuthenticationService.user_type == scope.restricted;
		}
	}
});



app.directive('header', function($requests, $sce) {
	return {
		restrict: 'A',
		templateUrl:'header.html',
		scope: {
			title:'@',
			buttons:'=',
			extraHeader:'='
		},
		link: function(scope,element, atrribs) {
			scope.safeExtraHeader = $sce.trustAsHtml(scope.extraHeader);
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
		link: function(scope,element, atrribs) {	
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
				if (value == '' && scope.callback) { 
					scope.callback();
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
		link: function(scope,element, atrribs) {
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
		
app.directive('documentSearch', function($requests) {
	return {
		restrict: 'A',
		templateUrl:'documentSearch.html',
		scope: {
			docLimit:'@',
			selectDoc:'=',
			nonDigitizedDefault:'@',
			limitCollectionId:'@'
		},
		link: function(scope,element, atrribs) {
			scope.page = 1;
			scope.filter = '';
			scope.documents = [];
			scope.count = 0;
			scope.nonDigitized = scope.nonDigitizedDefault || 0;
			scope.collection = '';
			scope.selected = null;
			
			scope.fetchDocuments = function() { 
				if (scope.limitCollectionId) { 
					scope.collection = scope.limitCollectionId;
				}
				$requests.fetch('fetchDocuments', {filter:scope.filter, collection:scope.collection, page:scope.page, limit:scope.docLimit, nonDigitized:scope.nonDigitized}).then(function(results) { 
					scope.documents = results.docs;
					scope.count = results.count;
				});
			}

			scope.selectCollection = function(collection) { 
				if (collection) { 
					scope.collection = collection.id;
				} else { 
					scope.collection = '';
				}
			}

			scope.$watchCollection('[filter, limitCollectionId, collection, nonDigitized]', function() { 
				scope.page = 1;
				scope.fetchDocuments();
			});

			scope.$watch('page', function() {
				scope.fetchDocuments();
			});

		}
	}
});

app.directive('collectionSelect', function($location) {
	return {
		restrict: 'A',
		template:'<span collection-chooser select-collection="selectCollection(collection)" clear="1"/>',
		scope: {},
		link: function(scope,element, atrribs) {
			scope.selectCollection = function(collection) { 
				if (collection) { 
					$location.path('/collections/'+collection.id);
				}
			}
		}
	}
});

app.directive('formGroup', function() {
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
			scope.type = scope.inputType || 'text';
			scope.label_width = 2;
			scope.field_width = 10;
			if (! element.hasClass('row')) {
				scope.label_width = 4;
				scope.field_width = 8;
				element.addClass('col-xs-6');
			} 
		}
	}
});

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
			scope.salt=Date.now();

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
				$requests.write('uploadFile', {type:scope.type, ext: scope.file.name.split('.').pop(), id:scope.itemId, data:scope.image_data}).then(function(results) { 
					var salt = new Date().getTime();
					scope.currentUrl = results+'?'+salt;
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
		},
		templateUrl: 'tagger.html',
		link: function(scope, element, attribs) { 
			scope.data = $data;
			scope.selected = '';
			scope.removeItem = function(i) { 
				scope.model.splice(i, 1);
			}
			scope.addItem = function() {
				scope.model.push(scope.selected);
				scope.selected = '';
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


