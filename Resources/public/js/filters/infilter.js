
var PropelSOAInFilter = PropelSOABaseFilter.extend({

    init: function()
    {
        this._super();

        this.filterType = 'IN';
        this.filterValue = '';
    },

    setFilterValue: function(newFilterValue)
    {
        this.filterValue = newFilterValue;
    }
});