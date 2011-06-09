{config_load file='match.conf'}
{assign var='statusOK' value=($fbook.me.profile.status=="Single" || $fbook.me.profile.status=="" || $get.status=="a" )}
{assign var='secretOK' value=($smarty.get.secret and ($fbook.uid=="211897" or $fbook.uid=="2203233"))}
{assign var='matchC' value=$fbook.me.profile.config[$fbook.me.screen]}
{assign var='matchT' value=$fbook.me.profile.matches}

{if $statusOK && $smarty.get.vote}
	{assign var='matchC' value=$smarty.get.vote}
{/if}
{if $statusOK && $smarty.get.tvote}
	{assign var='matchT' value=$smarty.get.tvote}
{/if}

{if $match_tops.item}
	<head><link rel="stylesheet" href="css/style_match_popup.css?v=2"></head>
	{foreach from=$match_tops.item item=m key=i}		
		<div>
			<img src="{$m.pic}" />
			<span>
				<a target="_blank" href="http://www.facebook.com/profile.php?id={$m.uid}">{$m.name}</a>
				{if $matchlistOK}
					{if $m.matchlist}
						<br/> &nbsp; <a style="font-size: 7pt" href="fetch.php?q=matchlist{block name="secretGet"}{/block}&uid={$m.uid}">matchlist</a>
					{else}
						<br/> &nbsp; <font style="color: #333; font-size: 7pt">outside of your network</font>
					{/if}
				{/if}
			</span>
			{if not $ie}
				<span class="s2">{$i+1}<span>
			{/if}
		</div>
	{/foreach}
{else}
	<div class="grid_6 alpha omega divide welcome">
		<img class="profile" src="{$fbook.me.profile.pic}" />
		<div class="main">
			<span>Hello</span> {$fbook.me.profile.name.name}.  
			<br/><span>{$friendCnt} friend{if $friendCnt!=1}s{/if}</span> on likebright.

			{* Status Bar *}
			{if !$secretOK}
				{assign var='x' value=$matchC}
				{if $matchC < #unlock1#}
					{assign var='y' value=#unlock1#}
					{block name=statusbar}{/block}
				{elseif $matchC < #unlock2# && $match_your}
					{assign var='y' value=#unlock2#}
					{block name=statusbar}{/block}
				{elseif $matchC < #unlock3#}
					{assign var='y' value=#unlock3#}
					{block name=statusbar}{/block}
				{elseif $matchC < #unlock4# && $match_your}
					{assign var='y' value=#unlock4#}
					{block name=statusbar}{/block}
				{/if}
				
				{*
				{assign var='x' value=$matchT}
				{assign var='cap' value='total'}
				{if $matchT < #unlockA1# && $statusOK}
					{assign var='y' value=#unlockA1#}
					{block name=statusbar}{/block}
				{elseif $matchT < #unlockA2# && $statusOK}
					{assign var='y' value=#unlockA2#}
					{block name=statusbar}{/block}
				{elseif $matchT < #unlockA3#}
					{assign var='y' value=#unlockA3#}
					{block name=statusbar}{/block}
				{/if}
				*}
			{/if}
			
			{* matchlist *}
			{if $get.degree!=0}
				{if $statusOK || $get.status=="a"}
					{*if $matchT >= #unlockA1# || $secretOK*}
					{if $match_your}
						{if $matchC >= #unlock2# || $secretOK}
							<div class="title" style="padding-left: 3px;">
								{*if $matchT == #unlockA1#*}
								{if $matchC == #unlock2#}<img src="images/famfamfam/icons/new.png" />{/if}
								Your matches 
								{*if $matchT >= #unlockA2# || $secretOK*}
								{if $matchC >= #unlock4# || $secretOK}
									<a class="popup" href="fetch.php?q=matchlist{$url.all}{block name="secretGet"}{/block}" 
										title="Your socially rated matches."
										>{*if $matchT == #unlockA2# || $matchT == #unlockA3#*}
										{if $matchC == #unlock2# || $matchC == #unlock4#}<img src="images/famfamfam/icons/new.png" />{/if}(see more)</a>
								{/if}
							</div>
							<div class="matchlist">
								{foreach from=$match_your item=m}
									<img src="{$m.pic}" title="{$m.name}" />
								{/foreach}
							</div>
						{/if}
					{/if}
					<div class="subtext">
						{assign var="link" value="http%3A%2F%2Flikebright.com%2Fcupid%2F%3Fuid%3D{$fbook.uid}%26degree={3-$get.degree}%26status={$get.status}"}
						{if not $match_your}You currently have no matches.<br/>{/if}
						<a target="_blank" href="http://www.facebook.com/dialog/feed?app_id=189260257772056&to={$guid}&redirect_uri=http%3A%2F%2Flikebright.com%2Fcupid%2Fclose.html&link={$link}&description=Play likebright and learn who your friends think is right for you.&name=Friends, help me answer who is right for me!&caption=Your opinion will be anonymous.">
							Enlist your friends
						</a> to generate matches!
					</div>
				{else}
					<div class="subtext">
						See your matches with <a href="?status=a{$url.degree}">all {if $get.degree==1}friends{else if $get.degree==2}friends of friends{/if}</a>.
					</div>
				{/if}
			{else}
				<div class="subtext">
					To see who has been matched to you,<br/>
					see <a href="?degree=1{$url.status}">friends</a> and <a href="?degree=2{$url.status}">+friends of friends</a>.
				</div>
			{/if}
						
		</div>
		{if $get.degree!=0 && $statusOK && $match_your}
			{block name="matchlist"}{/block}
		{/if}
	</div>
	<div class="clear"></div>
	<div class="grid_6 alpha omega divide">
		{if $matchC >= #unlock1# || $secretOK}
			<div class="grid_3 alpha tops">
				<div class="title">
					{if $get.degree==0}
						{assign var="cTitle" value="Your highest matched {if $get.status=="s"}singles{/if}{if $get.status=="x"}with unknown relationship status{/if}."}
					{else}
						{assign var="cTitle" value="The most matched {if $get.status=="s"}single{/if} guys{if $get.degree=="2"} in your friends of friends{/if}{if $get.status=="x"} with unknown relationship status{/if}."}
					{/if}
					{assign var="mgendr" value=$match_tops.male}
					{if $matchC == #unlock1#}<img src="images/famfamfam/icons/new.png" />{/if}
					{if $get.degree!=0}
						Most matched guys<br/>
					{else}
						Your highest matches<br/>
					{/if}
					{if $mgendr}
						<a target="_blank" href="http://www.facebook.com/dialog/feed?app_id=189260257772056&to={$guid}&redirect_uri=http%3A%2F%2Flikebright.com%2Fcupid%2Fclose.html&link=http://likebright.com/cupid/&name={$cTitle}&caption=Congrats to {$mgendr[0].name}, {$mgendr[1].name}, {$mgendr[2].name}, {$mgendr[3].name} and {$mgendr[4].name}.&picture=http://likebright.com/cupid/libs/php/image.php?q={$mgendr[0].uid}x0x{$mgendr[1].uid}x0x{$mgendr[2].uid}x0x{$mgendr[3].uid}x0x{$mgendr[4].uid}">
							post to wall
						</a>
						{if $matchC >= #unlock3# || $secretOK}
							<a class="popup" href="fetch.php?q=matchtops&sex=male&status={$get.status}&degree={$get.degree}{block name="secretGet"}{/block}" title="{$cTitle}">
								{if $matchC == #unlock3# || ($matchT == #unlockA3# && $get.degree != "2")}<img src="images/famfamfam/icons/new.png" />{/if}(see more)
							</a>
						{/if}
					{/if}
				</div>
				{foreach from=$match_tops.male item=m}
					<div class="imgList">
						<img src="{$m.pic}" title="{$m.name}" />
						<span>{$m.name}</span>
					</div>
				{foreachelse}
					Vote for guys to see results.
				{/foreach}
			</div>
			<div class="grid_3 omega tops">
				<div class="title">
					{assign var="cTitle" value="The most matched {if $get.status=="s"}single{/if} girls{if $get.degree=="2"} in your friends of friends{/if}{if $get.status=="x"} with unknown relationship status{/if}."}
					{assign var="mgendr" value=$match_tops.female}
					{if $get.degree!=0}
						{if $matchC == #unlock1#}<img src="images/famfamfam/icons/new.png" />{/if}
						Most matched girls<br/>
						{if $mgendr}
							<a target="_blank" href="http://www.facebook.com/dialog/feed?app_id=189260257772056&to={$guid}&redirect_uri=http%3A%2F%2Flikebright.com%2Fcupid%2Fclose.html&link=http://likebright.com/cupid/&name={$cTitle}&caption=Congrats to {$mgendr[0].name}, {$mgendr[1].name}, {$mgendr[2].name}, {$mgendr[3].name} and {$mgendr[4].name}.&picture=http://likebright.com/cupid/libs/php/image.php?q={$mgendr[0].uid}x0x{$mgendr[1].uid}x0x{$mgendr[2].uid}x0x{$mgendr[3].uid}x0x{$mgendr[4].uid}">
								post to wall
							</a>
							{if $matchC >= #unlock3# || $secretOK}
								<a class="popup" href="fetch.php?q=matchtops&sex=female&status={$get.status}&degree={$get.degree}{block name="secretGet"}{/block}" title="{$cTitle}">
									{if $matchC == #unlock3# || ($matchT == #unlockA3# && $get.degree != "2")}<img src="images/famfamfam/icons/new.png" />{/if}(see more)
								</a>
							{/if}
						{/if}
					{else}
						<br/><br/>
					{/if}
				</div>
				{foreach from=$match_tops.female item=m}
					<div class="imgList">
						<img src="{$m.pic}" title="{$m.name}" />
						<span>{$m.name}</span>
					</div>
				{foreachelse}	
					{if $get.degree!=0}Vote for girls to see results.{/if}
				{/foreach}
			</div>
		{/if}
		{block name="popularlist"}{/block}
		
			<div class="clear"></div>
		</div>
	<div class="clear"></div>
{/if}