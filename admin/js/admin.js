'use strict';

var app = angular.module('FA_Admin', ['ngRoute', 'ui.bootstrap', 'textAngular', 'ui.sortable', 'angularFileUpload', 'ng-breadcrumbs']);

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
			controller: 'login',
      label: 'Login'
		}).

		when('/documents', {
			templateUrl: 'documents.html',
			controller: 'documents',
      label: 'Records'
		}).
		when('/documents/:id', {
			templateUrl: 'documentEdit.html',
			controller: 'documentEdit'
		}).
		when('/collections', {
			templateUrl: 'collections.html',
			controller: 'collections',
      label: 'Collections'
		}).
		when('/collections/:id', {
			templateUrl: 'collectionEdit.html',
			controller: 'collectionEdit'
		}).
		when('/site/featuredDocs', {
			templateUrl: 'siteFeaturedDocs.html',
			controller: 'siteFeaturedDocs',
      label: 'Featured Docs'
		}).
		when('/site/:util', {
			templateUrl: 'siteUtils.html', 
			controller: 'siteUtils',
      label: 'Site'
		}).
    when('/', {
      templateUrl: 'adminIndex.html',
      controller: 'adminIndex',
      label: 'Admin'
    }).
		otherwise({
			templateUrl: 'adminIndex.html',
			controller: 'adminIndex',
      label: 'Admin'
		});
});

app.controller('documents', function($scope, $data, $requests, $location) {
	$scope.data = $data;
	$scope.selectDoc = function(doc) { 
		$location.path('documents/'+doc.id);
	}
});

app.controller('collections', function($scope, $data, $requests, $location) {
	$scope.data = $data;
	$scope.selectCollection = function(collection) { 
		$location.path('collections/'+collection.COLLECTION_ID);
	}
});

app.controller('documentEdit', function($scope, $filter, $routeParams, $requests, $messages, $data, $location, $search, $timeout) {
  $scope.data = $data;
	$scope.search = $search;
  $scope.location = $location;
	$scope.document = {
		'_authors': [],
		'_keywords': [],
		'_subjects': [],
    '_producers': [],
		'_related': [],
    'MONTH': '?',
    'DAY': '?',
    'YEAR': '?',
    'NO_COPIES': '1',
    'COLLECTION_ID': 1000,
	};
  var orig_doc = angular.copy($scope.document);
  $scope.related_filter= '';

	$scope.id = $routeParams.id;
  $scope.search.updateNeighbors('recordOpts', $scope.id);

	$scope.loadDocument = function() {
		return $requests.fetch('fetchDocument', {id:$routeParams.id}).then(function(results) { 
			$scope.document = results;
			$scope.document.thumbnail_url = $scope.document.THUMBNAIL ? $scope.document.THUMBNAIL + '?' + Date.now() : "";
      orig_doc = angular.copy($scope.document);
		})
	}
	
	$scope.deleteDocument = function() { 
		if(window.confirm("Are you sure you want to delete the document '"+$scope.document.TITLE+"'?")) { 
			$requests.write('deleteDocument', null, $scope.document.DOCID).then(function(results) { 
				$data.updateData().then(function() { 
					$messages.addMessage("Document '"+$scope.document.TITLE+"' successfully deleted");
					$location.path('/documents');
				})
			});
		}
	}

	$scope.saveDocument = function() {
		var data = angular.copy($scope.document);
		delete data.thumbnail_url;

		return $requests.write('saveDocument', data, $routeParams.id).then(function(results) {  
			$scope.document = results;
			$scope.id = $routeParams.id = $scope.document.DOCID;
			$data.updateData().then(function() {
				$scope.document.thumbnail_url = $scope.document.THUMBNAIL ? $scope.document.THUMBNAIL + '?' + Date.now() : "";
				// $location.path('/documents/'.$scope.document.DOCID);
        orig_doc = angular.copy($scope.document);
				$messages.addMessage("Document '"+$scope.document.TITLE+"' successfully saved");
			});
		});
	}

  $scope.fetchRelated = function(value) { 
    return $requests.fetch('fetchDocuments', {filter:value, limit:10, page: 1, nonDigitized:true, titleOnly: true})
      .then(function(results) { 
        return results.docs;
      })
  }

  $scope.selectRelated = function(doc) {
    $scope.document['_related'].push({
      DOCID: $scope.document.DOCID,
      TITLE: doc.label,
      DESCRIPTION: doc.DESCRIPTION,
      DOCID_OTHER: doc.id,
      TITLE_OTHER: $scope.document.TITLE,
      DESCRIPTION_OTHER: $scope.document.DESCRIPTION,
      TRACK_NUMBER: $filter('filter')($scope.document['_related'], {delete: "!1"}).length+1
    })
    $scope.related_filter= '';
    $timeout(function() { 
      $('.content-body').scrollTop($('.content-body')[0].scrollHeight);
    });
  }

  $scope.viewRelated= function(id) { 
    if (id) { 
      $location.path('/documents/'+id);
    }
  }

  $scope.deleteRelated = function(index) {
    $scope.document['_related'][index].delete = 1;
  }

	var checkChanged = function() {
    var doc = angular.copy($scope.document);
    $.each(['DATE_MODIFIED', 'DATE_CREATED'], function(i, v){
      delete orig_doc[v];
      delete doc[v];
    })
    // console.log(JSON.stringify(doc));
    // console.log(JSON.stringify(orig_doc));
    return ! angular.equals(doc, orig_doc);
  }

  $scope.$on('$locationChangeStart', function(event) {
    if(checkChanged()) {
      var answer = confirm("You have unsaved changes. Are you sure you want to leave this page?")
      if (!answer) {event.preventDefault(); };
    }
  });
  
  $scope.$on('$destroy', function() {
     window.onbeforeunload = undefined;
  });

  window.onbeforeunload = function(event) {
    if (typeof event == 'undefined') {event = window.event; }
    var msg = "You have unsaved changes.";
    if (checkChanged()) {
      if (event) {event.returnValue= msg; }
      return msg;
    }
  }
  
  if ($routeParams.id != 'new') { 
		$scope.loadDocument();
	}
	$scope.buttons = [{text:'Delete', action:$scope.deleteDocument, class:'btn-danger'}, {text:'Save', action:$scope.saveDocument, class:'btn-primary'}];
});

