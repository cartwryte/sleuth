<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $titleEscaped; ?></title>
        <?php echo $viteAssets ?? ''; ?>
    </head>
    <body>
        <luminary-layout title="<?php echo $titleEscaped; ?>">
            <luminary-header
                slot="header"
                title="<?php echo $headingTitleEscaped; ?>"
                message="<?php echo $messageEscaped; ?>"
                class="container"
            >
                <?php if (!empty($exceptions)) { ?>
                <luminary-exceptions-chain
                    slot="exceptions-chain"
                    data-exceptions="<?php echo $exceptionsJson; ?>"
                ></luminary-exceptions-chain>
                <?php } ?>
                <?php if (!empty($suggestions)) { ?>
                <luminary-suggestions
                    slot="suggestions"
                    title="How to fix this"
                    data-suggestions='<?php echo $suggestionsJson; ?>'
                ></luminary-suggestions>
                <?php } ?>

                <luminary-tech-info
                    slot="tech-info"
                    data-tech-info='<?php echo $techInfoJson; ?>'
                ></luminary-tech-info>
            </luminary-header>

            <luminary-stack-trace
                title="Stack Trace"
                slot="main"
                class="container"
            >
                <?php foreach ($frames as $index => $frame) { ?>
                <pre><?php var_dump($frame['codeLinesJson'] ?? 'missing'); ?></pre>
                <textarea><?php echo $frame['codeLinesJson'] ?? '[]'; ?></textarea>
                <luminary-stack-frame
                    file="<?php echo $frame['fileEscaped']; ?>"
                    line="<?php echo $frame['line']; ?>"
                    function="<?php echo $frame['function']; ?>"
                <?php echo $index === 0 ? 'open' : ''; ?>
                data-code-lines='<?php echo $frame['codeLinesJson'] ?? '[]'; ?>'
                ></luminary-stack-frame>
                <?php } ?>
            </luminary-stack-trace>
        </luminary-layout>
    </body>
</html>
