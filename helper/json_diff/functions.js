
// JSON.parse + Error-Messages
var parseJson = function(id)
{
	$("#json" + id).removeClass("json-error");
	$("#json" + id + "errormessage").text("");
	try {
		var txt = $.trim($("#json" + id).val());
		if (txt[0] !== "{" && txt[0] !== "[") {
			return txt;
		}
		else {
			return JSON.parse(txt);
		}
	} 
	catch (err) {
		$("#json" + id).addClass("json-error");
		$("#json" + id + "errormessage").text(err + "");
	}
};

$(function()
{
	$("#toggle_expert").click(function(){
		$(".expert").toggle("slow");
	});
	
	
	$("#swap").click(function(){
		var t = $("#json1").val();
		$("#json1").val($("#json2").val());
		$("#json2").val(t);
		
		var t = $("#json1_out").val();
		$("#json1_out").val($("#json2_out").val());
		$("#json2_out").val(t);
		
		var l = $("#for_json1").html();
		$("#for_json1").html($("#for_json2").html());
		$("#for_json2").html(l);
		
		compare();
	});
	
	$("#clear").click(function(){
		$("#json1").val("");
		$("#json2").val("");
	});
	
	$("#showunchanged").change(function(){
		$(".jsondiffpatch-unchanged")[this.checked ? "slideDown" : "slideUp"]();
	});
	
	var compare = function()
	{
	
		var json1 = parseJson(1), json2 = parseJson(2);
		
		if (typeof json1 == "undefined" || typeof json2 == "undefined") {
			$(".results").hide();
			return;
		}
		
		var d = jsondiffpatch.diff(json1, json2);
		
		if (typeof d == "undefined") {
			$("#jsondiff").val("");
			$("#jsondifflength").text("0");
			$("#visualdiff").empty().text("no_diff");
			$(".jsondiff").hide();
		}
		else {
			$("#jsondiff").val(JSON.stringify(d, null, 2));
			$("#jsondifflength").text(Math.round(JSON.stringify(d).length / 102.4) / 10.0);
			$("#visualdiff").empty().append(jsondiffpatch.html.diffToHtml(json1, json2, d));
			$(".jsondiff").show();
		}
		$(".jsondiffpatch-unchanged")[$("#showunchanged").get(0).checked ? "show" : "hide"]();
		
		$(".results").show();
	};
	
	$("#compare").click(compare);
	
	$(".json-input").change(function(){
		if ($("#live").attr("checked")) {
			compare();
		}
	}).keyup(function(){
		if ($("#live").attr("checked")) {
			compare();
		}
	});
	
	$("#live").change(function(){
		if (this.checked) {
			compare();
		}
		$("#compare").attr("disabled", this.checked ? "disabled" : null);
	});
	$("#compare").attr("disabled", $("#live").get(0).checked ? "disabled" : null);
	$("#live").get(0).checked
	
	$("#json1pretty").click(function(){
		var json = parseJson(1);
		//$("#json1_out").val((typeof json == "string" ? json : JSON.stringify(json, null, 2)).replace(/\\\/g,""));
		$("#json1_out").val(typeof json == "string" ? json : JSON.stringify(json, null, 2));
	});
	
	$("#json2pretty").click(function(){
		var json = parseJson(2);
		//$("#json2_out").val((typeof json == "string" ? json : JSON.stringify(json, null, 2)).replace(/\\\/g,""));
		$("#json2_out").val(typeof json == "string" ? json : JSON.stringify(json, null, 2));
	});
	
	$(".results").hide();
	
	if ($("#live").get(0).checked) {
		compare();
	}
	$(".expert").hide();
});
