{* $Header: /cvsroot/bitweaver/_bit_libertyform/templates/optfields_admin.tpl,v 1.1 2010/04/27 15:39:42 dansut Exp $ *}
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
