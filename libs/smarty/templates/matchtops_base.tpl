{config_load file='match.conf'}
{assign var='statusOK' value=($fbook.me.profile.status=="Single" || $fbook.me.profile.status=="")}
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

			{* Status Bar *}
			{if !$secretOK}
				{assign var='x' value=$matchC}
				{if $matchC < #unlock1#}
					{assign var='y' value=#unlock1#}
					{block name=statusbar}{/block}
				{elseif $matchC < #unlock2#}
					{assign var='y' value=#unlock2#}
					{block name=statusbar}{/block}
				{/if}
				
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
			{/if}
			
			{* matchlist *}
			{if $statusOK}
				{if $matchT >= #unlockA1# || $secretOK}
					{if $match_your}
						<div class="title" style="padding-left: 3px;">
							{if $matchT == #unlockA1#}<img src="images/famfamfam/icons/new.png" />{/if}
							Your matches 
							{if $matchT >= #unlockA2# || $secretOK}
								<a href="fetch.php?q=matchlist{block name="secretGet"}{/block}" 
									title="Your socially rated matches."
									>{if $matchT == #unlockA2# || $matchT == #unlockA3#}<img src="images/famfamfam/icons/new.png" />{/if}(see more)</a>
							{/if}
						</div>
						<div class="matchlist">
							{foreach from=$match_your item=m}
								<a href="http://www.facebook.com/profile.php?id={$m.fid}" target="_blank"><img src="{$m.pic}" title="{$m.name}" /></a>
							{/foreach}
						</div>
					{else}
						<div class="title" style="padding-left: 3px;">Sorry. No matches.<br/>Matches are socially determined.<br/>So, invite your friends to generate matches!</div>
					{/if}
				{/if}
			{/if}
						
		</div>
		{block name="matchlist"}{/block}
	</div>
	<div class="clear"></div>
	<div class="grid_6 alpha omega divide">
		{if $matchC >= #unlock1# || $secretOK}
			<div class="grid_3 alpha tops">
				<div class="title">
					{if $matchC == #unlock1#}<img src="images/famfamfam/icons/new.png" />{/if}
					Most dateable guys<br/>
					{if $matchC >= #unlock2# || $secretOK}
						<a href="fetch.php?q=matchtops&sex=male&status={$smarty.get.status}&degree={$smarty.get.degree}{block name="secretGet"}{/block}" 
							title="The most dateable {if !$smarty.get.status=="x"}single{/if} guys in your {if $smarty.get.degree=="2"}friends of friends {/if}network{if $smarty.get.status=="x"} with unknown relationship status{/if}."
							>{if $matchC == #unlock2# || ($matchT == #unlockA3# && $smarty.get.degree != "2")}<img src="images/famfamfam/icons/new.png" />{/if}(see more)</a>
					{/if}
				</div>
				{foreach from=$match_tops.male item=m}
					<div class="imgList">
						<a target="_blank" href="http://www.facebook.com/profile.php?id={$m.uid}"><img src="{$m.pic}" title="{$m.name}" /></a>
						<span>{$m.name|replace:' ':'<br/>'}</span>
					</div>
				{foreachelse}
					Vote for guys to see results.
				{/foreach}
			</div>
			<div class="grid_3 omega tops">
				<div class="title">
					{if $matchC == #unlock1#}<img src="images/famfamfam/icons/new.png" />{/if}
					Most dateable girls<br/>
					{if $matchC >= #unlock2# || $secretOK}
						<a href="fetch.php?q=matchtops&sex=female&status={$smarty.get.status}&degree={$smarty.get.degree}{block name="secretGet"}{/block}" 
							title="The most dateable {if !$smarty.get.status=="x"}single{/if} girls in your {if $smarty.get.degree=="2"}friends of friends {/if}network{if $smarty.get.status=="x"} with unknown relationship status{/if}."
							>{if $matchC == #unlock2# || ($matchT == #unlockA3# && $smarty.get.degree != "2")}<img src="images/famfamfam/icons/new.png" />{/if}(see more)</a>
					{/if}
				</div>
				{foreach from=$match_tops.female item=m}
					<div class="imgList">
						<a target="_blank" href="http://www.facebook.com/profile.php?id={$m.uid}"><img src="{$m.pic}" title="{$m.name}" /></a>
						<span>{$m.name|replace:' ':'<br/>'}</span>
					</div>
				{foreachelse}	
					Vote for girls to see results.
				{/foreach}
			</div>
		{/if}
		{block name="popularlist"}{/block}
		
			<div class="clear"></div>
		</div>
	<div class="clear"></div>
{/if}