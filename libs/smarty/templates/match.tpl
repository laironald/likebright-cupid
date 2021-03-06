<!doctype html>  
<!--[if lt IE 7 ]> <html class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html class="no-js ie8"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--> <html class="no-js"> <!--<![endif]-->
{assign var='ron' value=($fbook.uid=="2203233")}
{if $ron}{$fbook.me.profile.email}{/if}
<head>
  <meta property="og:title" content="Play likebright with me!" />
  <meta property="og:description" content="See friends' matches for you and vote on matches for them. No one will know that you voted or played." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="http://likebright.com/cupid/" />
  <meta property="og:image" content="http://likebright.com/cupid/images/likebright-square-big.jpg" />
  <meta property="og:site_name" content="likebright" />
  <meta property="fb:admins" content="2203233,211897" />
  
  <meta charset="utf-8">
  <title>likebright cupid</title>
  <meta name="description" content="">
  <meta name="author" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="/favicon.ico">

  <!-- CSS : implied media="all" -->
  <link rel="stylesheet" href="css/960.css">
  <link rel="stylesheet" href="css/style2.css?v=2">
  <link rel="stylesheet" href="css/style_match.css?v=2">
  <link rel="stylesheet" href="css/fancybox/jquery.fancybox-1.3.4.css">
  <script src="js/libs/modernizr-1.6.min.js"></script>
</head>

