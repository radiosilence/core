<article>
<h1>Articles</h1>
<p><a href="<?=$admin_url?>/new-article" class="button">Create New Article...</a></p>
<table>
 <th>Active</th>
 <th>Title</th>
 <th>Date</th>
 <th>Edit</th>
 <th>Delete</th>
 <?php foreach($articles as $article): ?>
 <tr>
  <td><input id="active_check" article="<?=$article->id?>" type="checkbox"<?=($article->active ? ' checked' : null)?>/></td>
  <td><?=$article->title?></td>
  <td><?=$article->posted_on->format('Y-m-d H:i')?></td>
  <td><a href="<?=$admin_url?>/edit-article/<?=$article->id?>">Edit</a></td>
  <td><a href="<?=$admin_url?>/delete-article/<?=$article->id?>">Delete</a></td>
 </tr>
 <?php endforeach; ?>
 <?php if(count($articles) == 0): ?>
 <tr>
  <td colspan="4" class="noitems">No articles.</td>
 </tr>
 <?php endif; ?>
</table>
</article>