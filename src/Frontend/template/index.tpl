<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $titleEscaped; ?></title>
        <script>
            // The server's only job is to provide the initial data blob.
            window.__LUMINARY__ = {
                initialState: <?php echo json_encode($storeData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
            };
        </script>

        <?php echo $viteAssets; ?>
    </head>
    <body>
        <luminary-layout id="layout-main">
            <luminary-header id="header-main" slot="header" class="container">
                <luminary-exceptions-chain id="exceptions-main" slot="exceptions-chain"></luminary-exceptions-chain>
                <luminary-suggestions id="suggestions-main" slot="suggestions"></luminary-suggestions>
                <luminary-tech-info id="tech-info-main" slot="tech-info"></luminary-tech-info>
            </luminary-header>

            <luminary-stack-trace id="stack-trace-main" slot="main" class="container">
            </luminary-stack-trace>
        </luminary-layout>
    </body>
</html>
