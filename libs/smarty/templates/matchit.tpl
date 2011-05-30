{*
	jQuery:
	Check out ColorBox plugin
	Check out Slidy plugin
	Check out Cycle plugin
	Check out Slides plugin
*}
<div>
	<div class="grid_7 alpha omega toptop">
		{if $wings}
			You have <a class="popup" href="fetch?q=friendlist" title="Your friends on likebright.">{$wings.count} friend{if $wings.count!=1}s{/if}</a> on likebright. 		
			{if $smarty.get.uid!=""} &nbsp; <a href="?{$url.all}">Vote for Everyone.</a>{/if}
			<div class="friendlist">
				{foreach from=$wings.faces item=frd}
					{if $frd.status=="" || $frd.status=="Single"}				
						<a href="?uid={$frd.uid}{if $frd.status==""}{$url.degree}&status=x{else}{$url.all}{/if}">
							<img src="{$frd.pic}" title="Vote more for {$frd.name}" />
						</a>
					{else}
						<img class="nostat" src="{$frd.pic}" title="{$frd.name}" />
					{/if}
				{/foreach}
			</div>
		{/if}
	</div>
	<div class="clear"></div>	
	<div class="match_base grid_9">
		{if $match}			
			<div class="grid_7 push_2 alpha omega">		
				<div class="header">
					<div id="cpic" class="profile">
						{if $fbook.me}
							<a href="?{if !$smarty.get.uid}uid={$match[0].c.uid}{/if}{$url.all}">
								<img class="img {if $smarty.get.uid}imglock{/if}" src="{$match[0].c.pic}" title="{if !$smarty.get.uid}Vote more for {$match[0].c.name}{else}Vote for Everyone{/if}" />
							</a>
						{else}
							<img class="img" src="{$match[0].c.pic}" title="{$match[0].c.name}" />
						{/if}
					</div>
					<div class="text">
						<font class="f1">Who is a better date for</font><br/>
						<font id="cname" class="f2">{$match[0].c.name}</font>
						{if !$fbook.me}
							<br/><font class="f0"><a href="$fbook.login">Connect with Facebook</a> to see your friends.</font>
						{/if}
					</div>
					<div class="ques">?</div>
				</div>
				<img onclick="next(0);" class="next" src="images/arrow-right{if $ie}-ex{/if}.png" />
			</div>
			<div class="clear"></div>
			<div class="grid_2 alpha">
				<div class="prev">
					<div id="ppic" class="main grid_2 alpha omega">
						<span>PREVIOUS</span>
						{if $fbook.me}
							<a target="_blank" href="#"><img src="{$match[0].c.picB}" title="" /></a>
						{else}
							<img src="{$match[0].c.picB}" />
						{/if}
					</div>
					<div class="clear"></div>
					<div class="sub grid_2 alpha omega">
						<div class="grid_2 alpha omega">
							<div id="pmpic1" {if not $ie}style="margin-right: 4px"{/if}>
								<img src="{$match[0].m1.pic}" title="{$match[0].m1.name}" />
								<span class="pct"></span>
								<span class="vte">your vote</span>
							</div>
							<div id="pmpic2">
								<img src="{$match[0].m2.pic}" title={$match[0].m2.name}"" />
								<span class="pct"></span>
								<span class="vte">your vote</span>
							</div>
						</div>
						<div class="clear"></div>
						<div class="grid_2 alpha omega">
							<span class="scr"></span>
						</div>
					</div>
					<div class="clear"></div>
					<div class="grid_2 alpha omega votemore">
						{if $fbook.me}
							{if !$smarty.get.uid}
								<a href="?uid={$match[0].c.uid}{$url.all}">Vote more for<br/>{$match[0].c.name}</a>
							{else}
								<a href="?{$url.all}">Vote for Everyone</a>							
							{/if}
						{/if}
					</div>
					<div class="clear"></div>
					<div id="button">
					</div>
				</div>
				<div class="start"> &nbsp; </div>
			</div>
			<div class="compare grid_7 omega">
				<div class="selectB select" onclick="next(1);">
					<div id="mpic1" class="white">
						<img src="{$match[0].m1.picB}" />
						<span class="name">{$match[0].m1.name}</span>
					</div>
					<div class="black"></div>
					<div class="blacktitle">VOTE NOW</div>
				</div>			
				<div class="selectB select" onclick="next(2);">
					<div id="mpic2" class="white">
						<img src="{$match[0].m2.picB}" />
						<span class="name">{$match[0].m2.name}</span>
					</div>
					<div class="black"></div>
					<div class="blacktitle">VOTE NOW</div>
				</div>			
			</div>
		{else}
			<div class="grid_7 push_2 alpha omega">		
				Sorry! You do not have enough applicable friends for cupid to operate properly. Please try the friends of friends option and please continue to connect with more Facebook friends.<br /><br />Best of luck.
			</div>
		{/if}	
	</div>
	
	{* Show user log in status *}
	<div class="user_status grid_6 push_1">
		{if $fbook.me}	
			<div id="matchtops">
				{include file="matchtops.tpl"}
			</div>
			{* DECIDED TO GET RID OF THIS FOR NOW *}
			{*
			<div class="grid_6 alpha omega divide">
				<div class="matchlist">			
					<div class="title">
						You have {$wings.count} friend{if $wings.count!=1}s{/if} on likebright. 
						{if $fbook.me.profile.status=="Single" or $fbook.me.profile.status==""}
						{/if}
					</div>
					{foreach from=$wings.faces item=frd}
						<a target="_blank" href="http://www.facebook.com/profile.php?id={$frd.uid}"><img src="{$frd.pic}" title="{$frd.name}" /></a>
					{/foreach}
				</div>
				<div class="send">
					<div class="divs">
						<div class="send">
							<fb:send href="http://likebright.com/cupid/" font=""></fb:send>
						</div>
						Send likebright to more friends!
					</div>
					<div class="divs"></div>
				</div>
			</div>
			*}
		{else}
			<div class="grid_6 alpha omega divide">
				<div class="title">Make your vote count! <a href="{$fbook.login}">Connect with Facebook.</a></div>
				<img src="images/famfamfam/icons/help.png" /> Play matchmaker with your Facebook friends! <br />
				<img src="images/famfamfam/icons/group.png" /> Discover which friends are most dateable! <br />
				<img src="images/famfamfam/icons/heart.png" /> Learn who your friends think you should date! <br />
				<img src="images/famfamfam/icons/emoticon_smile.png" /> Your votes are annonymous... have fun! <br />
				<br />
				<img src="images/famfamfam/icons/shield.png" /> Cupid will never use your data for evil or post anything without asking you first. <b>Ever.</b> <br />
				<br /><br />
				<fb:like href="http://likebright.com/cupid/" send="true" width="350" show_faces="true" font=""></fb:like>
			</div>
		{/if}
			<div class="grid_6 alpha omega footer">
				<div class="social">
					<a target="_blank" href="https://www.facebook.com/pages/cupid/220089028018633"><img src="images/icon-dock/facebook.png" title="Like!"></a>
					<a target="_blank" href="http://www.twitter.com/likebright1"><img src="images/icon-dock/twitter-2.png" title="Follow!"></a>
					<a target="_blank" href="https://github.com/laironald/likebright-cupid"><img src="images/icon-dock/github.png" title="Fork!"></a>
				</div>
				<div class="text">
					<a onclick="$('.footer .about').toggle();">About</a>
					{if $fbook.me}
					  - <a href="{$fbook.logout}">Logout</a>
					{/if}
				</div>
				<div class="about">
					Likebright developed using: 
					<ul>
						<li><a target="_blank" href="http://960.gs">960 Grid System</a></li>
						<li><a target="_blank" href="http://jquery.com">jQuery: The Write Less, Do More, JavaScript Library</a></li>
						<li><a target="_blank" href="http://developers.facebook.com/docs/reference/api/">Facebook Developers Graph API</a></li>
						<li><a target="_blank" href="http://www.famfamfam.com/lab/icons/silk/">famfamfam Silk Icons</a></li>
						<li><a target="_blank" href="http://fancybox.net/">FancyBox - Fancy lightbox alternative</a></li>
						<li><a target="_blank" href="http://html5boilerplate.com/">HTML Boilerplate - A rock-solid default for HTML5 awesome</a></li>
						<li><a target="_blank" href="http://icondock.com/free/vector-social-media-icons">Icon Dock - Vector Social Media Icons</a></li>
						<li><a target="_blank" href="http://sass-lang.com/">Sass (style with attitude)</a></li>
						<li><a target="_blank" href="http://smarty.net">Smarty Template Engine</a></li>
					</ul>
					<br />
					famfamfam icons used: 								
					<div class="famfamfam">
						<img src="images/famfamfam/icons/help.png" />
						<img src="images/famfamfam/icons/group.png" />
						<img src="images/famfamfam/icons/heart.png" />
						<img src="images/famfamfam/icons/new.png" />
						<img src="images/famfamfam/icons/lock.png" />
						<img src="images/famfamfam/icons/emoticon_smile.png" />
						<img src="images/famfamfam/icons/shield.png" />
					</div>
				</div>
			</div>
		</div>
	<div class="clear"></div>
</div>