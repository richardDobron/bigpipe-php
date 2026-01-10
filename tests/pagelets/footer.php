<?php
$pagelet = new \dobron\BigPipe\Pagelet('footer');

$pagelet
    ->appendContent('Footer content')
    ->addOnload('console.warn("Footer loaded");')
    ->require(['Footer', 'setYear']);
?><footer><div><?= $pagelet ?></div></footer>
