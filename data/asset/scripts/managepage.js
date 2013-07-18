/**
 * Created with JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-1-27
 * Time: 下午8:27
 * To change this template use File | Settings | File Templates.
 */
$LAB.script(window.jQueryPath)
	.wait(function() {
jQuery(document).ready(function () {
	jQuery("head").append('<link rel="stylesheet" href="./data/asset/scripts/lib/jquery-ui/themes/base/jquery.ui.all.css"><style>.ui-state-highlight { height: 1.5em; line-height: 1.2em;margin:2px;padding:5px; } .ui-sortable{min-height:30px;}</style>');
	jQuery('.title.flinks').css({background:'#F0F0F0 url(./template/dps_yeei_dream_ext/image/common/title.png) repeat-x 0 100%'});
	jQuery('.flink_block').css({border:'1px solid #ccc', margin:10})
	$LAB
		.script("./data/asset/scripts/lib/jquery-ui/ui/jquery.ui.core.js").wait()
		.script("./data/asset/scripts/lib/jquery-ui/ui/jquery.ui.widget.js").wait()
		.script("./data/asset/scripts/lib/jquery-ui/ui/jquery.ui.mouse.js").wait()
		.script('./data/asset/scripts/lib/jquery-ui/ui/jquery.ui.sortable.js')
		.wait(function () {
			jQuery('.portal_block_summary li').addClass('ui-state-default').css({padding:5, margin:2, overflow:'hidden', whiteSpace:'nowrap'})
			jQuery(".portal_block_summary ul").sortable({
				placeholder:"ui-state-highlight",
				connectWith:'.portal_block_summary ul',
				stop:function (event, ui) {
					console.log(ui);
				}
			});
			jQuery(".portal_block_summary ul").disableSelection();
		})
		.script('./data/asset/scripts/jquery.json-2.4.js');

})});

function flashPosition(){
	var _update = {};
	jQuery('.flink_block').each(function(){
		//alert(jQuery(this).children(':first-child').attr('groupid'));
		var groupid = jQuery(this).children(':first-child').attr('groupid');
		var i=0;
		jQuery(this).children(':first-child').next().children().each(function(){
			//alert(jQuery(this).attr('uid'));
			_update['uid'+jQuery(this).attr('uid')]='d'+i++ +'g'+groupid;
		})
	})
	jQuery.ajax({
		type:"POST",
		url:'ajax.php',
		data:_update,
		success:function(data,textStatus,jqXHR){
			//alert('textStatus:'+textStatus+'<br />'+'data:'+data+'<br />jqXHR:'+jqXHR);
			//alert(data)
			//alert(jqXHR.responseText)
		}
	})
	//console.log(_update);
}

function listtojson(){
	var objs = [];
	jQuery('.flink_block').each(function(){
		var obj = {};
		var links = [];
		obj.name = (jQuery(this).children(':first-child').children(':first-child').text());

		jQuery(this).children(':first-child').next().children().each(function(){
			//alert(jQuery(this).attr('uid'));
			links.push({href:jQuery(this).children(':first-child').attr('href'),text:jQuery(this).children(':first-child').text()});
		})
		obj.links = links;
		objs.push(obj);
	});
	//alert(jQuery.toJSON(objs));
	//return;
	jQuery.ajax({
		type:"POST",
		url:'ajax2.php',
		contentType : 'application/json',
		data:jQuery.toJSON(objs),
		success:function(data,textStatus,jqXHR){
			//alert('textStatus:'+textStatus+'<br />'+'data:'+data+'<br />jqXHR:'+jqXHR);
			document.body.innerHTML += data;
			//alert(data)
			//alert(jqXHR.responseText)
		},
		error:function(data,textStatus,jqXHR){
			alert('textStatus:'+textStatus+'<br />'+'data:'+data+'<br />jqXHR:'+jqXHR);
		}
	})
}