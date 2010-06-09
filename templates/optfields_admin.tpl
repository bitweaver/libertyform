{* $Header$ *}
{strip}
	<input type="hidden" name="page" value="{$page}" />
	{jstabs}
		{foreach from=$ofields item=ofield}
			{jstab title=$ofield.title}
				{legend legend=$ofield.legend}
					{include file="bitpackage:libertyform/optfield_admin.tpl" ofield=$ofield}
				{/legend}
			{/jstab}
		{/foreach}
	{/jstabs}
  {formfeedback warning=$errors.update}
{/strip}
