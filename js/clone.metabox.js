/*
 * to add a new input to a checkbox list in admin panel
 */
jQuery(document).ready(function($) {
	var elemsToClone = ['date','city','country','material'];

	$.each(elemsToClone,function(i,elem) {
		var containerId = "project_"+ elem;
		var elemToCloneName = "_basurama_project_"+ elem +"[]";
	
		// add button to add other field
		$("#"+ containerId +" .rwmb-meta-box .rwmb-checkbox_list-wrapper").append('<div id="'+ containerId +'-add-element" class="clone-metabox"><input type="text" value="" /><span class="button">+</span></div>');

	});

	// function to clone on click
	$(".clone-metabox span").click(function() {
		var cloneValue = $(this).parent().children("input").val();
		if ( cloneValue != '' ) {
			var elemToClone = $(this).closest(".rwmb-checkbox_list-wrapper").find(".rwmb-input input:last");
console.log(elemToClone);
			$(this).closest(".rwmb-checkbox_list-wrapper").children(".rwmb-input").append("<br />");
			$(this).closest(".rwmb-checkbox_list-wrapper").find(".rwmb-input input:last").clone().appendTo($(this).closest(".rwmb-checkbox_list-wrapper").children(".rwmb-input"));
			$(this).closest(".rwmb-checkbox_list-wrapper").children(".rwmb-input").append(cloneValue);
			$(this).closest(".rwmb-checkbox_list-wrapper").find(".rwmb-input input:last").attr("value",cloneValue).checked;
			$(this).parent().children("input").attr("value","");
		}
	});
});
