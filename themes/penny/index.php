<!doctype html>
<html>
    <head>
        <meta charset='utf-8' />
        <?php echo $view->baseHref(); ?>
        <title><?php echo $view->variable('title'); ?> | Penny</title>
        <link rel="stylesheet" href="theme/css/main.css" />
    </head>

    <body>
        <?php
        $view->includeThemeFile('header.php');
        $view->contents();
        ?>

        <script src="js/test.js"></script>
    </body>
</html>
