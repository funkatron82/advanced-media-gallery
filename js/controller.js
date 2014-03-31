//Advanced Media Gallery Controller
var AMG = AMG || {};
AMG.Lib = AMG.Lib || {};

AMG.Lib.Controller = Marionette.Controller.extend( {
	initialize: function( options ) {
		this.collection = new AMG.Lib.Gallery( null, options );	
		this.createRegions();
		this.createViews();
		this.setRegions();
		this.setEvents();
	},
	
	createRegions: function() {
		this.regions = {
			actionBar: new Backbone.Marionette.Region( {
				el: "#amg-action-bar"
			} ),
			
			content: new Backbone.Marionette.Region( {
				el: "#amg-gallery-content"
			} ),	
		
			type: new Backbone.Marionette.Region( {
				el: "#amg-type-wrapper"
			} )
		};
	},
	
	createViews: function() {
		this.views = {
			collection: new AMG.Lib.MediaListView( { collection: this.collection } ),
			actionBar: new AMG.Lib.ActionBarLayout(),
			bulkBar: new AMG.Lib.BulkActionView(),
			type: new AMG.Lib.GalleryTypeView( { collection: this.collection } ),
			add: new AMG.Lib.AddMediaView( { collection: this.collection } )
		};
	},
	
	setRegions: function() {
		//Main Regions
		this.regions.content.show( this.views.collection );
		this.regions.actionBar.show( this.views.actionBar );
		this.regions.type.show( this.views.type );
		
		//Action Bar
		this.views.actionBar.add.show( this.views.add );
		this.views.actionBar.bulk.show( this.views.bulkBar );		
	},
	
	setEvents: function() {
		var that = this;
		this.views.collection.on( 'update:select', function( views ){ 
			if( views.length > 0 )
				that.views.bulkBar.$el.show();
			else
				that.views.bulkBar.$el.hide();
		});
		
		this.views.bulkBar.on( 'removeSelected', function() {
			that.views.collection.removeSelected();
		} );
		
		this.views.bulkBar.on( 'clearSelected', function() {
			that.views.collection.clearAll();
		} );	
	}
} );