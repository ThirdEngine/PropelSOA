
var PropelSOABaseFilter = Class.extend({

    init: function()
    {
        this.filterType = '';
        this.field = '';
    },

    setField: function(newFieldName)
    {
        this.field = newFieldName;
    },

});
