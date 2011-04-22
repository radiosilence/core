<article>
<h1><?=($article->id ? 'Edit' : 'Create')?> Article</h1>
<form action="<?=$admin_url?>/edit-article/<?=$article->id?>" method="POST">
 <input type="hidden" name="_tok" value="<?=$tok?>"/>
 <input type="hidden" name="_do" value="1"/>
 <div class="fieldset">
  <?php if(count($_errors) > 0): ?>
  <p><?=$_message?></p>
  <ul>
  <?php foreach($_errors as $error): ?>
    <li><?=$error?></li>
  <?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <p><label for="title">Title</label><br/>
  <input type="text" name="title" id="title" value="<?=$article->title?>" placeholder="(Ex. My Article)"/></p>
  <p><label for="posted_on">Posted On</label><br/>
  <input type="text" name="posted_on" id="posted_on" class="datepick" placeholder="(Ex. 1978-09-09)" value="<?=$article->form_posted_on?>"/></p>
  <p><label for="custom_url">Custom URL</label><br/>
  <input type="text" name="custom_url" placeholder="(Automatically generated)" id="custom_url" value="<?=$article->custom_url?>"/></p>
  <p><label for="body">Body</label><br/>
  <textarea name="body" placeholder="(Ex. Blah blah blah)" id="body" class="markdown"><?=$article->body?></textarea></p>
  <p><input type="submit" value="Save"/></p>
 </div>
</form>