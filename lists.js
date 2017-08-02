widget_list = "";

function removeWidget(element,id,string)	{
	$(element).parent().remove();
			
	$('#phonetic').append(			
		"<li id=\""+id+"\" class=\"block\">"+
		string+
		"</li>");

	$('#'+id).draggable();

	widget_split = widget_list.split(id+',');			
	widget_list = widget_split[0]+widget_split[1];
	$('#widget_list').val(widget_list);
	console.log($('#widget_list'));
}

$(document).ready(function(){
	widget_list = $('#widget_list').val();
	$(".block").draggable();

	$(".drop").droppable({
		accept: ".block",
		activeClass: 'droppable-active',
		hoverClass: 'droppable-hover',
		drop: function(ev, ui) {
			var item = ui.draggable[0];
		
			$(this).append(
				"<li style=\"overflow:hidden;\">"+
				"<span class=\"left\">"+item.innerHTML+"</span>"+
				"<a href=\"javascript:void(0);\" onclick=\"removeWidget(this,'"+item.id+"','"+item.innerHTML+"');\" class=\"delete\">&nbsp;</a>"+
				"</li>");
		
			$(item).remove();
			
			widget_list += item.id+",";
			$('#widget_list').val(widget_list);
		}
	});
});