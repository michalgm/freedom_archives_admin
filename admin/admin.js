'use strict';

var app = angular.module('FA_Admin', ['ngRoute', 'ui.bootstrap', 'textAngular', 'ui.sortable', 'angularFileUpload', 'ngAnimate']);

/*app.run(function($rootScope, $location, AuthenticationService) { 
	var original_path = $location.path;
	$rootScope.$on('$routeChangeStart', function(event, next, current) {
		if ($location.path != '/login') { 
			AuthenticationService.checkLogin().then(function(results) { 
				console.log(results);
				$location.path('/login');
			});
		}
	});
});*/

app.config(function($routeProvider, $compileProvider) {
	//console.log($requests);
	$compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|data|blob):/);
	$routeProvider.
		when('/login', {
			templateUrl: 'login.html',
			controller: 'login'
		}).

		when('/documents', {
			templateUrl: 'documents.html',
			controller: 'documents'
		}).
		when('/documents/:id', {
			templateUrl: 'documentEdit.html',
			controller: 'documentEdit'
		}).
		when('/collections/:id', {
			templateUrl: 'collectionEdit.html',
			controller: 'collectionEdit'
		}).
		when('/site/featuredDocs', {
			templateUrl: 'siteFeaturedDocs.html',
			controller: 'siteFeaturedDocs'
		}).
		when('/site/:util', {
			templateUrl: 'siteUtils.html', 
			controller: 'siteUtils'
		}).
		otherwise({
			templateUrl: 'adminIndex.html',
			controller: 'adminIndex'
		});
});

app.controller('documents', function($scope, $data, $requests, $location) {
	$scope.data = $data;
	$scope.selectDoc = function(doc) { 
		$location.path('documents/'+doc.id);
	}
});

app.controller('documentEdit', function($scope, $filter, $routeParams, $requests, $messages, $data, $location) {
	$scope.data = $data;
	$scope.document = {
		'_authors': [],
		'_keywords': [],
		'_subjects': [],
	};
	
	$scope.loadDocument = function() {
		return $requests.fetch('fetchDocument', {id:$routeParams.id}).then(function(results) { 
			$scope.document = results;
		})
	}
	
	$scope.deleteDocument = function() { 
		if(window.confirm("Are you sure you want to delete the document '"+$scope.document.TITLE+"'?")) { 
			$requests.write('deleteDocument', null, $scope.document.DOCID).then(function(results) { 
				$data.updateData().then(function() { 
					$messages.addMessage("Document '"+$scope.document.TITLE+"' successfully deleted");
					//$location.path('/documents');
				})
			});
		}
	}

	$scope.saveDocument = function() {
		var data = angular.copy($scope.document);
		return $requests.write('saveDocument', data, $routeParams.id).then(function(results) {  
			$scope.document = results;
			console.log('hi')
			$routeParams.id = $scope.document.DOCID;
			$data.updateData().then(function() {
				$messages.addMessage("Document '"+$scope.document.TITLE+"' successfully saved");
			});
		});
	}

	if ($routeParams.id != 'new') { 
		$scope.loadDocument();
	}
	$scope.buttons = [{text:'Delete', action:$scope.deleteDocument, class:'btn-danger'}, {text:'Save', action:$scope.saveDocument, class:'btn-primary'}];
});

