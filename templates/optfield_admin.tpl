{assign var=ofname value=$ofield.name}
{assign var=oferrs value=$errors.$ofname}
{form}
{if $only}<input type="hidden" name="only" value="{$only}" />{/if}
<table style="width:auto;text-align:center;margin:0 auto;"><tbody>
	<tr>
		{if $ofield.categories}<td></td>{/if}
		<th style="text-align:left;">Name</th><th>Active</th><th colspan="2">Order</th></tr>
	{if empty($ofield.categories)}
		{foreach item=ofitem key=id from=$ofield.options name=ofopts}
		<tr>
			<td><input type="text" size="20" maxlength="64" name="{$ofname}[{$id}][text]" value="{$ofitem.text}" /></td>
			<td><input type="checkbox" name="{$ofname}[{$id}][active]" {if $ofitem.active eq 'y'}checked="checked"{/if} /></td>
			<td>{if !$smarty.foreach.ofopts.last}
				<input type="image" src="{booticon iname="icon-cloud-download"  ipackage="icons"  url="TRUE"}" alt="Move Down" name="submit[{$ofname}][down]" value="{$id}">
				{/if}</td>
			<td>{if !$smarty.foreach.ofopts.first}
				<input type="image" src="{booticon iname="icon-cloud-upload" ipackage="icons" url="TRUE"}" alt="Move Up" name="submit[{$ofname}][up]" value="{$id}">
				{/if}</td>
			<td>{formfeedback error=$oferrs.$id}</td>
		</tr>
		{/foreach}
	{else}
		{foreach item=ofcopts key=catid from=$ofield.options name=ofcats}
			<tr><td style="text-align:right;">{$ofield.categories.$catid}</td><td colspan="5"></td></tr>
			{foreach item=ofitem key=id from=$ofcopts name=ofopts}
			<tr>
				<td><input type="hidden" name="{$ofname}[{$id}][catid]" value="{$catid}" /></td>
				<td><input type="text" size="20" maxlength="64" name="{$ofname}[{$id}][text]" value="{$ofitem.text}" /></td>
				<td><input type="checkbox" name="{$ofname}[{$id}][active]" {if $ofitem.active eq 'y'}checked="checked"{/if} /></td>
				<td>{if !$smarty.foreach.ofopts.last}
					<input type="image" src="{booticon iname="icon-cloud-download"  ipackage="icons"  url="TRUE"}" alt="Move Down" name="submit[{$ofname}][down]" value="{$id}">
					{/if}</td>
				<td>{if !$smarty.foreach.ofopts.first}
					<input type="image" src="{booticon iname="icon-cloud-upload" ipackage="icons" url="TRUE"}" alt="Move Up" name="submit[{$ofname}][up]" value="{$id}">
					{/if}</td>
				<td>{formfeedback error=$oferrs.$id}</td>
			</tr>
			{/foreach}
		{/foreach}
	{/if}
	<tr>
		{if $ofield.categories}
			{assign var=cidx value=[category]}
			<td>{html_options options=$ofield.categories name=$ofname$cidx}</td>{/if}
		<td><input type="text" size="20" maxlength="64" name="{$ofname}[new]" value="" /></td>
		<td colspan="3" class="inptxtprompt">New {$ofield.text}</td>
		<td>{formfeedback error=$oferrs.new}</td>
	</tr>
</tbody></table>
<div class="control-group submit">
	<input type="submit" name="submit[{$ofname}]" value="{tr}Update{/tr}" />
</div>
{/form}
