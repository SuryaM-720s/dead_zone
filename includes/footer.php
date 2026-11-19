<?php
/**
 * footer.php
 * Closing HTML. Set $photoCredit before including on pages that show a photograph.
 */
?>
<?php if (($mainClass ?? 'page') === 'page'): ?>
</div>
<?php endif; ?>
</main>
<footer class="site-footer">
    <div class="wrap">
        <p><?= e(APP_NAME) ?> · CSC 4370 Project 2</p>
        <?php if (!empty($photoCredit)): ?>
            <p><?= $photoCredit ?></p>
        <?php endif; ?>
    </div>
</footer>
</body>
</html>
