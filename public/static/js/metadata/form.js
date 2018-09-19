var currentLeafletMap = null; // https://stackoverflow.com/questions/25627666/how-can-i-get-the-map-object-for-a-leaflet-map-from-the-id-of-the-div-element

function validateDate(date, field)
{
    //https://stackoverflow.com/questions/36009414/regex-to-validate-date-yyyy-mm-dd-while-typing-on-keyup/36009595
    var state = 'inValid';
    var regex = /^\d{0,4}$|^\d{4}-0?$|^\d{4}-(?:0[1-9]|1[012])(?:-(?:0[1-9]|[12]\d|3[01])?)?$/;
    var isValid = regex.test(date);

    if (isValid) {
        state = 'valid';
        // Full date (yyyy-mm-dd)
        if (date.length == 10) {
            var parts = date.split('-');
            var day = parts[2];
            var month = (parts[1] - 1);
            var year = parts[0];

            // Compare date
            var dateObject = new Date(year, month, day);
            var result = dateObject.getDate() == day && dateObject.getMonth() == month && dateObject.getFullYear() == year;

            if (!result) {
                state = 'inValid';
            }
        } else if (date.length != 4 && date.length != 7) { // check if date is yyyy or yyyy-mm
            state = 'inValid';
        }

        // Year must be 1600 or higher
        if (date.substr(0, 4) < 1600) {
            state = 'inValid';
        }

        // Empty input
        if (date.length == 0) {
            state = 'valid'
        }
    }

    // Date is not valid
    if (state == 'inValid') {
        $(field).addClass('invalid');
        validateForm();
        return false;
    }

    $(field).removeClass('invalid');
    validateForm();
    return true;
}

function loadMap(map_element)
{
    var map = L.map(map_element, {
        center: [48.760, 13.275],
        zoom: 4
    });

    // Add OSM & Google maps layer control.
    var osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    var osmAttrib = '&copy; <a href="http://openstreetmap.org/copyright">OpenStreetMap</a> contributors';
    var osm = L.tileLayer(osmUrl, { maxZoom: 18, attribution: osmAttrib });
    var baseLayers = {
        "OpenStreetMap": osm.addTo(map),
        "Google Maps": L.tileLayer('https://www.google.cn/maps/vt?lyrs=r@189&gl=cn&x={x}&y={y}&z={z}&hl=en', {
            attribution: 'google'
        }),
        "Google Maps Satellite": L.tileLayer('https://www.google.cn/maps/vt?lyrs=s@189&gl=cn&x={x}&y={y}&z={z}', {
            attribution: 'google'
        })
    };
    var overlays = {};
    var options = {
        position: 'topright',
        collapsed: false
    };

    var layerscontrol = L.control.layers(baseLayers, overlays, options).addTo(map);

    var drawnItems = L.featureGroup().addTo(map);

    var drawControlFull = new L.Control.Draw({
        edit: {
            featureGroup: drawnItems
        },
        draw: {
            circle: false,
            polygon: false,
            marker: false,
            circlemarker: false,
            polyline: false
        }
    });

    var drawControlEditOnly = new L.Control.Draw({
        edit: {
            featureGroup: drawnItems
        },
        draw: false
    });

    var mapContainer = map.getContainer();
    var inputs = $(mapContainer).closest('.input-group').find('input[type=hidden]');

    if ($(inputs).eq(0).val() != '') {
        // define rectangle geographical bounds
        var bounds = [
            [$(inputs).eq(3).val(), $(inputs).eq(0).val()],
            [$(inputs).eq(2).val(), $(inputs).eq(1).val()]
        ];
        // create an orange rectangle
        var layer = L.rectangle(bounds).addTo(map);
        drawnItems.addLayer(layer);
        map.addControl(drawControlEditOnly);
        map.fitBounds(bounds, {'padding': [150, 150]});
    } else {
        map.addControl(drawControlFull);
    }


    map.on(L.Draw.Event.CREATED, function (event) {
        var layer = event.layer;

        drawnItems.addLayer(layer);

        map.removeControl(drawControlFull);
        map.addControl(drawControlEditOnly);

        var mapContainer = map.getContainer();
        var inputs = $(mapContainer).closest('.input-group').find('input[type=hidden]');
        var inputGroup = $(mapContainer).parents('.modal').closest('.input-group');

        $(inputs).eq(3).val(layer.getLatLngs()[0][2].lat); //north
        $(inputs).eq(0).val(layer.getLatLngs()[0][2].lng); //west
        $(inputs).eq(2).val(layer.getLatLngs()[0][0].lat); //south
        $(inputs).eq(1).val(layer.getLatLngs()[0][0].lng); //east

        $(inputGroup).find('span.north').text(layer.getLatLngs()[0][2].lat); //north
        $(inputGroup).find('span.west').text(layer.getLatLngs()[0][2].lng); //west
        $(inputGroup).find('span.south').text(layer.getLatLngs()[0][0].lat); //south
        $(inputGroup).find('span.east').text(layer.getLatLngs()[0][0].lng); //east

    });

    map.on(L.Draw.Event.DELETED, function (event) {
        // Count rectangles after 'save'
        var rectangleCount = 0;
        drawnItems.eachLayer(function (layer) {
            if (layer instanceof L.Rectangle) {
                rectangleCount++;
            }
        });

        if (rectangleCount == 0) {
            map.addControl(drawControlFull);
            map.removeControl(drawControlEditOnly);

            var mapContainer = map.getContainer();
            var inputs = $(mapContainer).closest('.input-group').find('input[type=hidden]');
            var inputGroup = $(mapContainer).parents('.modal').closest('.input-group');

            $(inputs).eq(3).val(''); //north
            $(inputs).eq(0).val(''); //west
            $(inputs).eq(2).val(''); //south
            $(inputs).eq(1).val(''); //east

            $(inputGroup).find('span.north').text(''); //north
            $(inputGroup).find('span.west').text(''); //west
            $(inputGroup).find('span.south').text(''); //south
            $(inputGroup).find('span.east').text(''); //east
        }
    });

    map.on(L.Draw.Event.EDITED, function (event) {
        var layers = event.layers;
        layers.eachLayer(function (layer) {
            var mapContainer = map.getContainer();
            var inputs = $(mapContainer).closest('.input-group').find('input[type=hidden]');
            var inputGroup = $(mapContainer).parents('.modal').closest('.input-group');

            $(inputs).eq(3).val(layer.getLatLngs()[0][2].lat); //north
            $(inputs).eq(0).val(layer.getLatLngs()[0][2].lng); //west
            $(inputs).eq(2).val(layer.getLatLngs()[0][0].lat); //south
            $(inputs).eq(1).val(layer.getLatLngs()[0][0].lng); //east

            $(inputGroup).find('span.north').text(layer.getLatLngs()[0][2].lat); //north
            $(inputGroup).find('span.west').text(layer.getLatLngs()[0][2].lng); //west
            $(inputGroup).find('span.south').text(layer.getLatLngs()[0][0].lat); //south
            $(inputGroup).find('span.east').text(layer.getLatLngs()[0][0].lng); //east
        });
    });

    return map;
}

