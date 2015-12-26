
var PropelSOAQuery = Class.extend({

  init: function()
  {
    this.namespace = '';
    this.bundle = '';
    this.entity = '';

    this.resourceRoute = '';
    this.isPublic = false;

    this.filters    = [];
    this.joins      = [];
    this.linkedData = [];

    // Set of data and references used to facilitate re-running the query and updating the associated scope in Angular
    this.resultsContainer = null;
    this.resultsContainerKey = null;
    this.oneResult = false;
    this.config = null;

    this.returnedJoinTree = null;
    this.propelSOA = null;
  },

  getResourceRoute: function()
  {
    var route = this.resourceRoute;

    if (this.isPublic)
    {
      route = "/public" + route;
    }

    return env_url + route;
  },

  getPkName: function()
  {
    return this.entity + 'Id';
  },


  addInnerJoin: function(relation, namespace, bundle, entity)
  {
    var join = new PropelSOAJoin();
    join.relation = relation;

    var endRelation = relation;
    var lastIndex = endRelation.lastIndexOf('->');

    if (lastIndex >= 0)
    {
      endRelation = endRelation.substr(lastIndex + 2);
    }

    join.namespace = namespace ? namespace : this.namespace;
    join.bundle    = bundle    ? bundle    : this.bundle;
    join.entity    = entity    ? entity    : endRelation;

    this.joins.push(join);
  },


  addLinkedData: function(relation)
  {
    this.linkedData.push(relation);
  },


  addEqualFilter: function(fieldName, value)
  {
    filter = new PropelSOAEqualFilter();
    filter.setField(fieldName);
    filter.setFilterValue(value);

    this.filters.push(filter);
  },


  addInFilter: function(fieldName, values)
  {
    filter = new PropelSOAEqualFilter();
    filter.setField(fieldName);
    filter.setFilterValue(values);

    this.filters.push(filter);
  },


  buildQueryData: function()
  {
    var data = {};
    var listsToAdd = ['filters', 'joins', 'linkedData'];

    for(index = 0; index < listsToAdd.length; ++index)
    {
      listName = listsToAdd[index];
      data[listName] = this[listName];
    }

    var wrapperObject = {"data": data};
    return wrapperObject;
  },


  /**
   * This method actually runs the query through the server-side endpoint and populates the
   * specified angular model.
   *
   * @param $scope
   * @param property
   * @param config
   */
  runQuery: function($scope, property, config)
  {
    config = typeof(config) == 'undefined' ? {} : config;

    // Keep query information permanently so that we can refresh the results easily at any time
    this.config = config;
    this.resultsContainer = $scope;
    this.resultsContainerKey = property;

    var mainObject = this;
    $scope[property] = mainObject.propelSOA.getCollection(mainObject.namespace, mainObject.bundle, mainObject.entity);
    $scope[property].query = mainObject;

    var dataToSend = this.buildQueryData();
    var deferred = this.propelSOA.$q.defer();
    var localConfig = $.extend({},config);
    localConfig.url = this.getResourceRoute();
    localConfig.method = 'GET';
    localConfig.params = dataToSend;

    this.propelSOA.$http(localConfig).then( function(json, status, headers, config)
    {
      json = json.data;
      var joinTree = json.Data.joinTree;
      mainObject.returnedJoinTree = joinTree;

      json = json.Data.results;
      for(var index = 0; index < json.length; ++index)
      {
        // we need the recordId so that we make sure to update a registered model if we have one, get the
        // empty object first so we can pull the primary key field name

        var object=null;
        if ( json[index] !== null )
        {
          object = mainObject.propelSOA.getNewObject(mainObject.namespace, mainObject.bundle, mainObject.entity);
          object = object.populateFromJSON(json[index]);
          object.query = mainObject; // this will allow us to refresh the tree of results from any child object

          if (joinTree.dataRelationList.length > 0)
          {
            // the basic data is in place, now it is time to add the joined data
            object = mainObject.buildObjectFromJoinSubtree(object, json[index], joinTree.dataRelationList);
          }
        }

        $scope[property].collection.push(object);
      }

      // we need to register our new collection of objects with the main collection cache. this is so that we can
      // find records later, if one is deleted we want the framework to be able to remove it from all collections
      mainObject.propelSOA.registerCollection($scope[property], mainObject.namespace, mainObject.bundle, mainObject.entity);

      // tell anyone listening that we have finished
      deferred.resolve();

    }, function(reason)
    {
      deferred.reject();
    });

    return deferred.promise;
  },

  /**
   * This method actually runs the query through the server-side endpoint and populates the
   * specified angular model.
   *
   * @param $scope
   * @param property
   * @param config
   */
  runQueryOne: function($scope, property, config)
  {
    var deferred = this.propelSOA.$q.defer();
    this.oneResult = true;

    var limitedScope = {
      'results': null
    };

    this.runQuery(limitedScope, 'results', config).then(function()
    {
      $scope[property] = limitedScope.results.collection.length > 0 ? limitedScope.results.collection[0] : null;
      deferred.resolve();
    }, function()
    {
      deferred.reject();
    });

    return deferred.promise;
  },

  /**
   * This method will run a query for a record based on its ID and assign it to a property on a model $scope.
   *
   * @param id
   * @param $scope
   * @param property
   * @param config
   */
  runPkQuery: function(id, $scope, property, config)
  {
    var deferred = this.propelSOA.$q.defer();
    this.oneResult = true;
    this.addEqualFilter(this.getPkName(), id);

    var mainObject = this;
    var tempDataContainer = {};

    this.runQuery(tempDataContainer, 'results', config).then(function()
    {
      var recordsCount = tempDataContainer.results.length;
      if (recordsCount <= 0)
      {
        // we didn't find a result so just exit without changing the scope
        return;
      }

      $scope[property] = tempDataContainer.results.collection[0];
      deferred.resolve();
    });

    return deferred.promise;
  },


  /**
   * This method builds an object out of a subtree. This is used recursively to
   * populate top-level objects from a query.
   *
   * @param object
   * @param data
   * @param joinList
   */
  buildObjectFromJoinSubtree: function(object, data, joinList)
  {
    for (var index = 0; index < joinList.length; ++index)
    {
      var join = joinList[index];

      if (objectHasKey(data, join.relation))
      {
        if (join.relationType == 'linkeddata')
        {
          object.linkedDataModel[join.relation] = data[join.relation];
        }
        else
        {
          this.addToOneRelation(object, data, join);
        }
      }
      else
      {
        var plural = pluralize(join.relation);
        if (data[plural])
        {
          this.addToManyRelation(object, data, join, plural);
        }
      }
    }

    return object;
  },


  /**
   * This method will add a relation to a model where the related data is one record.
   *
   * @param object
   * @param data
   * @param join
   */
  addToOneRelation: function(object, data, join)
  {
    var childObject = this.propelSOA.getNewObject(join.namespace, join.bundle, join.entity);
    childObject = childObject.populateFromJSON(data[join.relation]);
    if ( childObject !== null )
    {
      childObject.query = this;

      if (join.joinList.length > 0)
      {
        childObject = this.buildObjectFromJoinSubtree(childObject, data[join.relation], join.joinList);
      }

    }

    object.relations[join.relation] = childObject;
  },


  /**
   * This method will add a relation to a model where the related data is a collection.
   *
   * @param object
   * @param data
   * @param join
   * @param plural
   */
  addToManyRelation: function(object, data, join, plural)
  {
    var recordsCount = data[plural].length;
    object.relations[plural] = this.propelSOA.getCollection(join.namespace, join.bundle, join.entity);
    object.relations[plural].query = this;

    for (recordIndex = 0; recordIndex < recordsCount; ++recordIndex)
    {
      var childObject = this.propelSOA.getNewObject(join.namespace, join.bundle, join.entity);
      childObject = childObject.populateFromJSON(data[plural][recordIndex]);
      childObject.query = this;

      if (join.joinList.length > 0)
      {
        childObject = this.buildObjectFromJoinSubtree(childObject, data[plural][recordIndex], join.joinList);
      }

      object.relations[plural].collection.push(childObject);
    }

    // register this collection with propelsoa so that it can get automatic updates
    this.propelSOA.registerCollection(object.relations[plural], join.namespace, join.bundle, join.entity);
  },


  /**
   * This method will re-run the query, and update the same scope we did initially with the new results.
   */
  refresh: function()
  {
    var deferred = this.propelSOA.$q.defer();
    var refreshContainer = this.resultsContainer;

    if (this.oneResult)
    {
      refreshContainer = {};
      refreshContainer[this.resultsContainerKey] = null;
    }

    this.runQuery(refreshContainer, this.resultsContainerKey, this.config).then(function(){
      if (this.oneRow)
      {
        // This means that the query did not update the original container, because the runQuery method
        // didn't know that we only want the first applicable record, so the main result should not be a collection
        this.resultsContainer[this.resultsContainerKey] = refreshContainer.results.collection.length > 0 ? tempContainer.results.collection[0] : null;
        deferred.resolve();
      }
    }, function()
    {
      deferred.reject();
    });

    return deferred.promise;
  }
});
