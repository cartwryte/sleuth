<header class="error-header page__container">
    <div class="error-header__body">
        <div class="error-header__content">
            <div class="error-header__main">
                <h1 class="error-header__title"><?php echo htmlspecialchars($headingTitle ?? 'Debug'); ?></h1>
                <?php if (isset($message)) { ?>
                <p class="error-header__message"><?php echo htmlspecialchars($message); ?></p>
                <?php } ?>

                <?php if (!empty($exceptions) && count($exceptions) > 1) { ?>
                <div class="exceptions-chain">
                    <h3 class="exceptions-chain__title">Exception Chain</h3>
                    <ol class="exceptions-chain__list">
                        <?php foreach ($exceptions as $index => $exception) { ?>
                        <li class="exceptions-chain__item <?php echo $index === 0 ? 'exceptions-chain__item--current' : ''; ?>">
                            <div class="exceptions-chain__class"><?php echo htmlspecialchars($exception['class']); ?></div>
                            <div class="exceptions-chain__message"><?php echo htmlspecialchars($exception['message']); ?></div>
                            <div class="exceptions-chain__location"><?php echo htmlspecialchars($exception['file']); ?>:<?php echo $exception['line']; ?></div>
                        </li>
                        <?php } ?>
                    </ol>
                </div>
                <?php } ?>
            </div>
            <div class="tech-info">
                <dl class="tech-info__list">
                    <?php foreach ($techInfo ?? [] as $label => $value) { ?>
                    <div class="tech-info__item">
                        <dt class="tech-info__label"><?php echo htmlspecialchars($label); ?></dt>
                        <dd class="tech-info__value"><?php echo htmlspecialchars($value); ?></dd>
                    </div>
                    <?php } ?>
                </dl>
            </div>
        </div>
    </div>
</header>
