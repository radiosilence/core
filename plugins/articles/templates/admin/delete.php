<article>
<?php if($_confirmed): ?>
<p>Article has been deleted.</p>
<p><a href="<?=$admin_url?>"?>Return to admin home</a></p>
<?php else: ?>
<form action="<?=$admin_url?>/delete-article/<?=$article->id?>" method="post">
<input type="hidden" name="confirm" value="1"/>
<p>Are you sure you wish to delete article #<?=$article->id?> &#8220;<?=$article->title?>&#8221;?</p>
<p><input type="submit" value="Yes"/>&nbsp;<a href="<?=$admin_url?>" class="button">No</a></p>
</form>
<?php endif;?>
</article>