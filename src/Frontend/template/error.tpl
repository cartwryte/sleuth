<?php if (!empty($frames)) { ?>
    <?php foreach ($frames as $index => $frame) { ?>
        <details class="error-frame" <?php echo $index === 0 ? 'open' : ''; ?>>
            <summary class="error-frame__summary">
                <span class="error-frame__file"><?php echo htmlspecialchars($frame['file']); ?>:<?php echo $frame['line']; ?></span>
                <span class="error-frame__function"><?php echo htmlspecialchars($frame['function']); ?>()</span>
            </summary>
            <div class="error-frame__body">
                <div class="code-lines">
                    <?php echo $frame['codeHtml'] ?? '<!-- No code available -->'; ?>
                </div>
            </div>
        </details>
    <?php } ?>
<?php } ?>
