// JavaScript Document
var AMG = AMG || {};
AMG.Lib = AMG.Lib || {};
(function($){
	
	//Media
	AMG.Lib.Media = Backbone.Model.extend( { 
		idAttribute: '_id',
		url: function() {
			return ajaxurl + '?' + $.param( { 
				action: 'amg-media', 
				media: encodeURIComponent( this.id ), 
				'_ajax_nonce' : encodeURIComponent( this.collection.nonce ),
				gallery: encodeURIComponent( this.collection.id ),
			} );
 		}
	} );
	
	//Gallery/Media Collection
	AMG.Lib.Gallery = Backbone.Collection.extend({
		model: AMG.Lib.Media,
		
		initialize: function( models, options ) {
			this.id = options.id;
			this.nonce = options.nonce;
			this.setType( options.type );
		},
		
		url: function() {
			return ajaxurl + '?' + $.param( { 
				action: 'amg-media', 
				gallery: encodeURIComponent( this.id ), 
				'_ajax_nonce' : encodeURIComponent( this.nonce )  
			} );
		},
		
		setType: function( type ) {
			var that = this;
			this.type =  _.contains( [ 'image', 'audio', 'video' ], type ) ? type: 'image' ;
			$.post( ajaxurl, { 
				action: 'amg-type', 
				gallery: encodeURIComponent( this.id ), 
				'_ajax_nonce' : encodeURIComponent( this.nonce ),
				type: encodeURIComponent( this.type )
			} )
			.done( function() {
				that.trigger( 'change:type', that );
				that.fetch();
			} );	
		},
		
		addMedia: function( data ) {
			//prevent duplicates
			var dup = this.findWhere( { id: data.id } ),
				maxOrdinal = this.length >0 ? this.max( function( m ){
					return m.get( 'ordinal' );	
				}).get( 'ordinal' ) : 0;
			if( !dup ) {
				data.ordinal = maxOrdinal+1;
				data.gallery = this.id;
				this.create( data );
			}
		},
		comparator: 'ordinal',
		getGalleryData: function() {
			return this.gallery;	
		}
	});
})(jQuery);