<div class="crm-block crm-form-block">
  <div>
    <table id="system_settings" class="form-layout">
      <tr>
         <td class="label">{$form.enable_js_lookup.label}</td>
          <td>
            {$form.enable_js_lookup.html}
            <br>
            <span class="description">{ts}Address lookup and autocomplete assistant{/ts} <a class="helpicon" onclick='CRM.help("{ts}Enable Lookup{/ts}", {literal}{"id":"id-enable-lookup","file":"CRM\/Admin\/Form\/Setting\/OsmSettings"}{/literal}); return false;' href="#" title="{ts}Help{/ts}" class="helpicon">&nbsp;</a></span>
          </td>
      </tr>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </div>
</div>
