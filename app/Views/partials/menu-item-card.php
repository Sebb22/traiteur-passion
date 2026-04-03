<?php
    /** @var array<string, mixed> $menuItem */
    /** @var callable $e */

    $imagePath   = (string) ($menuItem['image_path'] ?? '');
    $imageAlt    = (string) ($menuItem['image_alt'] ?? ($menuItem['name'] ?? ''));
    $description = trim((string) ($menuItem['description'] ?? ''));
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
    </div>
</article>
