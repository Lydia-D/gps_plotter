jQuery(document).ready(function($) {
  var pluginUrl = map_js_vars.plugin_url;
  var selectRoute = document.getElementById("selectRoute");
  var map = document.getElementById("map-canvas");
  var intervalID = 0;
  var zoom = 10;
  var autoRefresh = false;
  var UsernameArray = [];
  var viewingAllRoutes = false;

  var map_type = "ROADMAP";

  /* demo response
  var bws_demo = {"type": "FeatureCollection", "features": [{"type": "Feature", "id": "8BA21D90-3F90-407F-BAAE-800B04B1F5ED", "properties": {"speed": 0, "direction": 0, "distance": 0.0, "location_method": "na", "gps_time": "Jan 3 2007 11:43AM", "user_name": "wordpressUser1", "phone_number": "1BA21D90-3F90-407F-BAAE-800B04B1F5ED", "accuracy": 137, "geojson_counter": 1, "extra_info": "na"}, "geometry": {"type": "Point", "coordinates": [-122.2145580, 47.6366310]}},{"type": "Feature", "id": "8BA21D90-3F90-407F-BAAE-800B04B1F5ED", "properties": {"speed": 0, "direction": 0, "distance": 0.0, "location_method": "na", "gps_time": "Jan 3 2007 11:44AM", "user_name": "wordpressUser1", "phone_number": "1BA21D90-3F90-407F-BAAE-800B04B1F5ED", "accuracy": 137, "geojson_counter": 2, "extra_info": "na"}, "geometry": {"type": "Point", "coordinates": [-122.2017690, 47.6379610]}},{"type": "Feature", "id": "8BA21D90-3F90-407F-BAAE-800B04B1F5ED", "properties": {"speed": 0, "direction": 0, "distance": 0.0, "location_method": "na", "gps_time": "Jan 3 2007 11:45AM", "user_name": "wordpressUser1", "phone_number": "1BA21D90-3F90-407F-BAAE-800B04B1F5ED", "accuracy": 137, "geojson_counter": 3, "extra_info": "na"}, "geometry": {"type": "Point", "coordinates": [-122.2095790, 47.6429350]}}]};
  */

  //getAllRoutesForMap(); // assume the first marker on load is created here via call to loadGPSLocations
  getLastPosition();
  loadRoutesIntoDropdownBox(); //
  // load last position onto map

  $("#viewall").click(function() {
    getAllRoutesForMap();
  });

  $("#bws_satelite").click(function() {
    if (map_type == "ROADMAP") {
      map_type = "SATELLITE";
      $("#bws_satelite").html("Roadmap View");
    } else {
      map_type = "ROADMAP";
      $("#bws_satelite").html("Satellite View");
    }

    if (viewingAllRoutes) {
      getAllRoutesForMap();
    } else {
      if (hasMap()) {
        getRouteForMap();
      }
    }
  });

  $("#selectRoute").on("change", function() {
    if (hasMap()) {
      viewingAllRoutes = false;

      getRouteForMap();
    }
  });

  $("#autorefresh").click(function() {
    if (autoRefresh) {
      turnOffAutoRefresh();
    } else {
      turnOnAutoRefresh();
    }
  });

  $("#delete").click(function() {
    if (hasMap()) {
      deleteRoute();
    }
  });

  $("#refresh").click(function() {
    if (viewingAllRoutes) {
      getAllRoutesForMap();
    } else {
      if (hasMap()) {
        getRouteForMap();
      }
    }
  });

  function setTheme() {
    //var bodyBackgroundColor = $('body').css('backgroundColor');
    //$('.container').css('background-color', bodyBackgroundColor);
    //$('body').css('background-color', '#ccc');
    // $('head').append('<link rel="stylesheet" href="style2.css" type="text/css" />');
  }

  function getLastPosition() {
    // when the page first loads create map and display last known position

    viewingAllRoutes = false;
    //selectRoute.selectedIndex = 0;
    showPermanentMessage("Please select a route below");

    $.post(
      map_js_vars.ajax_url,
      {
        action: "get_last_position",
        get_last_position_nonce: map_js_vars.get_last_position_nonce
      },
      function(response) {
        /* Runing on demo data bws_demo*/
        /* Lyd edit: turn off LoadGPSLocations to checkif it doesnt load the map until a route is selected*/
        loadGPSLocations(response);
      }
    );
    }

  function getAllRoutesForMap() {
    // when the page first loads, get the routes from the DB and load them into the dropdown box.

    viewingAllRoutes = false;
    //selectRoute.selectedIndex = 0;
    showPermanentMessage("Please select a route below");

    $.post(
      map_js_vars.ajax_url,
      {
        action: "get_all_geojson_routes",
        get_all_geojson_routes_nonce: map_js_vars.get_all_geojson_routes_nonce
      },
      function(response) {
        /* Runing on demo data bws_demo*/
        loadGPSLocations(response);
        //loadGPSLocations(bws_demo);
      }
    );
  }

  function loadRoutesIntoDropdownBox() {
    $.post(
      map_js_vars.ajax_url,
      {
        action: "get_routes",
        get_routes_nonce: map_js_vars.get_routes_nonce
      },
      function(response) {
        loadRoutes(response);
      }
    );
  }

  function loadRoutes(json) {
    // console.log(JSON.stringify(json));

    if (json.length == 0 || json == "0") {
      showMessage("There are no routes available to view");
      map.innerHTML = "";
    } else {
      // create the first option of the dropdown box
      var option = document.createElement("option");
      option.setAttribute("value", "0");
      option.innerHTML = "Select Route...";
      selectRoute.appendChild(option);

      // when a user taps on a marker, the position of the sessionID in this array is the position of the route
      // in the dropdown box. it's used below to set the index of the dropdown box when the map is changed
      UsernameArray = [];

      // iterate through the routes and load them into the dropdwon box.
      $(json.routes).each(function(key, value) {
        var option = document.createElement("option");
        option.setAttribute("value", $(this).attr("user_name"));
        option.innerHTML =
          $(this).attr("user_name") + "  " + $(this).attr("times");
        selectRoute.appendChild(option);

        UsernameArray.push($(this).attr("user_name"));
      });

      // need to reset this for firefox
      selectRoute.selectedIndex = 0;

      showPermanentMessage("Please select a route below");
    }
  }

  function getRouteForMap() {
    $.post(
      map_js_vars.ajax_url,
      {
        action: "get_geojson_route",
        session_id: $("#selectRoute").val(),
        get_geojson_route_nonce: map_js_vars.get_geojson_route_nonce
      },
      function(response) {
        /* Runing on demo data bws_demo*/
        loadGPSLocations(response);
        //loadGPSLocations(bws_demo);
      }
    );
  }

  function loadGPSLocations(geojson) {
    //console.log(JSON.stringify(geojson));

    if (geojson.length == 0 || geojson == "0") {
      showMessage("There is no tracking data to view");
      map.innerHTML = "";
    } else {
      var finalLocation = false;

      if (map.id == "map-canvas") {
        // clear any old map objects
        document.getElementById("map-canvas").outerHTML =
          "<div id='map-canvas'></div>";

        // use leaflet (http://leafletjs.com/) to create our map and map layers
        var gpsPlotterMap = new L.map("map-canvas");

        var openStreetMapsURL =
          ("https:" == document.location.protocol ? "https://" : "http://") +
          "{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";
        var openStreetMapsLayer = new L.TileLayer(openStreetMapsURL, {
          attribution:
            '&copy;2014 <a href="http://openstreetmap.org">OpenStreetMap</a> contributors'
        });

        // need to get your own bing maps key, http://www.microsoft.com/maps/create-a-bing-maps-key.aspx
        // var bingMapsLayer = new L.BingLayer("GetAKey");
        // var googleMapsLayer = new L.Google('ROADMAP');
        //console.log(map_type);
        var googleMapsLayer = new L.Google(map_type);
        //var googleMapsLayer = new L.Google('HYBRID');

        // this fixes the zoom buttons from freezing
        // https://github.com/shramov/leaflet-plugins/issues/62
        L.polyline([[0, 0]]).addTo(gpsPlotterMap);

        // this sets which map layer will first be displayed, go ahead and change it to bingMapsLayer or openStreetMapsLayer to see
        //gpsPlotterMap.addLayer(googleMapsLayer);
        gpsPlotterMap.addLayer(openStreetMapsLayer);

        // this is the switcher control to switch between map types (upper right hand corner of map)
        gpsPlotterMap.addControl(
          new L.Control.Layers(
            {
              // 'Bing Maps':bingMapsLayer,
              //"Google Maps": googleMapsLayer
              OpenStreetMaps: openStreetMapsLayer
            },
            {}
          )
        );
      }

      var locationArray = [];

      for (var i = 0; i < $(geojson.features).length; i++) {
        var longitude = geojson.features[i].geometry.coordinates[0];
        var latitude = geojson.features[i].geometry.coordinates[1];

        var tempLocation = new L.LatLng(latitude, longitude);
        locationArray.push(tempLocation);

        if (i == $(geojson.features).length - 1) {
          //gpsPlotterMap.setView(new L.LatLng(latitude, longitude), zoom);
          finalLocation = true;

          if (!viewingAllRoutes) {
            displayCityName(latitude, longitude);
          }
        }

        var marker = createMarker(
          latitude,
          longitude,
          geojson.features[i].id, // session_id
          geojson.features[i].properties.speed,
          geojson.features[i].properties.direction,
          geojson.features[i].properties.distance,
          geojson.features[i].properties.location_method,
          geojson.features[i].properties.gps_time,
          geojson.features[i].properties.user_name,
          geojson.features[i].properties.accuracy,
          geojson.features[i].properties.extra_info,
          gpsPlotterMap,
          finalLocation
        );
      }

      var track = L.polyline(locationArray, {color: "red"}).addTo(gpsPlotterMap);
      // fit markers within window
      if ($(geojson.features).length == 1) {
        gpsPlotterMap.setView(tempLocation, zoom);
      } else {
        var bounds = new L.LatLngBounds(locationArray);
        gpsPlotterMap.fitBounds(bounds);
      }
      // restarting interval here in case we are coming from viewing all routes
      if (autoRefresh) {
        restartInterval();
      }
    }
  }

  // check to see if we have a map loaded, don't want to autoRefresh or delete without it
  function hasMap() {
    if (selectRoute.selectedIndex == 0) {
      // means no map
      //console.log(false);
      return false;
    } else {
      //console.log(true);
      return true;
    }
  }

  function createMarker(
    latitude,
    longitude,
    session_id,
    speed,
    direction,
    distance,
    locationMethod,
    gpsTime,
    userName,
    accuracy,
    extraInfo,
    map,
    finalLocation
  ) {
    var iconUrl;

    if (finalLocation) {
      iconUrl = pluginUrl + "public/assets/images/coolred_small.png";
    } else {
      iconUrl = pluginUrl + "public/assets/images/coolgreen2_small.png";
    }

    var markerIcon = new L.Icon({
      iconUrl: iconUrl,
      shadowUrl: pluginUrl + "public/assets/images/coolshadow_small.png",
      iconSize: [12, 20],
      shadowSize: [22, 20],
      iconAnchor: [6, 20],
      shadowAnchor: [6, 20],
      popupAnchor: [-3, -25]
    });

    var lastMarker = "</td></tr>";

    // when a user clicks on last marker, let them know it's final one
    if (finalLocation) {
      lastMarker =
        '</td></tr><tr><td colspan=2 style="text-align:center"><b>Final location</b></td></tr>';
    }

    // convert from meters to feet
    accuracy = parseInt(accuracy * 3.28);

    var popupWindowText =
      '<table border=0 cellspacing="0" cellpadding="0" id="popupTable">' +
      "<tr><td align=right>&nbsp;</td><td>&nbsp;</td><td rowspan=2 align=right>" +
      "<img src=" +
      pluginUrl +
      "public/assets/images/" +
      getCompassImage(direction) +
      ".jpg alt= />" +
      lastMarker +
      "<tr><td align=right>Speed:&nbsp;</td><td>" +
      speed +
      " mph</td></tr>" +
      "<tr><td align=right>Distance:&nbsp;</td><td>" +
      distance +
      " mi</td><td>&nbsp;</td></tr>" +
      "<tr><td align=right>Time:&nbsp;</td><td colspan=2>" +
      gpsTime +
      "</td></tr>" +
      "<tr><td align=right>UserName:&nbsp;</td><td>" +
      userName +
      "</td><td>&nbsp;</td></tr>" +
      "<tr><td align=right>Accuracy:&nbsp;</td><td>" +
      accuracy +
      " ft</td><td>&nbsp;</td></tr></table>";

    var gpsplotterMarker;
    var title = userName + " - " + gpsTime;

    // make sure the final red marker always displays on top
    if (finalLocation) {
      gpsplotterMarker = L.marker(new L.LatLng(latitude, longitude), {
        title: title,
        icon: markerIcon,
        zIndexOffset: 999
      })
        .bindPopup(popupWindowText)
        .addTo(map);
    } else {
      gpsplotterMarker = L.marker(new L.LatLng(latitude, longitude), {
        title: title,
        icon: markerIcon
      })
        .bindPopup(popupWindowText)
        .addTo(map);
    }

    // if we are viewing all routes, we want to go to a route when a user taps on a marker instead of displaying popupWindow
    if (viewingAllRoutes) {
      gpsplotterMarker.unbindPopup();

      gpsplotterMarker.on("click", function() {
        viewingAllRoutes = false;

        var indexOfRouteInRouteSelectDropdwon =
          UsernameArray.indexOf(userName) + 1;
        selectRoute.selectedIndex = indexOfRouteInRouteSelectDropdwon;
        showPermanentMessage("Please select a route below");

        if (autoRefresh) {
          restartInterval();
        }

        $.post(
          map_js_vars.ajax_url,
          {
            action: "get_geojson_route",
            userName: userName,
            get_geojson_route_nonce: map_js_vars.get_geojson_route_nonce
          },
          function(response) {
            loadGPSLocations(response);
          }
        );
      }); // on click
    }
  }

  function getCompassImage(azimuth) {
    if ((azimuth >= 337 && azimuth <= 360) || (azimuth >= 0 && azimuth < 23))
      return "compassN";
    if (azimuth >= 23 && azimuth < 68) return "compassNE";
    if (azimuth >= 68 && azimuth < 113) return "compassE";
    if (azimuth >= 113 && azimuth < 158) return "compassSE";
    if (azimuth >= 158 && azimuth < 203) return "compassS";
    if (azimuth >= 203 && azimuth < 248) return "compassSW";
    if (azimuth >= 248 && azimuth < 293) return "compassW";
    if (azimuth >= 293 && azimuth < 337) return "compassNW";

    return "";
  }

  function displayCityName(latitude, longitude) {
    var lat = parseFloat(latitude);
    var lng = parseFloat(longitude);
    var latlng = new google.maps.LatLng(lat, lng);
    reverseGeocoder = new google.maps.Geocoder();
    reverseGeocoder.geocode({ latLng: latlng }, function(results, status) {
      if (status == google.maps.GeocoderStatus.OK) {
        // results[0] is full address
        if (results[1]) {
          reverseGeocoderResult = results[1].formatted_address;
          showPermanentMessage(reverseGeocoderResult);
        } else {
          console.log("No results found");
        }
      } else {
        console.log("Geocoder failed due to: " + status);
      }
    });
  }

  function deleteRoute() {
    // comment out these two lines to get delete working
    // var answer = confirm("Disabled here on test website, this works fine.");
    // return false;

    var answer = confirm(
      "This will permanently delete this route\n from the database. Do you want to delete?"
    );

    if (answer) {
      $.post(
        map_js_vars.ajax_url,
        {
          action: "delete_route",
          session_id: $("#selectRoute").val(),
          delete_route_nonce: map_js_vars.delete_route_nonce
        },
        function(response) {
          map.innerHTML = "";
          selectRoute.length = 0;

          loadRoutesIntoDropdownBox();
          getAllRoutesForMap();
        }
      );
    } else {
      return false;
    }
  }

  // message visible for 7 seconds
  function showMessage(message) {
    // if we show a message like start auto refresh, we want to put back our current address afterwards
    var tempMessage = $("#messages").html();

    $("#messages").html(message);
    setTimeout(function() {
      $("#messages").html(tempMessage);
    }, 7 * 1000); // 7 seconds
  }

  function showPermanentMessage(message) {
    $("#messages").html(message);
  }

  function turnOffAutoRefresh() {
    showMessage("Auto Refresh Off");
    $("#autorefresh").val("Auto Refresh Off");

    autoRefresh = false;
    clearInterval(intervalID);
  }

  function turnOnAutoRefresh() {
    showMessage("Auto Refresh On (1 min)");
    $("#autorefresh").val("Auto Refresh On");
    autoRefresh = true;

    restartInterval();
  }

  function restartInterval() {
    // remember that if someone is viewing all routes and then switches to a single route
    // while autorefresh is on then the setInterval is going to be running with getAllRoutesForMap
    // and not getRouteForMap

    clearInterval(intervalID);

    if (viewingAllRoutes) {
      intervalID = setInterval(getAllRoutesForMap, 60 * 1000); // one minute
    } else {
      intervalID = setInterval(getRouteForMap, 60 * 1000);
    }
  }

  // for debugging, console.log(objectToString(map));
  function objectToString(obj) {
    var str = "";
    for (var p in obj) {
      if (obj.hasOwnProperty(p)) {
        str += p + ": " + obj[p] + "\n";
      }
    }
    return str;
  }
});
