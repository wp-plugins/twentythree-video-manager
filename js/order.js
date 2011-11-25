$j=jQuery.noConflict();

$j(document).ready(function(){	
	$j("#turn_on_order").click(function(){	
		$j("#selected-videos li").css({
			'display': 'block',
			'cursor': 'move'
		});
		$j(this).text('Save order');
		$j("#selected-videos").sortable({
			items    : 'li',
			opacity: 0.4,
			appendTo : 'body',
			cursor: 'move',
			stop     : function (event, ui) {
				var order_values = "";						
				$j("#selected-videos li").each(function(){							
					 order_values += $j(this).attr('rel') + ",";
				});
				$j("#23video_plugin_order").val(order_values);
			}
		});
		$j(this).click(function(){
			$j("#save_tag").click();
		});
		
		$j("<div></div>")
			.html('<h4>Order instructions</h4><ol><li>Click and drag the video you want to move</li><li>Move it between the two videos where you want to put it and release it.</li><li>When you have decided upon an order, press \'Save order\'</li></ol>')	
			.css({
				'position':'absolute',
				'top' : '60px',
				'left': '350px'
			})
			.insertBefore("#selected-videos");
	});
	
	$j("input[name='23video_plugin_options[ui]']").click(function(){
		if ($j(this).val() == 'none') {
			$j("input[name='23video_plugin_options[ui_width]']").addClass('hidden').val('');
			$j("input[name='23video_plugin_options[ui_height]']").addClass('hidden').val('');
			$j("span.label").addClass('hidden');	
		} else	{
			$j("span.hidden, input.hidden").removeClass('hidden');
		}
	});
});