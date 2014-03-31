// JavaScript Document
var AMG = AMG || {};
AMG.Lib = AMG.Lib || {};

(function($){
	/*
	 * Views 
	 */
	 
	//MediaView
	AMG.Lib.MediaView = Backbone.Marionette.ItemView.extend({
		
		isSelected: false,			
		
		events: {
			'click' : 'clickMedia',
			'dblclick': 'openEditLink',
			'select' : 'selectMedia',
			'unselect' : 'unselectMedia',
			'toggleSelect' : 'toggleSelectMedia',
			'click .amg-media-remove': 'removeMedia'
		},
		
		tagName: 'li',
		className: function() {
			var classes = this.model.get('type'),
				orientation = this.model.get( 'orientation' );
			classes += this.isSelected ? ' selected' : '';
			if( typeof orientation !== 'undefined' ) {
				classes += ' ' + orientation;
			}
			return classes;
		},
		
		template: function( data ) {
			var tmpl;
			switch( data.type ) {
				case 'image':
					tmpl = $( '#amg-image-tmpl' ).html();
				break;
					
				case 'audio':
					tmpl = $( '#amg-audio-tmpl' ).html();
				break;
				
				case 'video':
					tmpl = $( '#amg-video-tmpl' ).html();
				break;
				
				default:
					tmpl = '';	
			}
			
			return _.template( tmpl, data, { 
				variable: 'media',
				evaluate:    /<#([\s\S]+?)#>/g,
				interpolate: /\{\{([^\}]+?)\}\}(?!\})/g,
				escape:       /\{\{\{([\s\S]+?)\}\}\}/g
			} );;
		}, 
		
		clickMedia: function( e ) {
			this.trigger( 'click', { toggle: e.ctrlKey, range: e.shiftKey } );
		},
		
		openEditLink: function( e ) {
			window.open( this.model.get( 'editLink') );	
		},
		
		selectMedia: function() {
			this.isSelected = true;
			this.changeSelectClass();
			this.trigger( 'update:select' );
		},
		
		unselectMedia: function() {
			this.isSelected = false;
			this.changeSelectClass();
			this.trigger( 'update:select' );
		},
		
		toggleSelectMedia: function() {
			if( !this.isSelected )
				this.triggerMethod( 'select' );
			else
				this.triggerMethod( 'unselect' );
		},
		
		updateOrder: function( index ) {
			var that = this,
				deferred = $.Deferred();
			this.model.set( 'ordinal', index );
			return this.model.save().done( function() {
				that.render();
				that.trigger( 'update:order' );
			} );
		},
		
		changeSelectClass: function() {
			if( this.isSelected )
				this.$el.addClass( 'selected' );
			else
				this.$el.removeClass( 'selected' );	
		},
		
		removeMedia: function() {
			var that = this;
			that.unselectMedia();
			this.$el.on( "animationend webkitAnimationEnd oAnimationEnd MSAnimationEnd", function(){
				that.model.destroy( { wait: true } );
			} ).addClass( "removed" );
			return false;
		}
	});	
	
	//GalleryView
	AMG.Lib.MediaListView = Backbone.Marionette.CollectionView.extend({
		itemView: AMG.Lib.MediaView,
		tagName: "ul",
		className: "amg-media-list",
		itemViewEventPrefix: "media",
		
		initialize: function() {
			var that = this;
			this.lastSelected = '';
			this.$el.sortable({
				delay: 150,
				update: function( event, ui ) {
					that.sortMedia();
				},
				tolerance: 'pointer'
			});
		},
	
		itemEvents: {
			click: function( e, view, options ){
				if( options.range && ( 'undefined' !== typeof this.lastSelected ) ) {
					this.clearAll();
					this.selectRange( this.lastSelected, view );
				} else {
					if( options.toggle )
						view.toggleSelectMedia();
					else {
						this.clearAll();
						view.selectMedia();	
					}
					this.lastSelected = view;
				}
			},
			
			'update:select': function() {
				this.trigger( 'update:select', this.getSelected() )
			},
		},
		
		sortMedia: function() {
			var that = this,
			deferredSort = this.children.map( function( view ) {
				var index = that.$el.children( 'li' ).index( view.$el );
				return view.updateOrder( index );
			} );
			
			$.when.apply($, deferredSort).then( function() {
				that.trigger( 'gallery:sort' );	
			});
		},
		
		clearAll: function() {
			this.children.each( function( view ) {
				view.unselectMedia();
			});
		},
		
		selectRange: function( start, end ){
			var mediaElements = this.$el.children( 'li' ),
				startIndex = mediaElements.index( start.$el ) , 
				endIndex =  mediaElements.index( end.$el ), 
				selectGroup = mediaElements.slice(
				 	Math.min( startIndex, endIndex ),
				  	Math.max( startIndex, endIndex ) + 1
				);
			selectGroup.each( function( index ){
				$( this ).trigger( 'select' );	
			});
		},
		
		getSelected: function() {
			return this.children.filter( function( view ){ 
				return view.isSelected;
			} );
		},
		
		removeSelected: function() {
			var selected =  this.getSelected();
			_.each( selected, function( view ){ 
				view.removeMedia();
			} );	
		}
	});
	
	
	//AddMedia View
	AMG.Lib.AddMediaView = Backbone.Marionette.ItemView.extend({
		template: '#amg-media-add-tmpl',
		tagName: "button",
		className: "amg-add-media-button button",
		attributes: {
			href: "#",
			title: "Add Media"
		},
		
		initialize: function() {
			
		},
		
		events: {
			'click':  function() {
				var that = this,
					labels = {};
				this.frame = wp.media( {
					frame: 'select',
					className: 'media-frame amg-media-frame',
					multiple : true,
					title    : 'Select or upload media',
					library  : {
						type: that.collection.type
					},
					filterable: true				
				} );
				
				this.frame.on( 'select', function() {
					var selection = that.frame.state().get( 'selection' ).toJSON();
					_.each( selection, function( media ){
						that.collection.addMedia( media );
					} );
				});
				this.frame.open();	
				return false;
			}
		}
	});
	
	AMG.Lib.GalleryTypeView = Backbone.Marionette.ItemView.extend({
		template: '#amg-gallery-type-tmpl',
		tagName: "select",
		className: "amg-gallery-type-select",
		attributes: {
			name: '_amg_gallery_type'
		},
		onRender: function() {
			this.$el.val( this.collection.type );
		},
		events: {
			'change':  function( event ) {
				var type = $( event.target ).val();
				this.collection.setType( type );
			}
		}
	});
	
	//Action Bar Layout
	AMG.Lib.ActionBarLayout = Backbone.Marionette.Layout.extend({
		template: "#amg-action-tmpl",
		className: 'amg-action-layout' ,

		regions: {
			add: '#add-region',
			bulk: '#bulk-region'
		}
	});
	
	AMG.Lib.BulkActionView =  Backbone.Marionette.ItemView.extend({
		className: 'amg-bulk-bar',
		
		template: '#amg-bulk-tmpl',
		
		initialize: function() {
			this.$el.hide();
		},
		
		ui: {
			remove: '.remove',
			clear: '.clear'	
		},
		
		triggers: {
			'click @ui.remove': 'removeSelected',
			'click @ui.clear': 'clearSelected',	
		}
	});
})(jQuery);