app.controller('collectionEdit', function($scope, $filter, $routeParams, $requests, $messages, $data, $location) {
	$scope.collection = {
		_featured_docs: [],
		_subcollections: []
	};
	$scope.data = $data;
	$scope.id = $routeParams.id == 'top' ? 0 : $routeParams.id;

	$scope.loadCollection = function() {
		return $requests.fetch('fetchCollection', {id: $scope.id}).then(function(results) { 
			$scope.collection = results;
		});
	}
	
	$scope.saveCollection = function() {
		var data = angular.copy($scope.collection);
		delete data.count;
		return $requests.write('saveCollection', data, $scope.id).then(function(results) {  
			$scope.collection = results;
			$routeParams.id = $scope.collection.COLLECTION_ID;
			$scope.id = $routeParams.id;
			$data.updateData().then(function() {
				if ( $scope.collection.COLLECTION_ID == 0) { 
					$messages.addMessage("Top-level collection successfully saved");
				} else { 
					$messages.addMessage("Collection '"+$scope.collection.COLLECTION_NAME+"' successfully saved");
				}
			})
		});
	}
	
	$scope.exportCollection = function() { 
		$requests.fetch('exportCollection', {collection_id:$scope.id}).then(function(results) { 
			downloadFile(results.filename+'.csv', 'text/csv', results.file);
		});
	}
	
	$scope.addSubcollection = function(collection) { 
		var skip = 0
		if (collection.id != $scope.id && collection.id) { 
			angular.forEach($scope.collection._subcollections, function(v) { if (v.COLLECTION_ID == collection.id) { skip = 1; }});
			if (! skip) { 
				$scope.collection._subcollections.push({COLLECTION_ID:collection.id, COLLECTION_NAME:collection.label});
			}
		}
	}
	
	$scope.removeSubcollection = function(index) { 
		$scope.collection._subcollections.splice(index, 1);
	}
	
	$scope.editSubcollection = function(id) { 
		$location.path('/collections/'+id);
	}

	$scope.buttons = [{text:'Export Collection', action:$scope.exportCollection, class:'btn-default'}, {text:'Save', action:$scope.saveCollection, class:'btn-primary'}];

	if ($scope.id != 'new') { 
		$scope.loadCollection();
	}

})

app.controller('siteFeaturedDocs', function($scope, $requests, $messages) {
	$scope.featuredDocs = {};

	$scope.loadFeaturedDocs = function() {
		return $requests.fetch('fetchCollection', {id:0}).then(function(results) { 
			$scope.featuredDocs = results._featured_docs;
		})
	}

	$scope.saveFeaturedDocs = function() {
		return $requests.write('saveCollection', {COLLECTION_NAME:'', _featured_docs:$scope.featuredDocs}, 0).then(function(results) {  
			$messages.addMessage("Featured Docs successfully saved");
			$scope.featuredDocs = results._featured_docs;
		});
	}

	$scope.buttons = [{text:'Save', action:$scope.saveFeaturedDocs, class:'btn-primary'}];
	$scope.loadFeaturedDocs();
});

app.controller('adminIndex', function($scope) {
});

