<?php if(is_array($tab_order)) {
    foreach($tab_order as $tab) {
        if(isset($tabs[$tab])) {
            $_tabs[$tab] = $tabs[$tab];   
        }
    }
} else {
    $_tabs = $tabs;
}
?>
<div class="tabs">
    <ul>
    <?php foreach($_tabs as $tab_id => $tab): ?>
        <li><a href="#<?=$tab_id?>"><?=$tab['title']?></a></li>
    <?php endforeach; ?>
    </ul>
    <?php foreach($_tabs as $tab_id =>$tab): ?>
    <div id="<?=$tab_id?>">
        <?=$tab['content']?>
    </div>
    <?php endforeach; ?>
</div>