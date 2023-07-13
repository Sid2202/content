jQuery( document ).ready(function($) {
	"use strict";

	$('.google-fonts-list').each(function (i, obj) {
		if (!$(obj).hasClass('select2-hidden-accessible')) {
			$(obj).select2({
				allowClear: true,
				placeholder: $(obj).data('placeholder'),
			});
		}
	});

	$('.google-fonts-list').on('change', function() {

		var elementFontWeight = $(this).parent().parent().find('.google-fonts-fontweight-style');
		var elementSubsets = $(this).parent().parent().find('.google-fonts-subsets-style');
		var selectedFont = $(this).val();
		var customizerControlName = $(this).attr('control-name');

		// Clear Weight/Style dropdowns
		elementFontWeight.empty();
		elementSubsets.empty();


		// Get the Google Fonts control object
		var bodyfontcontrol = _wpCustomizeSettings.controls[customizerControlName];

		// For the selected Google font show the available weight/style variants
		if ( bodyfontcontrol.freeiofontslist[selectedFont] ) {
			$.each(bodyfontcontrol.freeiofontslist[selectedFont].variants, function(val, text) {
				elementFontWeight.append(
					$('<option></option>').val(text.id).html(text.name)
				);
				
			});
		}

		if ( bodyfontcontrol.freeiofontslist[selectedFont] ) {
			$.each(bodyfontcontrol.freeiofontslist[selectedFont].subsets, function(val, text) {
				elementSubsets.append(
					$('<option></option>').val(text.id).html(text.name)
				);
			});
		}


		freeioGetAllSelects($(this).parent().parent());
	});

	$('.google_fonts_select_control select').on('change', function() {
		freeioGetAllSelects($(this).parent().parent());
	});

	function freeioGetAllSelects($element) {
		var selectedFont = {
			fontfamily: $element.find('.google-fonts-list').val(),
			fontweight: $element.find('.google-fonts-fontweight-style').val(),
			subsets: $element.find('.google-fonts-subsets-style').val()
		};

		// Important! Make sure to trigger change event so Customizer knows it has to save the field
		$element.find('.customize-control-google-font-selection').val(JSON.stringify(selectedFont)).trigger('change');
	}


});