<!doctype html>
<html>
    <head>
        <meta charset='utf-8' />
        <title><?php echo $view->variable('title'); ?> | Penny</title>
    </head>

    <body>
        <?php $view->includeThemeFile('header.php'); ?>
        SECONDARY THEME!
        <?php $view->contents(); ?>
    </body>
</html>
