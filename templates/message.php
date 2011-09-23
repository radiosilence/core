<article>
    <h1><?=$_status?></h1>
    <p><?=$_message?></p>
    <?php if(is_array($_errors)): ?>
    <ul>
      <?php foreach($_errors as $e): ?>
      <li><?=$e?></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</article>