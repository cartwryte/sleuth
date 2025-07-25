<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title ?? 'Error'); ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
        <style><?php echo $css ?? ''; ?></style>
    </head>
    <body>
        <fieldset class="theme-switcher">
            <legend class="theme-switcher__legend">Theme</legend>
            <input class="theme-switcher__radio theme-switcher__radio--light" type="radio" name="color-scheme" value="light" id="theme-light" aria-label="Light theme">
            <input class="theme-switcher__radio theme-switcher__radio--system" type="radio" name="color-scheme" value="system" id="theme-system" aria-label="System theme" checked>
            <input class="theme-switcher__radio theme-switcher__radio--dark" type="radio" name="color-scheme" value="dark" id="theme-dark" aria-label="Dark theme">
            <div class="theme-switcher__indicator" aria-hidden="true"></div>
        </fieldset>

        <?php require __DIR__ . '/header.tpl'; ?>

        <main class="debug-main page__container">
            <?php require __DIR__ . '/error.tpl'; ?>
        </main>

        <script><?php echo $js ?? ''; ?></script>
    </body>
</html>
