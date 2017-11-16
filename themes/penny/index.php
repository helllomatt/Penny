<!doctype html>
<html>
    <head>
        <meta charset='utf-8' />
        <?php echo $view->baseHref(); ?>
        <title><?php echo $view->variable('title'); ?> | Penny</title>
        <?php echo Penny\ViewResponse::getGlobalStyles(); ?>
        <link rel="stylesheet" href="theme/css/main.css" />
    </head>

    <body>
        <?php
        $view->includeThemeFile('header.php');
        $view->contents();
        ?>

        <script src="js/test.js"></script>
        <?php echo Penny\ViewResponse::getGlobalScripts(); ?>
    </body>
</html>