function loadReadOnlyMap(map_id)
{
    var map = L.map(map_id, {
        center: [48.760, 13.275],
        zoom: 4
    });

    // Add OSM & Google maps layer control.
    var osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    var osmAttrib = '&copy; <a href="http://openstreetmap.org/copyright">OpenStreetMap</a> contributors';
    var osm = L.tileLayer(osmUrl, { maxZoom: 18, attribution: osmAttrib });
    //var googleLayer = new L.Google('ROADMAP');
    var baseLayers = {
        "OpenStreetMap": osm.addTo(map),
        "Google Maps": L.tileLayer('https://www.google.cn/maps/vt?lyrs=r@189&gl=cn&x={x}&y={y}&z={z}&hl=en', {
            attribution: 'google'
        }),
        "Google Maps Satellite": L.tileLayer('https://www.google.cn/maps/vt?lyrs=s@189&gl=cn&x={x}&y={y}&z={z}', {
            attribution: 'google'
        })
        //"Google": googleLayer.addTo(map)
    };
    var overlays = {};
    var options = {
        position: 'topright',
        collapsed: false
    };

    var layerscontrol = L.control.layers(baseLayers, overlays, options).addTo(map);

    var drawControlEditOnly = new L.Control.Draw({
        draw: false
    });

    var mapContainer = map.getContainer();
    var inputKey = $(mapContainer).data('key');

    var data = $( "input[name='"+inputKey+"[northBoundLatitude]']" );
    if (data.val() != '') {
        // define rectangle geographical bounds
        var bounds = [
            [$("input[name='" + inputKey + "[northBoundLatitude]']").val(), $("input[name='" + inputKey + "[westBoundLongitude]']").val()],
            [$("input[name='" + inputKey + "[southBoundLatitude]']").val(), $("input[name='" + inputKey + "[eastBoundLongitude]']").val()]
        ];
        // create an orange rectangle
        var layer = L.rectangle(bounds).addTo(map);
        map.addControl(drawControlEditOnly);
        map.fitBounds(bounds, {'padding': [150, 150]});
    }

    return map;
}