app.controller('siteUtils', function($scope, $routeParams, $requests, $messages, $q) {
	$scope.util = $routeParams.util;
	$scope.title = '';

	switch($scope.util) { 
		case 'backupDatabase': 
			$scope.title = 'Backup Database';
			$scope.backupDatabase = function() {
				$requests.write('backupDatabase').then(function(results) { 
					downloadFile('freedom_archives_export.sql', 'text/plain', results);
				});
			}
			break;
		case 'filemakerImport':
			$scope.title = 'Filemaker XML Import';
			break;
		case 'exportCollections':
			$scope.title = 'Export Collections';
			$scope.exportCollection = function() { 
				$requests.fetch('exportCollection').then(function(results) { 
					downloadFile(results.filename+'.csv', 'text/csv', results.file);
				});
			}
			break;	
		case 'updateThumbnails':
			$scope.title = 'Update Thumbnails';
			$scope.options  = {
				force:0,
				collection:'',
			}
			$scope.complete = 0;
			$scope.total = 0;

			$scope.updateThumbnails = function() { 
				$messages.clearMessages();
				$scope.complete = 0;
				$scope.total = 0;
				$scope.thumbnails = [];
				$scope.success = 0;
				$scope.failed = 0;

				$requests.fetch('getThumbnailDocs', {collection:$scope.options.collection, force:$scope.options.force}).then(function(results) { 
					if(results.length) { 
						$scope.total = results.length;
				
						var updateThumbnail = function() { 
							var doc = results.shift();
							doc.status = 'Processing';
							doc.statusCode = 0;
							$scope.thumbnails.push(doc);
							if (doc.docid) { 
								var request = $requests.fetch('updateThumbnail', {id:doc.docid}).then(function(result) { 
									$scope.complete++;	
									angular.extend(doc, result);
									doc.statusCode = doc.status == 'Success' ? 2 : 3;
									//doc.status = result.status;
									//doc.image = result.image;
									if (result.status == 'Success') { $scope.success++; } else { $scope.failed++; }
									//$scope.thumbnails.push(result);
									updateThumbnail();
								});
							} else { 
								console.log('all done');
							}
						}

						updateThumbnail();
					} else { 
						$messages.addMessage("There are no thumbnails to update", 'warning');
					}
				});
			};
			break;
		case 'updateKeywords':
			$scope.title = 'Update Keywords';
			$scope.updateKeywords  = function() { 
				$messages.clearMessages();
				$scope.complete = 0;
				$scope.total = 0;

				$requests.fetch('getDocIds').then(function(results) {
					if (results.length) { 
						$scope.total = results.length;

						var updateLookups = function() {
							var id = results.shift();
							if (id) {
								var request = $requests.fetch('updateLookups', {id:id}).then(function(result) { 
									$scope.complete++;
									updateLookups();
								});
							} else { 
								$messages.addMessage('Keywords updated', 'success');
							}
						}
						updateLookups();
					}
				});
			};
			break;
		case 'csvImport':
			$scope.title = 'CSV Import';
			break;
		case 'findDuplicates':
			$scope.title = 'Find Duplicate Documents';
			$scope.duplicates = {};
			$scope.limit = 5;
			$scope.offset = 0;
			$scope.count = 0;
			$requests.fetch('findDuplicates').then(function(results) { 
				$scope.duplicates = results;
				$scope.fields = Object.keys($scope.duplicates[0].docs[0]);
				$scope.count = $scope.duplicates.length+1;
			});
			break;
	}
});

// HACK: we ask for $injector instead of $compile, to avoid circular dep
app.factory('$templateCache', function($cacheFactory, $http, $injector) {
	var cache = $cacheFactory('templates');
	var allTplPromise;
	 
	return {
		get: function(url) {
			var fromCache = cache.get(url);
			 
			// already have required template in the cache
			if (fromCache) {
				return fromCache;
			}
		 
			// first template request ever - get the all tpl file
			if (!allTplPromise) {
				allTplPromise = $http.get('templates.html').then(function(response) {
					// compile the response, which will put stuff into the cache
					$injector.get('$compile')(response.data);
					return response;
				});
			}
		 
			// return the all-tpl promise to all template requests
			return allTplPromise.then(function(response) {
				return {
					status: response.status,
					data: cache.get(url)
				};
			});
		},
		 
		put: function(key, value) {
			cache.put(key, value);
		}
	};
});
app.service('$requests', function($http, $messages, $upload) {
	var service = this;
	service.fetch = function(action, params) { 
		params = params || {};
		params.action = action;
		return $http.get('admin.php', {params:params, timeout:15000});
	}
	service.write = function(action, data, id) { 
		//data = data || {};
		//data.action = action;
		return $http.post('admin.php', {action: action, id: id, data:data}, {timeout:15000});
	}
	service.handleError = function(data, status, headers, config) { 
		console.log('Error');
		console.log(status);
	}
});

app.service('$data', function($requests, $rootScope, $messages) {
	var $data = this;
	$data.collections = {};
	$data.documents = {};
	$data.users = {};
	$data.keywords = {};
	$data.authors = {};
	$data.subjects = {};
	$data.action_access = {};

	$data.updateData = function(noclear) {
		if (! noclear) { 
			$messages.clearMessages();
		}
		return $requests.fetch('fetch_data').then(function(results) {
			$data.collections = results.collections;
			$data.users = results.users;
			$data.keywords = results.keywords;
			$data.authors = results.authors;
			$data.subjects = results.subjects;
			$data.action_access = results.action_access;
		});
	}

	$data.clearData = function() { 
		$data.collections = {};
		$data.documents = {};
		$data.users = {};
		$data.keywords = {};
		$data.authors = {};
		$data.subjects = {};
		$data.action_access = {};
	}	
		
	//$data.updateData();	
	return $data;

});

