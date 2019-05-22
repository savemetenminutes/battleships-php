<?php
define('ROOT_DIR', __DIR__);

include(__DIR__ . '/Router.php');
$route = Router::fetchRoute();
$content = call_user_func(
    [
        $route['controller'],
        $route['action'],
    ]
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Battleships (by Milen Kirilov)</title>
</head>

<body>
<?=$content?>
</body>

</html>
