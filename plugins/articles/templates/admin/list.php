<article>
<h1>Articles</h1>
<p><a href="<?=$admin_url?>/new-article" class="button">Create New Article...</a></p>
<table>
 <th>Title</th>
 <th>Date</th>
 <th>Edit</th>
 <th>Delete</th>
 <?php foreach($posts as $post): ?>
 <tr>
  <td><?=$post->title?></td>
  <td><?=$post->posted_on->format('Y-m-d H:i')?></td>
  <td><a href="<?=$admin_url?>/edit-article/<?=$post->id?>">Edit</a></td>
  <td><a href="<?=$admin_url?>/delete-article/<?=$post->id?>">Delete</a></td>
 </tr>
 <?php endforeach; ?>
 <?php if(count($posts) == 0): ?>
 <tr>
  <td colspan="4" class="noitems">No articles.</td>
 </tr>
 <?php endif; ?>
</table>
</article>