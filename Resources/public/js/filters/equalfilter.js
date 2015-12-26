
var PropelSOAEqualFilter = PropelSOABaseFilter.extend({

    init: function()
    {
        this._super();

        this.filterType = 'EQUAL';
        this.filterValue = '';
    },

    setFilterValue: function(newFilterValue)
    {
        this.filterValue = newFilterValue;
    }
});