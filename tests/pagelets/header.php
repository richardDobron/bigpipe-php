<?php
$pagelet = new \dobron\BigPipe\Pagelet('header');

$pagelet
    ->appendContent('Header content')
    ->require(['Header', 'init']);
?><header><div><?= $pagelet ?></div></header>
