<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

$pageTitle = __('contact.page_title');
$siteTitle = $siteTitle ?? 'My Site';
$siteSlogan = $siteSlogan ?? '';
$subtitle = $subtitle ?? '';
$menuItems = $menuItems ?? [];
require __DIR__ . '/header.php';
?>

<div class="contact-page">
    <h1><?php echo __('contact.heading'); ?></h1>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="/contact" class="contact-form">
        <?php echo $csrfField ?? ''; ?>
        
        <div class="form-group">
            <label for="name"><?php echo __('contact.form.name'); ?></label>
            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        
        <div class="form-group">
            <label for="email"><?php echo __('contact.form.email'); ?></label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        
        <div class="form-group full-width">
            <label for="subject"><?php echo __('contact.form.subject'); ?></label>
            <input type="text" id="subject" name="subject" required value="<?php echo htmlspecialchars($_POST['subject'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        
        <div class="form-group full-width">
            <label for="message"><?php echo __('contact.form.message'); ?></label>
            <textarea id="message" name="message" required><?php echo htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        
        <button type="submit"><?php echo __('contact.form.submit'); ?></button>
    </form>
</div>

<style>
.contact-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.contact-page h1 {
    color: var(--primary-color);
    margin-bottom: 2rem;
}

.contact-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.contact-form .form-group {
    display: flex;
    flex-direction: column;
}

.contact-form .form-group.full-width {
    grid-column: 1 / -1;
}

.contact-form label {
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.contact-form input,
.contact-form textarea {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    font-size: 1rem;
}

.contact-form textarea {
    min-height: 150px;
    resize: vertical;
}

.contact-form button {
    grid-column: 1 / -1;
    padding: 0.75rem 2rem;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.contact-form button:hover {
    background-color: var(--secondary-color);
}

@media (max-width: 768px) {
    .contact-form {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}
</style>

<?php require __DIR__ . '/footer.php'; ?>
