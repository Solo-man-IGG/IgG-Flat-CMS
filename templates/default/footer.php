    </main>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteTitle ?? 'My Site', ENT_QUOTES, 'UTF-8'); ?>. <?php echo __('footer.rights'); ?></p>
        <p><?php echo __('footer.powered_by'); ?></p>
    </footer>
</body>
</html>
