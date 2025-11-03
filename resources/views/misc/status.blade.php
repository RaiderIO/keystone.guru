<?php
/** @var array $checks */
$title = 'Application Status';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
@foreach($checks as $service => $check)
    <p>{{ ucfirst($service) }}: {{$check['ok'] ? 'OK' : $check['error']}}</p>
@endforeach
</body>
</html>
