
var PropelSOAObject = Class.extend({

  /**
   * This method will build the default data needed by all services in Propel SOA.
   */
  init: function()
  {
    this.namespace = '';
    this.bundle = '';
    this.entity = '';

    this.standardResourceRoute = '';
    this.resourceRoutes = [];
    this.propelSOA = null;
    this.isPublic = false;

    this.fieldList            = [];
    this.linkedDataFieldNames = [];

    this.model           = {};
    this.linkedDataModel = {};

    this.relations = {};

    this.query = {};
  },

  /**
   * This returns the name of the primary key property name.
   *
   * @return string
   */
  getPkName: function()
  {
    return this.entity + 'Id';
  },

  /**
   * This method will return the primary key value of this object.
   *
   * @return int
   */
  getPk: function()
  {
    return this.model[this.getPkName()];
  },

  /**
   * This method will return the URL to the service endpoint.
   *
   * @param string routeKey
   * @return string
   */
  getResourceRoute: function(routeKey)
  {
    var route = typeof(routeKey) == 'undefined' ? this.standardResourceRoute : this.resourceRoutes[routeKey];

    if (this.isPublic)
    {
      route = "/public" + route;
    }

    return env_url + route;
  },

  /**
   * This method will make a call to an API endpoint. This is only for calls that commit data to the API. Calls
   * that need to query for data should go through the propelsoaquery interfaces.
   *
   * @param string resourceRoute
   * @param object data
   *
   * @param object $scope
   * @param string scopeVariable
   * @param config
   * @return promise
   */
  callApi: function(resourceRoute, data, $scope, scopeVariable, config)
  {
    config = typeof(config) == 'undefined' ? {} : config;
    var mainObject = this;
    config.url = resourceRoute;
    config.method = 'POST';
    config.data = data;

    // set up the promise so that the calling code can read our ID from the results when the request finishes
    var deferred = this.propelSOA.$q.defer();
    this.propelSOA.$http(config).then( function(json, status, headers, config)
    {
      if (typeof(json.data.Data.pk) != 'undefined') {
        mainObject.model[mainObject.getPkName()] = json.data.Data.pk;
      }

      if (typeof($scope) !== 'undefined' && typeof(scopeVariable) !== 'undefined')
      {
        $scope[scopeVariable] = json.data.Data;
      }

      deferred.resolve(json.data);
    },
    function(reason)
    {
      deferred.reject();
    });

    return deferred.promise;
  },

  /**
   * This method will generate the data object used for the standard save method.
   *
   * @return object
   */
  generateGenericObject: function(allowedFields)
  {
    var genericObject = {};

    for(var index = 0; index < allowedFields.length; ++index)
    {
      var fieldName  = allowedFields[index];
      genericObject[fieldName] = this.model[fieldName];
    }

    return genericObject;
  },

  /**
   * This method will populate an object based on JSON returned by a query.
   *
   * @param recordJSON
   * @return this
   */
  populateFromJSON: function(recordJSON)
  {
    if ( recordJSON === null )
    {
      return null;
    }
    var populatedObject = this;

    var pkFieldName = this.getPkName();
    var recordId = recordJSON[pkFieldName];

    if (recordId)
    {
      populatedObject = this.propelSOA.getNewObject(this.namespace, this.bundle, this.entity, recordId);
    }

    for(var index = 0; index < populatedObject.fieldList.length; ++index)
    {
      var fieldName = populatedObject.fieldList[index];
      var fieldIndex = populatedObject.fieldList.indexOf(fieldName);

      if (fieldIndex != -1)
      {
        populatedObject.model[fieldName] = recordJSON[fieldName];
      }
    }

    this.propelSOA.registerObject(populatedObject);
    return populatedObject;
  },

  /**
   * This method will return an element from the relation array.
   *
   * @param relationName
   * @return object
   */
  getRelation: function(relationName)
  {
    if (this.relations[relationName])
    {
      return this.relations[relationName];
    }

    return null;
  },

  /**
   * This method will send a delete request to the API.
   *
   * @param config
   * @return promise
   */
  delete: function(config)
  {
    config = typeof(config) == 'undefined' ? null : config;
    var mainObject = this;

    // set up the promise so that the calling code can read our ID from the results when the request finishes

    var deferred = this.propelSOA.$q.defer();

    var pk = this.getPk();
    var url = this.getResourceRoute() + "/" + pk;

    this.propelSOA.$http.delete(url, config).then( function(json, status, headers, config)
    {
      // first, make sure we remove this record from any collections since the record doesn't exist anymore
      this.propelSOA.removeDeletedRecordsFromRegisteredCollections(mainObject.namespace, mainObject.bundle, mainObject.entity, pk);

      deferred.resolve(json.data);
    },
    function(reason)
    {
      deferred.reject();
    });

    return deferred.promise;
  },

  /**
   * This will use the query object to refresh the results tree with the data from the database. The query
   * object should still have the same filters and information setup from the first query.
   */
  refreshTree: function()
  {
    if (!this.query)
    {
      throw "PROPELSOA_NO_QUERY_SOURCE";
    }

    return this.query.refresh();
  },

  /**
   * This will import field values from another object. This is useful for stripping cruft off of a propel soa object to get
   * ready to save it to the database.
   */
  importFields: function (dataObject)
  {
    for (var index = 0; index < this.fieldList.length; ++index) {
      var fieldName = this.fieldList[index];

      if (typeof(dataOjbect[fieldName]) != 'undefined') {
        this.model[fieldName] = dataObject[fieldName];
      }
    }
  }
});
