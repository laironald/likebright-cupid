{assign var='guid' value=$smarty.get.c}
{assign var='l' value=$smarty.get.m1}
{assign var='r' value=$smarty.get.m2}
{if $matchers[$guid]}
	<div id="button1" class="button grid_2 alpha omega">
	{*
		<a target="_blank" href="http://www.facebook.com/dialog/feed?app_id=189260257772056&to={$guid}&redirect_uri=http%3A%2F%2Flikebright.com%2Fcupid%2Fclose.html&link=http://likebright.com/cupid/&name=I know someone you should date.&caption=See who I think you're compatible with.&description= &picture={if $matchers[$guid].sex == "male"}https://lh5.googleusercontent.com/-jYLB-BlOKWA/TdX7uBt7ZRI/AAAAAAAABg0/7nLDK5ZMezg/woman%252520sil%2525202.png{else}https://lh4.googleusercontent.com/-r6LFCbd6UCg/TdX0YNNKwrI/AAAAAAAABgY/0ZLtv9hCS5w/hat%252520man%2525202.png{/if}">
	*}
		<a target="_blank" href="http://www.facebook.com/dialog/feed?app_id=189260257772056&to={$guid}&redirect_uri=http%3A%2F%2Flikebright.com%2Fcupid%2Fclose.html&link=http://likebright.com/cupid/&description= &name=I just voted on a good match for you.&caption=Play likebright with me!  See friends' matches for you and vote on matches for them. No one will know that you voted or played.&picture=http://likebright.com/cupid/libs/php/image.php?q={$guid}x{$l}x{$r}x0">
			Tell {$matchers[$guid].name}
		</a>
	</div>
{/if}
<div class="clear"></div>
{assign var='guid' value=$smarty.get.m}
{if $matchers[$guid]}
	<div id="button1" class="button grid_2 alpha omega">
	{*
		<a target="_blank" href="http://www.facebook.com/dialog/feed?app_id=189260257772056&to={$guid}&redirect_uri=http%3A%2F%2Flikebright.com%2Fcupid%2Fclose.html&link=http://likebright.com/cupid/&name=I know someone you should date.&caption=See who I think you're compatible with.&description= &picture={if $matchers[$guid].sex == "male"}https://lh5.googleusercontent.com/-jYLB-BlOKWA/TdX7uBt7ZRI/AAAAAAAABg0/7nLDK5ZMezg/woman%252520sil%2525202.png{else}https://lh4.googleusercontent.com/-r6LFCbd6UCg/TdX0YNNKwrI/AAAAAAAABgY/0ZLtv9hCS5w/hat%252520man%2525202.png{/if}">
	*}
		<a target="_blank" href="http://www.facebook.com/dialog/feed?app_id=189260257772056&to={$guid}&redirect_uri=http%3A%2F%2Flikebright.com%2Fcupid%2Fclose.html&link=http://likebright.com/cupid/&description= &name=I just voted on a good match for you.&caption=Play likebright with me!  See friends' matches for you and vote on matches for them. No one will know that you voted or played.&picture=http://likebright.com/cupid/libs/php/image.php?q=0x{$l}x{$r}x0">
			Tell {$matchers[$guid].name}
		</a>
	</div>
{/if}
{if $smarty.get.c && $smarty.get.m}
	<div id="button1" class="button grid_2 alpha omega">
		<a target="_blank" href="http://www.facebook.com/dialog/feed?app_id=189260257772056&redirect_uri=http%3A%2F%2Flikebright.com%2Fcupid%2Fclose.html&link=http://likebright.com/cupid/&description= &name=I think {$matchers[$smarty.get.c].name} and {$matchers[$smarty.get.m].name} are a good match.&caption=Play likebright with me! See friends' matches for you and vote on matches for them. No one will know that you voted or played.&picture=http://likebright.com/cupid/libs/php/image.php?q={$smarty.get.c}x0x0x{$smarty.get.m}">
			Post to my wall
		</a>
	</div>
{/if}
