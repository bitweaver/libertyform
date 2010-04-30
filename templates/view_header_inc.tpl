<div class="header">
	<h1>{$gContent->getContentTypeName()}: {$gContent->mInfo.title|escape}</h1>
	<div class="date">
		{tr}Created by{/tr}: {displayname user=$gContent->mInfo.creator_user user_id=$gContent->mInfo.creator_user_id real_name=$gContent->mInfo.creator_real_name}, {$gContent->mInfo.created|bit_long_datetime}<br />
		{tr}Last modification by{/tr}: {displayname user=$gContent->mInfo.modifier_user user_id=$gContent->mInfo.modifier_user_id real_name=$gContent->mInfo.modifier_real_name}, {$gContent->mInfo.last_modified|bit_long_datetime}
	</div>
</div><!-- end .header -->
