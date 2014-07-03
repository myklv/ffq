<!doctype html>
<html>
<head>
	<meta charset=utf-8>
	<title><?=$name?> * wiki.zick.io</title>
	<link rel="stylesheet" href="/src/wiki.css">
</head>

<body>
    <?php if ($error || $alert || $notice): ?>
        <p class=msg> <?= $error ?> <?= $alert ?> <?= $notice ?> </p>
    <? endif; ?>

    <nav>
        <?php if (empty($all)): ?> 
            <a href=/ class=back>&larr; all pages</a>
        <?php endif; ?>
    </nav>

    <div class=wrapper>
        <header>
            <h1> <?=$name?> </h1>
            <a href=javascript:window.location='/:'+prompt()> new</a>
        </header>

        <ul class=list>
        <?php foreach ($list as $i): ?>
            <li><a href="/<?=$i?>"><?=$i?></a></li>
        <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
