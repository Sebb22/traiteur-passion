<?php
    /** @var array<string, mixed> $menuItem */
    /** @var callable $e */

    $imagePath   = (string) ($menuItem['image_path'] ?? '');
    $imageAlt    = (string) ($menuItem['image_alt'] ?? ($menuItem['name'] ?? ''));
    $description = trim((string) ($menuItem['description'] ?? ''));
    $options     = is_array($menuItem['options'] ?? null) ? $menuItem['options'] : [];
?>
<article class="menuItem">
    <div class="menuItem__thumb" aria-hidden="true">
        <picture>
            <?php if ($imagePath !== ''): ?>
            <source
                type="image/webp"
                srcset="<?php echo $e(str_replace('-1200.webp', '-600.webp', $imagePath)); ?> 600w, <?php echo $e($imagePath); ?> 1200w"
                sizes="(max-width: 768px) 600px, 1200px"
            />
            <img src="<?php echo $e($imagePath); ?>" loading="lazy" alt="<?php echo $e($imageAlt); ?>">
            <?php endif; ?>
        </picture>
    </div>

    <div class="menuItem__body">
        <div class="menuItem__top">
            <h3 class="menuItem__name"><?php echo $e($menuItem['name'] ?? ''); ?></h3>
            <span class="menuItem__dots" aria-hidden="true"></span>
            <span class="menuItem__price"><?php echo $e($menuItem['price_from_label'] ?? ''); ?></span>
        </div>

        <?php if ($description !== ''): ?>
        <p class="menuItem__desc"><?php echo nl2br($e($description)); ?></p>
        <?php endif; ?>

        <?php if ($options !== []): ?>
        <div class="menuItem__options">
            <div class="menuItem__optionsTitle">Options disponibles</div>
            <div class="menuItem__optionsList">
                <?php foreach ($options as $option): ?>
                <?php
                    $optionLabel = trim((string) ($option['label'] ?? ''));
                    $optionPrice = trim((string) ($option['price_label'] ?? ''));
                    $quoteOnly   = ! empty($option['is_quote_only']);
                    if ($optionLabel === '') {
                        continue;
                    }
                ?>
                <div class="menuItem__optionChip">
                    <span><?php echo $e($optionLabel); ?></span>
                    <?php if ($optionPrice !== ''): ?>
                    <em><?php echo $e($optionPrice); ?></em>
                    <?php elseif ($quoteOnly): ?>
                    <em>Sur devis</em>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</article>
