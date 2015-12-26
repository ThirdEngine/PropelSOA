/**
* Coders should take care to know that methods on a collection NEVER get sent back to the server. The
* canonical way to add and remove objects from the application are through the query and object classes.
* This class is called by those classes to keep existing collections up to date when those operations
* take place.
*/

var PropelSOACollection = Class.extend({

  init: function()
  {
    this.namespace = '';
    this.bundle = '';
    this.entity = '';

    this.resourceRoute = '';

    this.propelSOA   = null;
    this.query       = null;

    this.collection = [];
  },


  /**
   * This will use the query object to refresh the collection with the data from the database. The query
   * object should still have the same filters and information setup from the first query.
   */
  refreshTree: function()
  {
    if (!this.query)
    {
      throw "PROPELSOA_NO_QUERY_SOURCE";
    }

    return this.query.refresh();
  }
});