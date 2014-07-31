if (typeof(JDB) == 'undefined' || ( ! JDB instanceof Object)) JDB = new Object();

if (typeof(JDB.SBGoogleMap) == 'undefined' || ( ! JDB.SBGoogleMap instanceof Object)) {

	JDB.SBGoogleMap = function() {

		function Trigger(map, event)
		{
			google.maps.event.trigger(map, event);
		}

		/**
		 * The Map "class" constructor.
		 *
		 * @param 	object 		init 			Initialisation object containing information essential to the construction of the map.
		 * @param 	object 		options		Map UI options.
		 */
		function Map(init, options) {

			// Check that we have the required information.
			if (init == null
				|| typeof(init) != 'object'
				|| typeof(init.map_container) == 'undefined'
				|| typeof(init.map_lat) == 'undefined'
				|| typeof(init.map_lng) == 'undefined'
				|| typeof(init.map_zoom) == 'undefined'
				|| typeof(init.pin_lat) == 'undefined'
				|| typeof(init.pin_lng) == 'undefined') {
					return false;
				}

			// Set the default map options.
			var map_options = {
				'ui_zoom'				: false,
				'ui_scale' 				: false,
				'ui_overview'			: false,
				'ui_map_type'			: false,
				'map_drag'				: false,
				'map_click_zoom'		: false,
				'map_scroll_zoom'		: false,
				'pin_drag'				: false,
				'background'			: '#FFFFFF',
				'map_types'				: '',
				'map_type_control'		: false,
				'map_type_default'		: 'normal',

				'ui_disable'			: false,
				'ui_pan'				: false,
				'ui_rotate'				: false,
				'ui_streetview'			: false
			}

			// An index containing the available map types.
			var valid_map_types = {
				'hybrid'			: google.maps.MapTypeId.HYBRID,
				'normal'			: google.maps.MapTypeId.ROADMAP,
				'physical'			: google.maps.MapTypeId.TERRAIN,
				'satellite'			: google.maps.MapTypeId.SATELLITE
			};


			// Override the default options.
			for (o in options) {
				if (map_options[o] != 'undefined') {
					map_options[o] = options[o];
				}
				if (map_options[o] == '') {
					map_options[o] = false;
				}
				if (map_options[o] == '1') {
					map_options[o] = true;
				}
			}

			default_map_type = valid_map_types[map_options['map_type_default']];

			map_center = new google.maps.LatLng(init.map_lat, init.map_lng);

			// Create the map.
			this.__map = new google.maps.Map(
					document.getElementById(init.map_container), {
						backgroundColor: map_options['background'],
						center: map_center,
						zoom: init.map_zoom,
						scrollwheel: map_options.map_scroll_zoom,
						disableDoubleClickZoom: map_options.map_click_zoom,
						draggable: map_options.map_drag,
						disableDefaultUI: map_options.ui_disable,
						mapTypeId: default_map_type,
						zoomControl: map_options.ui_zoom,
						scaleControl: map_options.ui_scale,
						overviewMapControl: map_options.ui_overview,
						mapTypeControl: map_options.ui_map_type,
						panControl: map_options.ui_pan,
						streetViewControl: map_options.ui_streetview,
						rotateControl: map_options.ui_rotate,
					});


			// A shortcut variable that we can reference in our function literals below.
			var t = this;

			this.__center = map_center;

			// Add the map "pin".
			this.__marker = new google.maps.Marker({
				position : new google.maps.LatLng(init.pin_lat, init.pin_lng),
				map : t.__map,
				draggable: map_options.pin_drag,
				autoPan: true,
				clickable: map_options.pin_drag
			});

			// Add an event listener to the map "pin".
			if (map_options.pin_drag) {
				this.__marker_listener = google.maps.event.addListener(this.__marker, 'dragend', function() {
					var pin_data = t.get_marker();
					t.set_location(pin_data);
				});
			}



			// If we have a "map_field", we need to update it every time our map changes.
			if (typeof(init.map_field) == 'string' && init.map_field.length) {

				this.__map_field = jQuery(document.getElementById(init.map_field));

				// Add the event listener.
				if (this.__map_field.length) {
					this.__map_listener = google.maps.event.addListener(this.__map, 'bounds_changed', function() {
						var map_data = t.get_location();
						var pin_data = t.get_marker();
						var field_data = map_data.latlng.lat() + '|' + map_data.latlng.lng() + '|' + map_data.zoom + '|' + pin_data.lat() + '|' + pin_data.lng();

						// This is to deal with a painful edge case
						// Check to see if the overlay is in place, if it is, dont update
						// this only really occurs during init but there's a race condition here
						if(t.__overlay_container.length > 0)
						{
							if(t.__overlay_container.is(':hidden')) {
								t.__map_field.val(field_data);
							}
						}
						else {
							t.__map_field.val(field_data);
						}
					});
				}
			}

			// If we have an "address_input" field, and an "address_submit" field, we need to
			// link these to our map.
			if (typeof(init.address_input) == 'string' && typeof(init.address_submit) == 'string' && init.address_input.length && init.address_submit.length) {
				this.__address_input = jQuery(document.getElementById(init.address_input));
				this.__address_submit= jQuery(document.getElementById(init.address_submit));


				if (this.__address_input.length && this.__address_submit.length) {
					this.__in_lookup = false;

					// Set a flag every time we enter or leave the address_input field.
					this.__address_input.unbind('focus').bind('focus', function(e) {
						t.__in_lookup = true;
					}).unbind('blur').bind('blur', function() {
						t.__in_lookup = false;
					});

					// Find the specified address.
					this.__address_submit.unbind('click').bind('click', function(e) {
						var address = jQuery.trim(t.__address_input.val());

						if( address !== '')
						{
							t.pinpoint_address(address, function() {
								// Update the map and pin data.
								var map_data = t.get_location();
								var pin_data = t.get_marker();
								var field_data = map_data.latlng.lat() + '|' + map_data.latlng.lng() + '|' + map_data.zoom + '|' + pin_data.lat() + '|' + pin_data.lng();
								t.__map_field.val(field_data);
							});
						}

						return false;
					}).parents('form').submit(function(e) {
						if (t.__in_lookup) {
							t.__address_submit.click();
							return false;
						}
					});
				}
			}

			if(typeof(init.pin_center_control) == 'string' && init.pin_center_control.length){
				this.__pin_center_control = jQuery(document.getElementById(init.pin_center_control));

				// Find the specified address.
				this.__pin_center_control.unbind('click').bind('click', function(e) {

					var map_data = t.get_location();
					var pin_data = t.get_marker();
					t.set_marker(map_data.latlng);
					return false;
				})
			}

			// If we have an "overlay_container" passed, link these, and attach
			if (typeof(init.overlay_container) == 'string' && init.overlay_container.length && typeof(init.overlay_reset) == 'string' && init.overlay_reset.length && typeof(init.overlay_field) == 'string' && init.overlay_field.length) {
				this.__overlay_container = jQuery(document.getElementById(init.overlay_container));
				this.__overlay_reset = jQuery(document.getElementById(init.overlay_reset));
				this.__overlay_field = jQuery(document.getElementById(init.overlay_field));

				// Matrix only passes the cell's required state as part of the display init js,
				// so we'll grab that now, and if it's required hide our overlay
				if( init.cell_required == true)
				{
					this.__overlay_container.hide();
					this.__overlay_reset.hide();
				}

				if(this.__overlay_container.length && this.__overlay_reset.length && this.__overlay_field) {

					this.__field_optional = true;


					// Now figure out the initial state of the optional field
					// if we have existing data from a save state, keep it open
					if( init.existing_data == true ) {
						t.__overlay_container.hide();
					}
					else {
						t.__overlay_field.attr('rel', t.__map_field.val());
						t.__map_field.val('');
					}

					this.__overlay_container.bind('click', function(e) {
						t.__overlay_container.hide();

						if(t.__map_field.val() == '') t.__map_field.val(t.__overlay_field.attr('rel'));
						t.__overlay_field.attr('rel', '');

						return false;
					});

					this.__overlay_reset.bind('click', function(e) {
						t.__overlay_container.show();

						t.__overlay_field.attr('rel', t.__map_field.val());
						t.__map_field.val('');
						return false;
					});
				}
			}

			return this;
		}


		/**
		 * Sets the map location and zoom level.
		 * @param 	int 	latlng 	A google.maps.LatLng object containing the marker's latitude and longitude.
		 * @param		int		zoom		The map zoom level.
		 * @return 	object 	An object containing the map's latitude, longitude, and zoom.
		 */
		Map.prototype.set_location = function(latlng, zoom) {
			if (this.__map) {
				this.__map.panTo(latlng);
			}
			return this.get_location();
		}

		/**
		 * Sets the map location and zoom level.
		 * @param 	int 	latlng 	A google.maps.LatLng object containing the marker's latitude and longitude.
		 * @param		int		zoom		The map zoom level.
		 * @return 	object 	An object containing the map's latitude, longitude, and zoom.
		 */
		Map.prototype.set_center = function(latlng) {
			if (this.__map) {
				this.__map.setCenter(latlng);
			}
			return this.get_location();
		}


		/**
		 * Gets the map location and zoom level.
		 * @return 	object 	An anonymous object with two properties: latlng (google.maps.LatLng object); zoom (integer).
		 */
		Map.prototype.get_location = function() {
			if (this.__map) {
				loc = this.__map.getCenter();
				return {
					latlng: loc,
					zoom: this.__map.getZoom()
				};
			} else {
				return false;
			}
		}


		/**
		 * Sets the location of the map marker.
		 * @param 	object		latlng				A google.maps.LatLng object, or an anonymous object with the properties lat and lng.
		 * @return 	object 		A google.maps.LatLng object containing the marker's latitude and longitude.
		 */
		Map.prototype.set_marker = function(latlng) {
			// Check the parameters.
			/*if (jQuery.isFunction(latlng.lat) == false) {
				if ((typeof(latlng.lat) == 'undefined') || (typeof(latlng.lng) == 'undefined')) {
					return false;
				} else {
					latlng = new google.maps.LatLng(latlng.lat, latlng.lng);
				}
			}*/
			this.__marker.setPosition(latlng);

			return this.get_marker();
		}


		/**
		 * Returns the latitude and longitude of the map marker. If no marker exists,
		 * return FALSE.
		 * @return 		object 		A google.maps.LatLng object containing the marker's latitude and longitude.
		 */
		Map.prototype.get_marker = function() {
			if (this.__marker) {
				loc = this.__marker.getPosition();
				return loc;
			} else {
				return false;
			}
		}


		/**
		 * Attempts to locate the given address on the map.
		 * @param 	string		address			The address to locate (can be a postcode).
		 * @param		function	callback		The function to call when we're all done here (optional).
		 */
		Map.prototype.pinpoint_address = function(address, callback) {
			if (jQuery.trim(address) == '') return;

			t = this;


			// Does this look like a lat/lng?
			if(address.indexOf(',') > 0) {
				parts = address.split(',');
				if(parts.length == 2) {
					if(!isNaN(parts[0]) && !isNaN(parts[1])) {
						attempt = new google.maps.LatLng(parts[0], parts[1]);

						t.set_location(attempt,10);
			        	t.set_marker(attempt);

			        	return;
					}
				}
			}


			// Create a new Geocoder object to help us find the address.
			geo = new google.maps.Geocoder();

			region = '';
			var map_data = t.get_location();
			// Get the region first via a reverse geocode

			geo.geocode({'latLng': map_data.latlng}, function(results,status){
				if (status == google.maps.GeocoderStatus.OK) {
					for(var a in results)
					{
						for(var k in results[a].address_components)
						{
							if(results[a].address_components[k].short_name != '')
							{
								region = results[a].address_components[k].short_name;
							}
						}
					}

					geo.geocode( { 'address': address, 'region' : region }, function(results, status) {
				      	if (status == google.maps.GeocoderStatus.OK) {
				        	t.set_location(results[0].geometry.location);
				        	t.set_marker(results[0].geometry.location);
				      	}
				    });

				}
				else
				{

					geo.geocode( { 'address': address}, function(results, status) {
				      if (status == google.maps.GeocoderStatus.OK) {
				        t.set_location(results[0].geometry.location);
				        t.set_marker(results[0].geometry.location);
				      }
				    });

				}
			});


		}

		Map.prototype.resize = function(){
			t = this;
			google.maps.event.trigger(t.__map,'resize');
		}

		// Return our publically-accessible object.
		return ({Map : Map});

	}();
}


function initialize() {
	maps = Array();

    if (typeof(JDB.google_maps) !== 'undefined' && JDB.google_maps instanceof Array) {
		for (var i in JDB.google_maps) {
			maps[i] = new JDB.SBGoogleMap.Map(JDB.google_maps[i].init, JDB.google_maps[i].options);
		}
	}

}


function initializeSingle(arrKey) {
	maps = Array();

	console.log('init single - '+arrKey);

    if (typeof(JDB.google_maps) !== 'undefined' && JDB.google_maps instanceof Array) {
		for (var i in JDB.google_maps) {
			if(i == arrKey) {
				console.log('found');
				maps[i] = new JDB.SBGoogleMap.Map(JDB.google_maps[i].init, JDB.google_maps[i].options);
			}
		}
	}
}


// Create the Google Maps.
jQuery(document).ready(function() {
   	google.maps.event.addDomListener(window, 'load', initialize);
});
