<script type="text/javascript">
{literal}
CRM.$(function() {

  function osm() {
    /**
     * Just a test function to verify that the js snippet
     * has been successfully injected into the page code
     */
    this.test = function() {
      console.log("de.systopia.osm: enabled");
    }
    /**
     * Returns all needed field values as an object
     */
    this.getFieldValues = function() {
      var address = {
        street_address: CRM.$('#address_1_street_address').val(),
        country_id: CRM.$('#s2id_address_1_country_id').select2('data').id,
        city: CRM.$('#address_1_city').val(),
        postal_code: CRM.$('#address_1_postal_code').val()
      };
      return address;
    }
    /**
     * Returns whether all required fields have been set
     */
    this.isReadyForLookUp = function() {
      var values = this.getFieldValues();
      if(values.street_address == "" ||
         values.country_id     == "" ||
         (values.city           == "" &&
         values.postalcode     == "")) {
           return false;
      }
      return true;
    }
    /**
     * Calls the lookup method when all required fields have been set
     */
    this.callWhenReady = function(event) {
      CRM.$(event.currentTarget).css({"border-radius":"",
                                      "border":""});
      if(this.isReadyForLookUp()) {
        this.query(this.getFieldValues());
      }else{
        this.setStatus("none");
      }
    }
    /**
     * Highlight a specified DOM element
     */
    this.highlightElement = function(target, style) {
      var color;
      switch(style) {
        case "ok":
          color = "#00FF00";
          break;
        case "warning":
          color = "#FF023E";
          break;
        case "error":
        default:
          color = "#FF0000";
      }
      target.css({"border-radius": "3px", "border": "2px solid " + color});
    };
    /**
     * Set a field value if it does not already contain a value.
     * If already set, highlight the field.
     */
    this.setValueIfEmpty = function(selector, value, override) {
      var field = CRM.$(selector);
      var val = field.val();
      if(val && !override && value !== val) {
        this.highlightElement(field, "warning");
      }else{
        field.val(value);
      }
    }
    /**
     * Set address field values
     */
    this.setFieldValues = function(address) {
      if(typeof address.street_address != "undefined") {
        this.setValueIfEmpty('#address_1_street_address', address.street_address, true);
      }
      if(typeof address.city != "undefined") {
        this.setValueIfEmpty('#address_1_city', address.city, true);
      }
      if(typeof address.postal_code != "undefined") {
        this.setValueIfEmpty('#address_1_postal_code', address.postal_code);
      }
      if(typeof address.country_id != "undefined") {
        CRM.$('#s2id_address_1_country_id').val(address.country_id).trigger("change");
      }
    }
    /**
     * Sets the current status indicator
     */
     this.setStatus = function(status) {
       var elem = CRM.$('#address_1_street_address').parent();
       var html_check = CRM.$('<a class="ui-icon ui-icon-circle-check osm_status_icon"></a>');
       var html_unknown = CRM.$('<a class="ui-icon ui-icon-help osm_status_icon"></a>');
       {/literal}
       var icon_busy = "{$config->resourceBase}i/loading.gif";
       {literal}
       var html_inprogress = CRM.$('<a class="osm_status_icon"><img src="' + icon_busy + '"></img></a>');
       var html_alert = CRM.$('<a class="ui-icon ui-icon-alert osm_status_icon"></a>');

       CRM.$('.osm_status_icon').remove();

       switch (status) {
         case "unknown":
          elem.append(html_alert);
          break;
         case "success":
          elem.append(html_check);
          break;
         case "in_progress":
          elem.append(html_inprogress);
          break;
         case "alert":
           elem.append(html_alert);
           break;
         case "none":
          break;
         default:
       }
     }
    /**
     * Queries the osm API
     */
     this.query = function(address) {
       var self = this;
       self.setStatus("in_progress");
       CRM.api3('OsmLookup', 'normalise', address)
        .done(function(result) {
          if(typeof result.is_error !== "undefined" && result.is_error) {
            self.setStatus("alert");
            var defaultOptions = {expires: 10000};
            switch(result.error_code) {
              case 1:
                CRM.alert(result.error_msg, ts('Error'), "warning", defaultOptions);
                break;
              case 2:
              case 3:
                CRM.alert(result.error_msg, ts('Server error'), "error", defaultOptions);
                break;
              default:
                console.log(result.error_msg);
                break;
            }
            return;
           }
          if(result._street_address_not_normalised) {
            self.setStatus("unknown");
          }else{
            self.setStatus("success");
          }
          self.setFieldValues(result);
        })
        .fail(function(result) {
          CRM.alert(result, ts('Server error'), "error");
        });
     }
     /**
      * Wait for events and call the lookup method when
      * all required information is available
      */
     this.enableTrigger = function() {
        var self = this;
        var watch = ['#address_1_street_address',
                     '#address_1_city',
                     '#address_1_postal_code',
                     '#s2id_address_1_country_id',
                    ];
        watch.forEach(function(element) {
          CRM.$(element).change(CRM.$.proxy(self.callWhenReady, self));
        });
     }
  }

  var osm = new osm();
  osm.test();
  osm.enableTrigger();
});
{/literal}
</script>
