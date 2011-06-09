{extends file="matchtops_base.tpl"}
{block name=statusbar}
	<div class="statusbar">
		<div style="width: {math equation="x / y * 100" x=$x y=$y}%"></div>
		<span>{$x} {$cap} votes.</span>
	</div>
{/block}

{block name="secretGet"}{if $smarty.get.secret}&secret={$smarty.get.secret}{/if}{/block}

{block name="popularlist"}
	{if $matchC < #unlock1# && !$secretOK}
		<div class="grid_6 alpha omega tops">
			<div class="title">
				<img src="images/famfamfam/icons/lock.png" /> 
				Vote {#unlock1#} times to unlock your most matched friends.
			</div>
		</div>
	{elseif $matchC < #unlock3# && !$secretOK}
		<div class="grid_6 alpha omega tops">
			<div class="title">
				<img src="images/famfamfam/icons/lock.png" /> 
				Vote {#unlock3#} times to unlock more most matched friends.
			</div>
		</div>
	{/if} 
{/block}

{block name="matchlist"}
	{if $matchC < #unlock2# && $statusOK && !$secretOK}
		<div class="title">
			<img src="images/famfamfam/icons/lock.png" /> 
			Vote {#unlock2#} times to unlock your matches.
		</div>
	{elseif $matchC < #unlock4# && $statusOK && !$secretOK}
		<div class="title">
			<img src="images/famfamfam/icons/lock.png" /> 
			Vote {#unlock4#} times to unlock more matches.
		</div>
	{*
	{elseif $matchT < #unlockA3# && !$secretOK}
		<div class="title">
			<img src="images/famfamfam/icons/lock.png" /> 
			Vote {#unlockA3#} times to unlock your friend's matches.
		</div>
	*}
	{/if}
{/block}
