<style>
	body {
		font-size: 9pt;
		font-family: Arial;
		background: #fff;
	}
	div.divide {
		padding: 2px 0px;
		border-bottom: 1px solid #666;
		margin: 2px 0px;
	}
	div.divide div {
		display: inline-block;
		vertical-align: top;
		overflow: hidden;
	}
	a {
		text-decoration: none;
		color: #3b5998;
	}
</style>

<div style="width: 100%; position: fixed; top: 0px; background-color: #fff; border-bottom: 3px solid #000; padding: 4px;">
	<a href="?page={$smarty.get.page}">Users</a>
	<a href="?section=email&page={$smarty.get.page}">Email</a>
	<br/>
	{$users.cnt} Users
	
	{if not $email}
		{if $smarty.get.page*25 > 0}
			<a href="?page={$smarty.get.page-1}">&lt;Prev</a>
		{/if}
		{if ($smarty.get.page+1)*25 < $users.cnt}
			<a href="?page={$smarty.get.page+1}">Next&gt;</a>
		{/if}
	{/if}
</div>

<br/><br/><br/>

{if $email} 
	<textarea style="width: 100%; height: 400px">{foreach from=$email item=e}{$e}, {/foreach}</textarea>
{else}
	{foreach from=$users.uid item=u}
		<div class="divide">
			<div style="width: 50px"><a target="_blank" href="http://facebook.com/profile.php?id={$u.json.uid}"><img src="{$u.json.pic_square}" /></a></div>
			<div style="width: 225px;">
				<a target="_blank" href="http://facebook.com/profile.php?id={$u.json.uid}">{$u.json.name}</a>
				{if $u.json.relationship_status}  <br/>{$u.json.relationship_status}{/if}
				{if $u.json.current_location.name}<br/>{$u.json.current_location.name}{/if}
				{if $u.json.email}<br/>{$u.json.email}{/if}
			</div>
			<div style="width: 100px;">
				{foreach from=$u.config key=k item=v}
					{if $v} {$k}: {$v} ... {/if}
				{/foreach}
				<br/>{$u.matches} votes
			</div>
			<div style="width: 120px;">
				Matched: {$u.matched}<br/>
				Skipped: {$u.skipped}<br/>
				Friends vote'd: {$u.voted}<br/> 
			</div>
		</div>
	{/foreach}
{/if}