app.service('AuthenticationService', function($requests, $rootScope, $data) {
	var AuthenticationService = this;

	$rootScope.username = ''
	AuthenticationService.username = '';
	AuthenticationService.user_type = '';
	AuthenticationService.badLogin = 0;
	AuthenticationService.error = 0;

	AuthenticationService.login = function(user, password) { 
		AuthenticationService.badLogin = 0;
		AuthenticationService.error = 0;
		return $requests.write('login', {user:user, password:password}).then(function(result) { 
			if(setLogin(result)) { 
				if ($rootScope.username) { 
					$data.updateData();
				} else {
					AuthenticationService.badLogin = 1;
					$data.clearData();
				}
				return $rootScope.username || false;
			}
		}, function(error) { 
			if (error && error.data && error.data.statusString && error.data.statusString == 'Bad Login') { 
				AuthenticationService.badLogin = 1;
			} else { 
				AuthenticationService.error = error.data.statusString;
			}
			$rootScope.username = AuthenticationService.username = '';
			AuthenticationService.user_type = '';
			$data.clearData();
		});
	};

	AuthenticationService.logout = function() { 
		return $requests.fetch('logout').then(function(result) { 
			$data.clearData();
			$rootScope.username = AuthenticationService.username = '';
			AuthenticationService.user_type = '';
		});
	}

	AuthenticationService.checkLogin = function() { 
		return $requests.fetch('check_login').then(function(results) { 
			if (setLogin(results) ) { 
				if ($rootScope.username) { 
					$data.updateData();
				} else {
					$data.clearData();
				}
				return $rootScope.username || false;
			}
		});
	}
	
	var setLogin = function(data) {
		if (angular.isDefined(data.username)) { 
			$rootScope.username = AuthenticationService.username = data.username;
			AuthenticationService.user_type = data.user_type;
			return true;
		}
	}
});


app.service('$messages', function($rootScope) {
	var service = this;
	service.messages = [];

	service.addMessage = function(message, type) {
		var type = type || 'info';
		service.messages.push({message: message, type: type});
	};

	service.error = function(message) { 
		console.log('Error'+message);
		service.addMessage('Error: '+message, 'danger');
	}
	
	service.getMessages = function() { 
		return service.messages;
	};

	service.deleteMessage = function(index) { 
		service.messages.splice(index, 1);
	}

	service.clearMessages = function() { 
		service.messages = [];
	}
	
	$rootScope.$on( "$routeChangeStart", function(event, next, current) {
		service.clearMessages();
	});
});

app.config(function($httpProvider) { 
	$httpProvider.interceptors.push(function ($q, $messages, $injector) {
		var AuthenticationService;

		return {
			'response': function(response) { 
				if (response.config && response.config.url == 'admin.php') { 
					//console.log(response);
					if (response.data.statusCode == 1) {
						return response.data.data;
					} else if (response.data.statusString) { 
						if (response.data.statusCode == 401) { 
							AuthenticationService = AuthenticationService || $injector.get('AuthenticationService');
							AuthenticationService.logout();
						}	
						$messages.error(response.data.statusString);
						return $q.reject(response);
					} else { 
						$messages.error('An unknown error occurred.<br/>Response was: <pre>'+response.data+'</pre>');
						return $q.reject(response);
					}
				} else { 
					return response;
				}
			},
			'responseError': function(rejection) { 
				console.log(rejection);
				if (rejection.status) { 
					$messages.error('Unable to complete request ('+rejection.status+')');
				} else { 
					$messages.error('Unable to complete request - are you sure you\'re online?');
				}
				return $q.reject(rejection);
			}
		}
	});
});

//This is ugly (should not be in global namespace), but I'm getting lazy...
var downloadFile = function(filename, mimetype, data) { 
	var blob = new Blob([data], {type: mimetype});
	var hiddenElement = document.createElement('a');
	hiddenElement.href = URL.createObjectURL(blob);
	hiddenElement.target = '_blank';
	hiddenElement.download = filename;
	hiddenElement.click();
	$(hiddenElement).remove();
}

