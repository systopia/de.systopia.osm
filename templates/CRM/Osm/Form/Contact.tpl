<script type="text/javascript">
{literal}
function osm() {
  /**
   * Just a test function to verify that the js snippet
   * has been successfully injected into the page code
   */
  this.test = function() {
    console.log("de.systopia.osm: js snippet injection successful");
  }
  /**
   * Returns all needed field values as an object
   */
  this.getFieldValues = function() {
    var address = {
      street_address: CRM.$('#address_1_street_address').val(),
      country: CRM.$('#s2id_address_1_country_id').select2('data').text,
      city: CRM.$('#address_1_city').val(),
      postalcode: CRM.$('#address_1_postal_code').val()
    };
    return address;
  }
  /**
   * Queries the osm API
   */
   this.query = function(address) {
     CRM.api3('OsmLookup', 'call', address)
     .done(function(results) {
            results.forEach(function(entry) {

            });
          });
   }
}

var osm = new osm();
osm.test();
{/literal}
</script>
