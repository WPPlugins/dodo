function widget_show(select,cat_or_tag)
{
	if(cat_or_tag == 0)
	{
		if(select == 0)
		{
			document.getElementById('widget_pl').style.display = "none";
			document.getElementById('widget_sp').style.display = "block";		
		}
		else
		{
			document.getElementById('widget_sp').style.display = "none";
			document.getElementById('widget_pl').style.display = "block";
		}
	}else{
		if(select == 0)
		{
			document.getElementById('widget_pl_t').style.display = "none";
			document.getElementById('widget_sp_t').style.display = "block";		
		}
		else
		{
			document.getElementById('widget_sp_t').style.display = "none";
			document.getElementById('widget_pl_t').style.display = "block";
		}		
	}
}