app.controller('collectionEdit', function($scope, $filter, $routeParams, $requests, $messages, $data, $location, $download, $search) {
	$scope.collection = {
		_featured_docs: [],
    _subcollections: [],
    _removeDocs: [],
		_addDocs: []
	};

  $scope.search = $search;
	$scope.data = $data;
  $scope.location = $location;
	$scope.id = $routeParams.id == 'top' ? 0 : $routeParams.id;
  var orig_col = angular.copy($scope.collection);
  $scope.search.updateNeighbors('collectionOpts', $scope.id);

  $scope.removeDoc = function(index) {
    $scope.collection['_removeDocs'].push($scope.documents[index].id);
    $scope.documents.splice(index, 1);  
  }

  $scope.addDoc = function(doc) {
    $scope.collection['_addDocs'].push(doc.id);
    $scope.documents.push(doc);  
  }

	$scope.loadCollection = function() {
		return $requests.fetch('fetchCollection', {id: $scope.id}).then(function(results) { 
			$scope.collection = results;
      orig_col = angular.copy($scope.collection);

      $requests.fetch('fetchDocuments', {'filter_types[]':['COLLECTION_ID'], 'filter_values[]': [$scope.id], nonDigitized: 1}).then(function(results) {
        $scope.documents = results.docs;
      })
		});
	}
	
	$scope.saveCollection = function() {
		var data = angular.copy($scope.collection);
		delete data.count;
		return $requests.write('saveCollection', data, $scope.id).then(function(results) {  
			$scope.collection = results;
			$routeParams.id = $scope.collection.COLLECTION_ID;
			$scope.id = $routeParams.id;
      orig_col = angular.copy($scope.collection);
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
			$download.downloadFile(results.filename+'.csv', 'text/csv', results.file);
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

  $scope.deleteCollection = function() { 
    if(window.confirm("Are you sure you want to delete the collection '"+$scope.collection.COLLECTION_NAME+"'?")) { 
      $requests.write('deleteCollection', null, $scope.collection.COLLECTION_ID).then(function(results) { 
        $data.updateData().then(function() { 
          $messages.addMessage("Collection '"+$scope.collection.COLLECTION_NAME+"' successfully deleted");
          $location.path('/collections');
        })
      });
    }
  }
	
	$scope.removeSubcollection = function(index) { 
		$scope.collection._subcollections.splice(index, 1);
	}
	
	$scope.editSubcollection = function(id) { 
		$location.path('/collections/'+id);
	}

  var checkChanged = function() {
    var col = angular.copy($scope.collection);
    $.each(['DATE_MODIFIED', 'DATE_CREATED'], function(i, v){
      delete orig_col[v];
      delete col[v];
    })
    return ! angular.equals(col, orig_col);
  }

  $scope.$on('$locationChangeStart', function(event) {
    if(checkChanged()) {
      var answer = confirm("You have unsaved changes. Are you sure you want to leave this page?")
      if (!answer) {event.preventDefault(); };
    }
  });
  
  $scope.$on('$destroy', function() {
     window.onbeforeunload = undefined;
  });

  window.onbeforeunload = function(event) {
    if (typeof event == 'undefined') {event = window.event; }
    var msg = "You have unsaved changes."
    if (checkChanged()) {
      if (event) {event.returnValue= msg; }
      return msg;
    }
  }

	$scope.buttons = [{text:'Export Collection', action:$scope.exportCollection, class:'btn-default'}, {text:'Delete', action:$scope.deleteCollection, class:'btn-danger'}, {text:'Save', action:$scope.saveCollection, class:'btn-primary'}];

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

app.controller('siteUtils', function($scope, $routeParams, $requests, $messages, $q, $data, $download, $timeout, $filter) {
	$scope.util = $routeParams.util;
	$scope.title = '';

	switch($scope.util) { 
		case 'backupDatabase': 
			$scope.title = 'Backup Database';
			$scope.backupDatabase = function() {
				$requests.write('backupDatabase').then(function(results) { 
					$download.downloadFile('freedom_archives_export.sql', 'text/plain', results);
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
					$download.downloadFile(results.filename+'.csv', 'text/csv', results.file);
				});
			}
			break;
		case 'editLists':
			$scope.title = 'Edit Lists';
			$scope.lists = {};
			$scope.limit = 50;
			
			$scope.fetchList = function(label) {
				var list = $scope.lists[label];
				$requests.fetch('fetchList', {field: label, value: list.filter, limit: $scope.limit, offset: (list.offset-1)*$scope.limit  })
					.then(function(results){
						$scope.lists[label].items = results.items;
						$scope.lists[label].count = results.count;
					})
			}

			$scope.editItem = function(label, item, action, new_item) {
				$requests.fetch('editListItem', {field: label, item: item, new_item: new_item || "", listAction: action})
					.then(function(results) {
						$messages.addMessage("Item "+(action == 'delete' ? item : new_item)+" "+action+'ed successfully');
						$scope.fetchList(label);
						$scope.lists[label].new = '';
					})
			}

			angular.forEach(['keyword', 'subject', 'author', 'producer', 'program', 'quality', 'generation', 'format'], function(v) {
				$scope.lists[v] = {
					items: [],
					filter: '',
					count: 0,
					offset: 1,
					new: ''
				}
				$scope.fetchList(v);
			})
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
				// $scope.total = Object.keys($data.collections).length;
				var ids = [];
				// angular.forEach($data.collections, function(c) {
				// 	ids.push({id: c.id, type: 'collection'});
				// })

				$requests.fetch('getDocIds').then(function(results) {
					if (results.length) { 
						ids = results;
            $scope.total = results.length;
						// angular.forEach(results, function(v) {
						// 	ids.push({id: v, type: 'document'});
						// });
					}
					var updateLookups = function() {
						var items = [];
						var x = 0;
						while (x < 50 && ids.length) {
							items.push(ids.shift());
							x++;
						}
						var request = $requests.write('updateLookups', {items:items}).then(function(result) { 
							$scope.complete+=result;
							if (ids.length) {
								updateLookups();
							} else {
								$messages.addMessage('Keywords updated', 'success');
							}
						});
					}
					updateLookups();
				});
			};
			break;
		case 'csvImport':
			$scope.title = 'CSV Import';
			break;
		case 'reviewChanges':
			$scope.title = 'Review Changes';
      $scope.data = $data;

			$scope.log = [];
      $scope.filtered_log = [];

			$scope.pagination = {
				limit: 10,
				offset: 1,
				count:0,
        page: 1
			}
      $scope.only_reviewed = true;
			$scope.lastUpdate = '';
      $scope.time_amount = 10;
      $scope.time_period = 'day';
      $scope.time_limit = 'last_update';
      $scope.user_filter = $scope.type_filter = $scope.action_filter = 'All';
      $scope.title_filter = '';
      $scope.collection_filter = '';

      $scope.fetchLog = function() {
				$requests.fetch('fetchAuditLog', {
          'only_reviewed': $scope.only_reviewed,
          'time_period': $scope.time_period,
          'time_limit': $scope.time_limit,
          'time_amount': $scope.time_amount,
          }).then(function(results) {
					$scope.log = results.log;
					$scope.lastUpdate = results.lastUpdate;
          $scope.filter();
				})
			}

      $scope.filter = function() {
        $timeout(function() {
          var log = $scope.log;
          if ($scope.title_filter != '') {
            log = $filter('filter')(log, {description:$scope.title_filter});
          }
          log = $filter('filter')(log, {
            action:$scope.action_filter != 'All' ? $scope.action_filter : undefined,
            user:$scope.user_filter != 'All' ? $scope.user_filter : undefined,
            type:$scope.type_filter != 'All' ? $scope.type_filter : undefined,
            collection_id:$scope.collection_filter != '' ? $scope.collection_filter : undefined,
          }, true);
          $scope.filtered_log = log;
          $scope.pagination.count = $scope.filtered_log.length+1;
          $scope.pagination.page = 1;
        });
      }

      $scope.reviewItem= function(item) {
        var save = {
          NEEDS_REVIEW: item.NEEDS_REVIEW == 1 ? 0 : 1,
        }
        var action = item.type == 'document' ? 'saveDocument' : 'saveCollection';

        $requests.write(action, save, item.id).then(function(results) {
          $scope.saving = false;
          $scope.fetchLog();
        });
      }

			$scope.fetchLog();
			break;

    case 'publish':
      $scope.title = 'Publish or Restore Live Site';
      $scope.backups = {};

      $scope.restoreBackup = function(backup) {
        if(window.confirm("Are you sure you want to restore "+backup.id+" from "+backup.date+" to the live site?")) { 
          $requests.fetch('restoreBackup', {id: backup.id}).then(function(results) {
            $messages.addMessage(backup.id+' restored', 'success');
            $scope.restoring = false;
            $scope.fetchBackups();
          });
        } else {
          $timeout(function() {$scope.restoring = false; });
        }
      }
      
      $scope.fetchBackups = function() {
        $requests.fetch('fetchBackups').then(function(results) {
          $scope.backups = results;
        });
      }
      
      $scope.pushChanges = function() {
        $requests.fetch('pushChanges').then(function(results) {
          $messages.addMessage('Live site updated', 'success');
          $scope.fetchBackups();
        })
      }
      $scope.fetchBackups();
      $scope.buttons = [{text:'Publish Changes to Live Site', action:$scope.pushChanges, class:'btn-primary'}];
      break;

		case 'findDuplicates':
			$scope.title = 'Find Duplicate Documents';
			$scope.duplicates = {};
			$scope.pagination = {
				limit: 5,
				offset: 1,
				count:0
			}
			$requests.fetch('findDuplicates').then(function(results) { 
				$scope.duplicates = results;
				$scope.fields = Object.keys($scope.duplicates[0].docs[0]);
				$scope.pagination.count = $scope.duplicates.length+1;
			});
			break;

    case 'manageUsers':
      $scope.title = 'Manage Users';
      $scope.users = {};
      $scope.edit = {};
      $scope.pw = {};

      $scope.editUser = function(user) {
        $scope.edit = angular.copy(user);
        $scope.pw = {};
      }

      $scope.addUser = function() {
        $scope.edit = { user_id: 'new', user_type: 'Intern'};
        $scope.users.new = angular.copy($scope.edit);
        $scope.pw = {id: 'new'};
      }

      $scope.saveUser = function() {
        var user = angular.copy($scope.edit);
        if ($scope.pw.pw1 && $scope.pw.pw1 != $scope.pw.pw2) { 
          $scope.saving = false;
          return;
        }
        if (user.user_id == 'new' && ! $scope.pw.pw1) {
          $scope.saving = false;
          $messages.error("Password cannot be blank");
          return;
        }
        user.password = $scope.pw.pw1;
        $requests.write('saveUser', user).then(function(response) {
          $scope.saving.status = false;
          $scope.users[response.user_id] = response;
          $scope.edit = {};
          $scope.pw = {};
          delete $scope.users.new;
          $messages.addMessage("User '"+user.username+"' saved", 'success');
        })
      }

      $scope.deleteUser = function(user) {
        if(window.confirm("Are you sure you want to delete '"+user.username+"'?")) { 
          $requests.write('deleteUser', {id: user.user_id}).then(function(response) {
            delete $scope.users[user.user_id];
            $scope.saving = false;
          })
        } else {
          $timeout(function() {$scope.saving.status = false;});
        }
      }

    	$requests.fetch('fetchUsers').then(function(results){
        $scope.users = results;
      }) 

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
				allTplPromise = $http.get('js/templates.html').then(function(response) {
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
		return $http.get('admin.php', {params:params, timeout:600000});
	}
	service.write = function(action, data, id) { 
		//data = data || {};
		//data.action = action;
		return $http.post('admin.php', {action: action, id: id, data:data}, {timeout:600000});
	}
	service.handleError = function(data, status, headers, config) { 
		console.log('Error');
		console.log(status);
	}
});

app.service('$data', function($requests, $rootScope, $messages, $filter) {
	var $data = this;
	$data.collections = [];
	$data.action_access = {};
  $data.collection_index = {};
  $data.users = [];

	$data.updateData = function(noclear) {
		if (! noclear) { 
			$messages.clearMessages();
		}
		return $requests.fetch('fetchData').then(function(results) {
			$data.collection_index = results.collections;
      $data.collections = $filter('toArray')(results.collections);
			$data.action_access = results.action_access;
      $data.users = results.users;
		});
	}

	$data.clearData = function() { 
    $data.collections = [];
		$data.users = [];
    $data.action_access = {};
		$data.collection_index = {};
	}	
		
	//$data.updateData();	
	return $data;

});

app.service('AuthenticationService', function($requests, $rootScope, $data) {
	var AuthenticationService = this;

	$rootScope.username = ''
	AuthenticationService.username = '';
  AuthenticationService.user_type = '';
	AuthenticationService.name = '';
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
			AuthenticationService.name = '';
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
			AuthenticationService.name = data.name;
			return true;
		}
	}
});

app.service('$search', function($rootScope, $requests) {
  var service = this;

  service.buildSearch = function(type) {
    if (! service[type]) {
      service[type] = angular.copy(service.recordOpts);
      service.resetSearch(type);
    }
    var params = {
      filter:service[type].filter,
      collection:service[type].collection, 
      page:service[type].page,
      limit:service[type].itemLimit,
      nonDigitized:service[type].nonDigitized,
      IS_HIDDEN: service[type].IS_HIDDEN,
      NEEDS_REVIEW: service[type].NEEDS_REVIEW,
      'filter_types[]': [],
      'filter_values[]': [],
    };
    $.each(service[type].filters, function(i, filter) {
      params['filter_types[]'].push(filter.type);
      params['filter_values[]'].push(filter.value);
    });

    return params;
  }

  service.updateNeighbors = function(type, id) {
    if (type == 'colRecordOpts' || id == 'new') { return; }
    var options = service[type]; // == 'recordOpt' ? service.recordOpts : service.collectionOpts;
    var items = type == 'recordOpts' ? service.records : service.collections;
    var neighbors = type == 'recordOpts' ? service.neighbors.records : service.neighbors.collections;
    neighbors.next = null;
    neighbors.prev = null;

    var index = $.inArray(id, items);
    if (index < 0) {
      if (neighbors.resultNum != null) {
        if(neighbors.resultNum >= options.page* options.itemLimit) {
          options.page++;
        } else {
          options.page--;
        }
        if (options.page > 0) {
          service.fetchItems(type).then(function(res) {
            items = type == 'recordOpts' ? service.records : service.collections;
            index = $.inArray(id, items);
            if ( index < 0) {
              console.log('give up!');
            } else {      
              service.updateNeighbors(type, id);
            }
          });
        } else {
          service.resetSearch(type);
        }
      }
    } else {
      neighbors.resultNum = (options.page-1)*options.itemLimit + index+1;

      if (index == 0 && options.page != 1) {
        var params = service.buildSearch(type);
        params.page--;
        service.fetchItems(type, params, true).then(function(res) {
          neighbors.prev = res[options.itemLimit-1].id;
        })
      } else{
        neighbors.prev = index == 0 ? null : items[index -1];   
      }

      if (index == options.itemLimit-1 && neighbors.resultNum < options.count) {
        var params = service.buildSearch(type);
        params.page++;
        
        service.fetchItems(type, params, true).then(function(res) {
          neighbors.next = res[0].id;
        })
      } else {
        neighbors.next = neighbors.resultNum >= options.count ? null : items[index +1];   
      }
    }
  }

  service.fetchItems = function(type, params, nosave) { 
    if (!params) {
      var params = service.buildSearch(type);
    }
    var action = type == 'collectionOpts' ? 'fetchCollections' : 'fetchDocuments';
    return $requests.fetch(action, params).then(function(results) { 
      var items = {};

      if (type == 'collectionOpts') {
          items = results.collections;
      } else {
          items = results.docs;
      }
      if (! nosave) {
        var ids = $.map(items, function(i) { return i.id; })
        if (type != 'collectionOpts') {
          service.records = ids;//service.records.concat(ids);
          service[type].digitized = results.digitized || 0;
        } else {
          service.collections = ids; //service.collections.concat(ids);
        }
        if (results.count != service[type].count) {
          service.resetSearch(type)
          service[type].count = results.count;
        }
      }
      return items;
    });
  }

  service.resetSearch = function(type) {
    if (! service[type]) {
      service[type] = angular.copy(service.recordOpts);
    }
    service[type].count = 0;
    service[type].page = 1;
    var clearType = type=='recordOpts' ? 'records' : (type == 'collectionOpts' ? 'collections' : null);
    if (clearType) {
      service[clearType] = [];
      service.neighbors[clearType] = {
        prev: null,
        next: null,
        resultNum: null
      }
    }
  }

  service.reset = function() {
    service.records = [];
    service.collections = [];

    service.neighbors = {
      collections: {
        prev: null,
        next: null,
        resultNum: null
      },
      records: {
        prev: null,
        next: null,
        resultNum: null
      }
    };
    service.recordOpts = {
      filter : '',
      nonDigitized: 0,
      NEEDS_REVIEW : 0,
      IS_HIDDEN : 0,
      collection : '',
      page: 1,
      count: 0,
      digitized: 0,
      filters: [],
      itemLimit: 10
    };
    service.colRecordOpts = {
      filter : '',
      nonDigitized: 0,
      NEEDS_REVIEW : 0,
      IS_HIDDEN : 0,
      collection : '',
      page: 1,
      count: 0,
      digitized: 0,
      filters: [],
      itemLimit: 10
    };
    service.collectionOpts = {
      filter : '',
      NEEDS_REVIEW : 0,
      IS_HIDDEN : 0,
      page: 1,
      count: 0,
      collection : '',
      filters: [],
      itemLimit: 10
    };
  }

  service.reset();

});


app.service('$messages', function($rootScope) {
	var service = this;
	service.messages = [];

	service.addMessage = function(message, type) {
		var type = type || 'info';
		service.messages.push({message: message, type: type});
		$('.processing-spinner').remove(); 		
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
					if (typeof(response.data) == 'string' && response.data.match(/^#STATUS#/)) {
            response.data = JSON.parse(response.data.replace(/^#STATUS#.*?(#ENDSTATUS#)?{/, '{'));
          }
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

app.service('$download', function($timeout) {
  this.downloadFile = function(filename, mimetype, data) { 
    var blob = new Blob([data], {type: mimetype});
  	var hiddenElement = document.createElement('a');
  	hiddenElement.href = URL.createObjectURL(blob);
  	hiddenElement.target = '_blank';
  	hiddenElement.download = filename;
    $timeout(function() {
      $('body').append(hiddenElement);
      hiddenElement.click();
      $(hiddenElement).remove();
      $('.processing-spinner').remove();      
    });
  }
});

// angular.module("template/typeahead/typeahead-popup.html").run(["$templateCache", function($templateCache) {
//   $templateCache.put("template/typeahead/typeahead-popup.html",
//     "<ul class=\"dropdown-menu\" ng-show=\"isOpen()\" ng-style=\"{top: position.top+'px', left: position.left+'px', width: position.width+'px'}\" style=\"display: block;\" role=\"listbox\" aria-hidden=\"{{!isOpen()}}\">\n" +
//     "    <li ng-repeat=\"match in matches track by $index\" ng-class=\"{active: isActive($index) }\" ng-mouseenter=\"selectActive($index)\" ng-click=\"selectMatch($index)\" role=\"option\" id=\"{{match.id}}\">\n" +
//     "        <div typeahead-match index=\"$index\" match=\"match\" query=\"query\" template-url=\"templateUrl\"></div>\n" +
//     "    </li>\n" +
//     "</ul>\n" +
//     "");
// }]);
