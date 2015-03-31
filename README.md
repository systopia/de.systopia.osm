#SYSTOPIA OpenStreetMap CiviCRM GeoCoder

This CiviCRM extension adds the Nominatim OpenStreetMap server as a geocoding option. Just install the extension, and activate via "Administer" => "System Settings" => "Mapping and Geocoding".

Disclaimer: This CiviCRM extension requests geodata from nominatim.osm.org, a service of OpenStreetMap Foundation. We have been advised that OSM servers might not be suitable for massive data requests, but so far could not get precise information on what this exactly means. Also, we don't know what the consequences of requests too massive for the OSM infrastructure might be. Therefore we recommend that you consider using the throttling option for the "Geocode and Parse Adresses" cronjob if processing data sets containing more than 10.000 addresses.

Find the German README [HERE](https://github.com/systopia/de.systopia.osm/blob/master/README_DE.md):

Links:
*   Open-Street-Map-Project:    http://www.openstreetmap.org
*   Open-Street-Map-Server:     http://nominatim.openstreetmap.org/
*   API specs:                  http://wiki.openstreetmap.org/wiki/API_v0.6