<body lang="en" >
  {include file="fbook/init.tpl"}
  <div id="header">
  	<div class="wrapper">
		<div class="wrapl">
			<div class="logo">
				<a href="?"><img src="images/likebright.png" class="logo" /></a> 
				<span class="beta">BETA</span>
			</div>
			{if $fbook.me}
				<div class="boxy gadd">
					<a href="?degree=0{$url.status}" {if $get.degree==0}class="select"{/if}>me</a>
					<a href="?degree=1{$url.status}" {if $get.degree==1}class="select"{/if}>friends</a>
					{* <a href="?degree=2{$url.status}" {if $get.degree==2}class="select"{/if}>+friends of friends</a> *}
				</div>
				<div class="boxy gadd">
					{* <a href="?status=s{$url.degree}" {if $get.status=="s"}class="select"{/if}>single</a> *}
					<a href="?status=x{$url.degree}" {if $get.status=="x"}class="select"{/if}>single+?</a>
					<a href="?status=a{$url.degree}" {if $get.status=="a"}class="select"{/if}>all</a>
				</div>
				{*
					<div class="boxy gadd">
						<a href="?gender=a{$url.degree}{$url.status}" {if $get.gender==""}class="select"{/if}>all</a>
						<a href="?gender=m{$url.degree}{$url.status}" {if $get.gender=="m"}class="select"{/if}>male</a>
						<a href="?gender=f{$url.degree}{$url.status}" {if $get.gender=="f"}class="select"{/if}>female</a>
					</div>
				*}
			{/if}
		</div>
		{if not $fbook.me}
			<div class="wrapr"><a href="{$fbook.login}"><img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif"></a></div>
		{else}
			<div class="wrapr">
				<fb:like href="http://likebright.com/cupid/" send="true" width="300" height="30" show_faces="false" font=""></fb:like>
			</div>
		{/if}
	</div>
  </div>

  <div id="container">
    <div id="main" role="main">
		<div id="canvas">
			{include file='matchit.tpl'}
		</div>
		{if not $fbook.me}

		{else}
			{*
				<a href="{$fbook.logout}"><img src="http://static.ak.fbcdn.net/rsrc.php/z2Y31/hash/cxrz4k7j.gif"></a>
				<div id="profile"><img src="images/load.gif" /></div>
				<div id="likes"><img src="images/load.gif" /></div>
				<div id="checkins">
					{if $fbook.me.p_checkin}
						<img src="images/load.gif" />
					{/if}
				</div>
			*}
		{/if}
    </div>
	<div id="footer"></div>
  </div>

  <div id="fb-root"></div>
  <script src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script>
  <script>FB.init({ appKey:'c6865a7ae2c9768b260344f8ab6d10be' });</script>

  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.0/jquery.js"></script>
  <script>!window.jQuery && document.write(unescape('%3Cscript src="js/libs/jquery-1.5.0.js"%3E%3C/script%3E'))</script>
  {* 
    <script src="http://ajax.microsoft.com/ajax/jquery.templates/beta1/jquery.tmpl.min.js"></script>
  	<script src="js/knockout-1.1.2.js"></script>
	    <script src="js/brew.js"></script>
  *}
  <script type="text/javascript" charset="utf-8">
    var is_ssl = ("https:" == document.location.protocol);
    var asset_host = is_ssl ? "https://s3.amazonaws.com/getsatisfaction.com/" : "http://s3.amazonaws.com/getsatisfaction.com/";
    document.write(unescape("%3Cscript src='" + asset_host + "javascripts/feedback-v2.js' type='text/javascript'%3E%3C/script%3E"));
  </script>
  <script type="text/javascript" charset="utf-8">
    var feedback_widget_options = {};
    feedback_widget_options.display = "overlay";  
    feedback_widget_options.company = "likebright";
    feedback_widget_options.placement = "right";
    feedback_widget_options.color = "#1F86A1";
    feedback_widget_options.style = "idea";
	var feedback_widget = new GSFN.feedback_widget(feedback_widget_options);
  </script>
  {* 
	<script src="js/underscore-min.js"></script>
    <script src="js/jquery.cycle.all.min.js"></script>
  *}
  <script src="js/plugins.js"></script>
  <script src="js/script2.js"></script>
  <script src="js/jquery.timers.min.js"></script>
  <script src="js/jquery.qtip.min.js"></script>
  <script src="js/jquery.fancybox-1.3.4.pack.js"></script>
  <script src="js/underscore-min.js"></script>
  <script>  
	$(document).ready(function() {
		match = $.parseJSON('{$matchJSON}');
		$("a.popup").live("mouseover", function(event) {
			$("a.popup").fancybox({
				'width'				: 600,
				'height'			: 400,
				'autoScale'			: true,
				'type'				: 'iframe',
				'titlePosition'		: 'over'
			});
		});

		{*
		{if $fbook.me}
			var query = FB.Data.query("SELECT uid, name FROM user WHERE uid in (SELECT uid2 FROM friend WHERE uid1 = me() LIMIT 5 OFFSET 0)");
			query.wait(function(rows) {
				alert(JSON.stringify(rows));
			});
		{/if}
		
		{if $fbook.me}
			FB.api('/me/friends', function(friends) {
				fidlist = _.map(friends["data"], function(v) { return v["id"]; });
				
				/*
				FB.api({
					method: "fql.query", 
					query: "SELECT uid, email, first_name, middle_name, last_name, name, pic_square, pic_big, affiliations, birthday_date, sex, meeting_sex, relationship_status, current_location, family FROM user WHERE uid in (SELECT uid1 FROM friend WHERE uid2=me())"
					//, activities, interests, music, tv, movies, books
				}, 
				function(fql) {
					alert("HELLO2");
					alert(JSON.stringify(fql));
				});
				FB.api({
					method: "fql.query", 
					query: "SELECT uid,music,tv,movies FROM user WHERE uid in (SELECT uid1 FROM friend WHERE uid2=me())"
					//, activities, interests, music, tv, movies, books
				}, 
				function(fql2) {
					alert("HELLO3");
					alert(JSON.stringify(fql2));
				});
				*/
			});
			alert("OK");
		{/if}
		*}
		
			{*
				$("#ppic a").fancybox({
					'width'				: 600,
					'height'			: 400,
					'autoScale'			: true,
					'type'				: 'iframe',
					'titlePosition'		: 'over'
				});
				$(".button a").live("mouseover", function(event) {
					$(".button a").fancybox({
						'width'				: 600,
						'height'			: 400,
						'autoScale'			: true,
						'type'				: 'iframe',
						'titlePosition'		: 'over'
					});
				});
			*}
		{if $ron}
		{/if}
	});
	{if $ron}
	{/if}
	
    var match = null;
	var pos = 0;
	var lock = false;
	
	$.fn.preload = function() {
		this.each(function(){
			$('<img/>')[0].src = this;
		});
	}
	function next(vote) {
		if (lock)
			return;
	
		if (pos == match.length - 1) {
			return;
		}
		
		prevPos = pos;
		prevVote = vote;
		$.ajax({
			type: "POST",
			url: "fetch.php?q=vote&scr={$fbook.me.screen}",
			data: "c="+match[pos]["c"]["uid"]+"&m1="+match[pos]["m1"]["uid"]+"&m2="+match[pos]["m2"]["uid"]+"&vote="+vote,
			dataType: "json",
			success: function(data) {
				//alert(data);
				$("#pmpic1 span.pct").html(Math.round(data["m1"]*100, 0));
				$("#pmpic2 span.pct").html(Math.round(data["m2"]*100, 0));
				
				if (Math.round(data["m1"]*100,0)==50) {
					$("#pmpic1 span.pct").css("background-color", "#999999");
					$("#pmpic2 span.pct").css("background-color", "#999999");
					$(".sub .scr").html("More votes needed.");
				} else {
					$("#pmpic1 span.pct").css("background-color", (data["m1"]>data["m2"])?"#dd3c10":"#3b5998");
					$("#pmpic2 span.pct").css("background-color", (data["m2"]>data["m1"])?"#dd3c10":"#3b5998");
					$(".sub .scr").html("Based on "+data["v"]+" votes.");
				}

				/*
					if (data["v"]>=10)
						$(".sub .scr").html("Based on "+data["v"]+" votes.");
					else
						$(".sub .scr").html("More votes needed.");
				*/
				{if $fbook.me}
					if (vote!=0) {
						$.ajax({
							type: "GET",
							url: "fetch.php?q=matchtops&status={$get.status}&degree={$get.degree}",
							success: function(data) {
								$("#matchtops").html(data);
							}
						});	
					}
				{/if}
			}
		});

		{if $fbook.me && $get.degree!=0}
			if (vote!=0)
				$.ajax({
					type: "GET",
					url: "fetch.php?q=matchbutton&c="+match[pos]["c"]["uid"]+"&m1="+match[pos]["m1"]["uid"]+"&m2="+match[pos]["m2"]["uid"]+"&m="+match[pos]["m"+vote]["uid"]+"&status={$get.status}&degree={$get.degree}",
					success: function(data) {
						$("#button").show();
						$("#button").html(data);
					}
				});
			else
				$("#button").html("");
		{/if}
		{*
				$("#button").hide();
		*}
		pos++;	
		{if $fbook.me}
			if (pos == match.length - 10) {
				$.getJSON("fetch.php?q=match&uid={$smarty.get.uid}{$url.all}", 
					function(data) { $.merge(match, data); });
			}
		{/if}
		
		if (pos < match.length - 1) {
			//$("#npic").attr("src", match[pos+1]["c"]["pic"]);
		}  else {
			return;
		}
		if (vote == 0) {
			$("#pmpic1 span.vte").slideUp();
			$("#pmpic2 span.vte").slideUp();
		} else {
			$("#pmpic"+(0+vote)+" span.vte").animate({ opacity: 0.50 });
			$("#pmpic"+(3-vote)+" span.vte").animate({ opacity: 0.00 });
			$("#pmpic1 span.vte").slideDown();
			$("#pmpic2 span.vte").slideDown();
		}

		var img = new Image();
		img.src = match[pos-1]["c"]["picB"];
		img.onload = function() {
			$("#ppic").css("height", 13+Math.min(100, 100/img.width*img.height));
			$("#ppic img").attr("src", match[pos-1]["c"]["picB"]);
			//$("#ppic a").attr("href", "http://www.facebook.com/profile.php?id=" + match[pos-1]["c"]["uid"]);
			{if $fbook.me}
				$("#ppic a").attr("href", "?uid=" + match[pos-1]["c"]["uid"] + "{$url.all}");
				$("#ppic a").attr("title", "Vote more for " + match[pos-1]["c"]["name"]);
			{/if}
		}
		fader = function(select, data) {
			if ($(select+" img").attr("src") != data["pic"]) {
				$(select+" img").attr("src", data["pic"]);
				$(select+" img").attr("title", data["name"]);
				//$(select+" a").attr("href", "http://www.facebook.com/profile.php?id="+data["uid"]);
/*			
				$(select).animate({ opacity: 0.40 }, "fast", function() {
					$(this).attr("src", data["picB"]);
					$(this).animate({ opacity: 0.80 }, "slow");
				});
*/				
			}
		};
		fader("#pmpic1", match[pos-1]["m1"]);
		fader("#pmpic2", match[pos-1]["m2"]);
		$("#mpic1 img").attr("src", match[pos]["m1"]["picB"]);
		$("#mpic2 img").attr("src", match[pos]["m2"]["picB"]);
		$("#mpic1 .name").html(match[pos]["m1"]["name"]);
		$("#mpic2 .name").html(match[pos]["m2"]["name"]);
		
		{if $fbook.me}
			$("#cpic .img").attr("src", match[pos]["c"]["pic"]);
			$("#cpic .img").attr("title", "Vote more for "+match[pos]["c"]["name"]);
			$("#cpic a").attr("href", "?uid="+match[pos]["c"]["uid"]+"{$url.all}");
			{if $get.degree!="0"}
				$(".votemore a").html("Vote more for<br/>"+match[pos-1]["c"]["name"]);
				$(".votemore a").attr("href", "?uid="+match[pos-1]["c"]["uid"]+"{$url.all}");
			{/if}
		{else}
			$("#cpic .img").attr("src", match[pos]["c"]["pic"]);
			$("#cpic .img").attr("title", match[pos]["c"]["name"]);
		{/if}
		$("#cname").html(match[pos]["c"]["name"]);
		
		if (pos == 1) {
			$(".match_base .start").hide();
			$(".match_base .prev").show();
			if (vote == 0) {
				$("#pmpic1 span.vte").hide();
				$("#pmpic2 span.vte").hide();
			}
		}
		
		lock = true;
		$(".select").toggleClass("selectB");
		
		window.setTimeout(function() {
			$(".select").toggleClass("selectB");
			lock = false;
		}, 1000);
	}
  </script>
  <script>  
      var _gaq=[['_setAccount','UA-23583988-1'],['_trackPageview']];
      (function(d,t) { var g=d.createElement(t),s=d.getElementsByTagName(t)[0];g.async=1;
      g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
      s.parentNode.insertBefore(g,s) } (document,'script'));
   </script>  
   
</body>
</html>
