/**
 * Created with JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-1-27
 * Time: 下午8:51
 * To change this template use File | Settings | File Templates.
 */

(function(w, d) {w.isOldIE = !-[1, ];w.lib = './data/asset/scripts/lib/';
w.jQueryPath = w.lib+'jquery/jquery.' + (w.isOldIE ? 'i' : 'n') + 'e.js';
require.config({
	waitSeconds:20,
	paths:{
		jquery: w.jQueryPath.slice(0, -3)
	}
});
//$LAB.script(w.jQueryPath).wait(function() {w.jQuery = jQuery.noConflict();/**/})
//document.writeln('<scr'+'ipt type="text/javascript" src="'+(w.jQueryPath)+'"> </scr'+'ipt> ');
//document.writeln('<scr'+'ipt type="text/javascript">window.jQuery = jQuery.noConflict();</scri'+'pt> ');
})(window, document);
//做web前端开发的，最头疼的就是IE，他是每个前端心中永远的痛。其实微软为我们提供了一些版本识别的接口，可以在javascript中使用，下面我就来谈谈这些接口的使用方法；
//1.JScript条件编译
//微软特立独行的JScript和原生的Javascript其实有很多不同点，利用条件编译，可以很方便的区别JScript和Javascript。废话不多，直接上代码
/*@cc_on
 // alert('IE中可见');
 @*/
//这段代码在IE中会弹出对话框；
/*@cc_on
 @if ( @_jscript )
 //alert('IE中可见');
 @else @*/
//alert('其他浏览器中可见');
/*@end @*/
//这段代码可在对话框中显示你使用的是IE还是非IE
/*@cc_on
 //alert(@_jscript_version);
 @*/
//这段代码会弹出你的JScript版本号。其中对应关系为:IE10=10，IE9=9，IE8=5.8，IE7=5.7，IE6=5.6或5.7，IE5.5=5.5
//当用户为Windows XP安装了JScript5.7补丁后，IE6的@_jscript_version可能为5.7，而不是5.6,这一点与IETester不同。
//条件编译在所有你见过的IE版本下均通用。
//2.IE的quirks模式。
//当HTML中没有声明DOCTYPE时，IE会自动工作在“quirks”模式下，即一种类似IE5渲染方法的模式。当使用IE6及以上版本时，我们可以使用document.compatMode来进行识别这一模式。
//alert(document.compatMode);
//alert(document.documentMode)
//怪癖模式下弹出“BackCompat”，正常模式下弹出“CSS1Compat”
//3.浏览器兼容模式
//IE8及其以上版本的浏览器为用户提供了浏览器兼容性视图，即用户在浏览网页时如果页面不能正常显示，可以尝试以低版本IE的模式下工作。从IE8开始，我们可以使用document.documentMode来获取当前模式。
//我们可以写这样一段代码：alert(document.documentMode); 在IE中打开，然后再开发者工具中切换“浏览器模式”来查看效果。
//通过实验我们可以发现，IE在正常模式下，弹出当前版本号，是个整数，兼容模式下，弹出所兼容的版本号，，怪癖模式下弹出“5”.