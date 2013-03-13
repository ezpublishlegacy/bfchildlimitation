{if and( $content_object.can_create, $is_container )}
	<div id="ezwt-creataction" class="ezwt-actiongroup">
		<label for="ezwt-create" class="hide">Create:</label>
		{def $can_create_class_list = ezcreateclasslistgroups( $content_object.can_create_class_list )}
		{set $can_create_class_list = $can_create_class_list|ext_bfchildlimitation_recomputeAllowedChildren($current_node)}
		{if $can_create_class_list|count()}
			<select name="ClassID" id="ezwt-create">
				{foreach $can_create_class_list as $group}
				<optgroup label="{$group.group_name}">
					{foreach $group.items as $class}
						<option value="{$class.id}">{$class.name|wash}</option>
					{/foreach}
				</optgroup>
				{/foreach}
			</select>
		{/if}
		<input type="hidden" name="ContentLanguageCode" value="{ezini( 'RegionalSettings', 'ContentObjectLocale', 'site.ini')}" />
		<input class="ezwt-input-image" type="image" src={"websitetoolbar/ezwt-icon-create.png"|ezimage} name="NewButton" title="{'Create here'|i18n('design/standard/parts/website_toolbar')}" />
	</div>
{/if}
