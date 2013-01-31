varienGrid.prototype.initGrid = function(){
    if(this.preInitCallback){
        this.preInitCallback(this);
    }
    if($(this.containerId+this.tableSufix)){
        this.rows = $$('#'+this.containerId+this.tableSufix+' tbody tr.data-row');
        for (var row=0; row<this.rows.length; row++) {
            Event.observe(this.rows[row],'mouseover',this.trOnMouseOver);
            Event.observe(this.rows[row],'mouseout',this.trOnMouseOut);
            Event.observe(this.rows[row],'click',this.trOnClick);
            Event.observe(this.rows[row],'dblclick',this.trOnDblClick);
        }
    }
    if(this.sortVar && this.dirVar){
        var columns = $$('#'+this.containerId+this.tableSufix+' thead a');

        for(var col=0; col<columns.length; col++){
            Event.observe(columns[col],'click',this.thLinkOnClick);
        }
    }
    this.bindFilterFields();
    this.bindFieldsChange();
    if(this.initCallback){
        try {
            this.initCallback(this);
        }
        catch (e) {
            if(console) {
                console.log(e);
            }
        }
    }
};

$(document).observe('dom:loaded', function(){
	$('ecocode_minify_log_grid_table').select('.show-log-details').each(function(btn){
		var id = btn.readAttribute('data-id');
		btn.observe('click', function(){
			if(!$('log-details-' + id)) return;
			btn.toggleClassName('details-visible');
			$('log-details-' + id).toggle();
		});
	});
});