<?php
$pagelet = new \dobron\BigPipe\Pagelet('footer');

$pagelet
    ->appendContent('Footer content')
    ->onAfterLoad(['Footer', 'loaded'])
    ->require(['Footer', 'setYear']);
?><footer><div><?= $pagelet ?></div></footer>
