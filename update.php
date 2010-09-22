#!php
<?php
passthru(sprintf('cd %s && git pull origin master', __DIR__ ));
?>