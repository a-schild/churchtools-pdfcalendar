<?php
declare(strict_types=1);
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/common.php';

ctLogout();
header('Location: index.php');
