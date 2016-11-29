(function($, Proud) {
  Proud.behaviors.proud_agency = {
    attach: function(context, settings) {
      var agency_settings = settings.proud_agency;
      if(agency_settings && agency_settings.agency_type_name) {

        var name = 'input[name="' + agency_settings.agency_type_name + '"]',
            builder;

        // Listen for panels setup, save builder
        $( document ).on('panels_setup', function(e, builderView, thingnew) {
          builder = builderView;
        });
        // Place agency at top
        $('#agency_section_meta_box').appendTo('#titlediv').css('margin-top', '1em');
        // Agency edit page

        // Switcher
        function changeType() {
          $('#agency_url_wrapper, #post_menu_wrapper, #wr_editor_tabs, .wr-editor-tab-content').hide();
          //if (isNewPost) {
          //  window.setTimeout(function(){$('#wr_editor_tabs a[href="#wr_editor_tab2"]').trigger('click');}, 1000);
          //}
          var type = $(name + ':checked').val();
          if (type == 'external') {
            $('#so-panels-panels').hide();
          }
          else if (type =='section') {
            activatePagebuilder('section');
          }
          else if (type =='page') {
            activatePagebuilder('page');
          }
        }
        function activatePagebuilder(type){
          // New post so process (otherwise let site-origin handle)
          if(agency_settings.isNewPost) {
            $('input[name="panels_data"]').val(agency_settings.agency_panels[type]);
            $('#content-panels').trigger('click');
            if(builder) {
              builder.model.loadPanelsData(JSON.parse(agency_settings.agency_panels[type]));
            }
          }
        }
        changeType();
        $(name).bind('click', changeType);
      }
    }
  };
})(jQuery, Proud);