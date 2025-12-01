<?php
/**
 * Dashboard - Order Form Tab Partial
 *
 * Contains the order form content for the user dashboard.
 * Reuses existing order form logic with dashboard-specific styling.
 *
 * @package Tabesh
 * @since 1.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get settings - ensure they are always arrays
$book_sizes = Tabesh()->get_setting('book_sizes', array());
$paper_types = Tabesh()->get_setting('paper_types', array());
$print_types = Tabesh()->get_setting('print_types', array());
$binding_types = Tabesh()->get_setting('binding_types', array());
$license_types = Tabesh()->get_setting('license_types', array());
$cover_paper_weights = Tabesh()->get_setting('cover_paper_weights', array());
$lamination_types = Tabesh()->get_setting('lamination_types', array());
$extras = Tabesh()->get_setting('extras', array());

// Ensure all array settings are actually arrays (defensive programming)
$book_sizes = is_array($book_sizes) ? $book_sizes : array();
$paper_types = is_array($paper_types) ? $paper_types : array();
$print_types = is_array($print_types) ? $print_types : array();
$binding_types = is_array($binding_types) ? $binding_types : array();
$license_types = is_array($license_types) ? $license_types : array();
$cover_paper_weights = is_array($cover_paper_weights) ? $cover_paper_weights : array();
$lamination_types = is_array($lamination_types) ? $lamination_types : array();
$extras = is_array($extras) ? $extras : array();

/**
 * Sanitization for extras array.
 * Filters out:
 * - Non-scalar values (arrays, objects)
 * - Empty strings and whitespace-only values
 * - The string 'on' which can appear from malformed checkbox submissions
 */
$extras = array_filter(array_map(function($extra) {
    $extra = is_scalar($extra) ? trim(strval($extra)) : '';
    return (!empty($extra) && $extra !== 'on') ? $extra : null;
}, $extras));

// Scalar settings
$min_quantity = Tabesh()->get_setting('min_quantity', 10);
$max_quantity = Tabesh()->get_setting('max_quantity', 10000);
$quantity_step = Tabesh()->get_setting('quantity_step', 10);
?>

<div class="dashboard-tab-content" id="order-form-content">
    <div class="tab-header">
        <h2 class="tab-title">
            <span class="tab-title-icon">📝</span>
            <?php esc_html_e('ثبت سفارش جدید', 'tabesh'); ?>
        </h2>
        <p class="tab-description"><?php esc_html_e('مشخصات کتاب خود را وارد کنید و قیمت را محاسبه نمایید.', 'tabesh'); ?></p>
    </div>

    <?php if (empty($book_sizes) || empty($paper_types)): ?>
        <div class="dashboard-alert error">
            <span class="alert-icon">⚠️</span>
            <div class="alert-content">
                <strong><?php esc_html_e('خطا:', 'tabesh'); ?></strong>
                <?php esc_html_e('تنظیمات محصول تکمیل نشده است. لطفاً با پشتیبانی تماس بگیرید.', 'tabesh'); ?>
            </div>
        </div>
    <?php else: ?>

    <form id="dashboard-order-form" class="dashboard-form">
        <!-- Progress Bar -->
        <div class="form-progress">
            <div class="progress-bar">
                <div class="progress-fill" id="form-progress-fill" style="width: 8.33%"></div>
            </div>
            <span class="progress-text" id="form-progress-text"><?php esc_html_e('مرحله 1 از 12', 'tabesh'); ?></span>
        </div>

        <!-- Step 1: Book Title -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <span class="step-number">1</span>
                <h3 class="step-title"><?php esc_html_e('عنوان کتاب', 'tabesh'); ?></h3>
                <button type="button" class="help-tip" data-tip="<?php esc_attr_e('عنوان کتاب همان متنی است که روی جلد کتاب چاپ می‌شود.', 'tabesh'); ?>">❓</button>
            </div>
            <div class="form-group">
                <label for="dashboard_book_title"><?php esc_html_e('عنوان کتاب (نام روی جلد):', 'tabesh'); ?></label>
                <input type="text" id="dashboard_book_title" name="book_title" required class="form-input" placeholder="<?php esc_attr_e('عنوان کتاب را وارد کنید', 'tabesh'); ?>">
            </div>
        </div>

        <!-- Step 2: Book Size -->
        <div class="form-step" data-step="2">
            <div class="step-header">
                <span class="step-number">2</span>
                <h3 class="step-title"><?php esc_html_e('قطع کتاب', 'tabesh'); ?></h3>
                <button type="button" class="help-tip" data-tip="<?php esc_attr_e('قطع کتاب اندازه فیزیکی کتاب را تعیین می‌کند.', 'tabesh'); ?>">❓</button>
            </div>
            <div class="form-group">
                <label for="dashboard_book_size"><?php esc_html_e('انتخاب قطع:', 'tabesh'); ?></label>
                <select id="dashboard_book_size" name="book_size" required class="form-select">
                    <option value=""><?php esc_html_e('انتخاب کنید...', 'tabesh'); ?></option>
                    <?php foreach ($book_sizes as $size): ?>
                        <option value="<?php echo esc_attr($size); ?>"><?php echo esc_html($size); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Step 3: Paper Type -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <span class="step-number">3</span>
                <h3 class="step-title"><?php esc_html_e('نوع کاغذ', 'tabesh'); ?></h3>
                <button type="button" class="help-tip" data-tip="<?php esc_attr_e('نوع کاغذ بر کیفیت و قیمت چاپ تاثیر دارد.', 'tabesh'); ?>">❓</button>
            </div>
            <div class="form-group">
                <label for="dashboard_paper_type"><?php esc_html_e('نوع کاغذ:', 'tabesh'); ?></label>
                <select id="dashboard_paper_type" name="paper_type" required class="form-select">
                    <option value=""><?php esc_html_e('انتخاب کنید...', 'tabesh'); ?></option>
                    <?php foreach ($paper_types as $type => $weights): ?>
                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Step 4: Paper Weight -->
        <div class="form-step" data-step="4">
            <div class="step-header">
                <span class="step-number">4</span>
                <h3 class="step-title"><?php esc_html_e('گرماژ کاغذ', 'tabesh'); ?></h3>
                <button type="button" class="help-tip" data-tip="<?php esc_attr_e('گرماژ بالاتر یعنی کاغذ ضخیم‌تر و مرغوب‌تر.', 'tabesh'); ?>">❓</button>
            </div>
            <div class="form-group">
                <label for="dashboard_paper_weight"><?php esc_html_e('گرماژ:', 'tabesh'); ?></label>
                <select id="dashboard_paper_weight" name="paper_weight" required class="form-select">
                    <option value=""><?php esc_html_e('ابتدا نوع کاغذ را انتخاب کنید', 'tabesh'); ?></option>
                </select>
            </div>
        </div>

        <!-- Step 5: Print Type -->
        <div class="form-step" data-step="5">
            <div class="step-header">
                <span class="step-number">5</span>
                <h3 class="step-title"><?php esc_html_e('نوع چاپ', 'tabesh'); ?></h3>
                <button type="button" class="help-tip" data-tip="<?php esc_attr_e('چاپ رنگی برای کتاب‌های تصویری و چاپ سیاه‌وسفید برای متون مناسب است.', 'tabesh'); ?>">❓</button>
            </div>
            <div class="form-group">
                <label for="dashboard_print_type"><?php esc_html_e('نوع چاپ:', 'tabesh'); ?></label>
                <select id="dashboard_print_type" name="print_type" required class="form-select">
                    <option value=""><?php esc_html_e('انتخاب کنید...', 'tabesh'); ?></option>
                    <?php foreach ($print_types as $type): ?>
                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Step 6: Page Count -->
        <div class="form-step" data-step="6">
            <div class="step-header">
                <span class="step-number">6</span>
                <h3 class="step-title"><?php esc_html_e('تعداد صفحات', 'tabesh'); ?></h3>
                <button type="button" class="help-tip" data-tip="<?php esc_attr_e('تعداد صفحات کتاب باید زوج باشد.', 'tabesh'); ?>">❓</button>
            </div>
            <div class="form-row">
                <div class="form-group half">
                    <label for="dashboard_page_count_bw"><?php esc_html_e('صفحات سیاه و سفید:', 'tabesh'); ?></label>
                    <input type="number" id="dashboard_page_count_bw" name="page_count_bw" min="0" value="0" class="form-input">
                </div>
                <div class="form-group half">
                    <label for="dashboard_page_count_color"><?php esc_html_e('صفحات رنگی:', 'tabesh'); ?></label>
                    <input type="number" id="dashboard_page_count_color" name="page_count_color" min="0" value="0" class="form-input">
                </div>
            </div>
        </div>

        <!-- Step 7: Quantity -->
        <div class="form-step" data-step="7">
            <div class="step-header">
                <span class="step-number">7</span>
                <h3 class="step-title"><?php esc_html_e('تیراژ', 'tabesh'); ?></h3>
                <button type="button" class="help-tip" data-tip="<?php esc_attr_e('با افزایش تیراژ، قیمت هر جلد کاهش می‌یابد.', 'tabesh'); ?>">❓</button>
            </div>
            <div class="form-group">
                <label for="dashboard_quantity"><?php printf(esc_html__('تعداد (حداقل %d):', 'tabesh'), $min_quantity); ?></label>
                <input type="number" id="dashboard_quantity" name="quantity" 
                       min="<?php echo esc_attr($min_quantity); ?>" 
                       max="<?php echo esc_attr($max_quantity); ?>" 
                       step="<?php echo esc_attr($quantity_step); ?>" 
                       value="<?php echo esc_attr($min_quantity); ?>" 
                       required class="form-input">
            </div>
        </div>

        <!-- Step 8: Binding -->
        <div class="form-step" data-step="8">
            <div class="step-header">
                <span class="step-number">8</span>
                <h3 class="step-title"><?php esc_html_e('نوع صحافی', 'tabesh'); ?></h3>
                <button type="button" class="help-tip" data-tip="<?php esc_attr_e('صحافی شومیز اقتصادی‌تر و جلد سخت مرغوب‌تر است.', 'tabesh'); ?>">❓</button>
            </div>
            <div class="form-group">
                <label for="dashboard_binding_type"><?php esc_html_e('نوع صحافی:', 'tabesh'); ?></label>
                <select id="dashboard_binding_type" name="binding_type" required class="form-select">
                    <option value=""><?php esc_html_e('انتخاب کنید...', 'tabesh'); ?></option>
                    <?php foreach ($binding_types as $type): ?>
                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Step 9: Cover Options -->
        <div class="form-step" data-step="9">
            <div class="step-header">
                <span class="step-number">9</span>
                <h3 class="step-title"><?php esc_html_e('گزینه‌های جلد', 'tabesh'); ?></h3>
                <button type="button" class="help-tip" data-tip="<?php esc_attr_e('سلفون براق درخشان و سلفون مات ظاهر لوکس دارد.', 'tabesh'); ?>">❓</button>
            </div>
            <div class="form-row">
                <div class="form-group half">
                    <label for="dashboard_cover_paper_weight"><?php esc_html_e('گرماژ کاغذ جلد:', 'tabesh'); ?></label>
                    <select id="dashboard_cover_paper_weight" name="cover_paper_weight" class="form-select">
                        <?php if (empty($cover_paper_weights)): ?>
                            <option value="250">250g</option>
                            <option value="300">300g</option>
                        <?php else: ?>
                            <?php foreach ($cover_paper_weights as $weight): ?>
                                <option value="<?php echo esc_attr($weight); ?>"><?php echo esc_html($weight); ?>g</option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group half">
                    <label for="dashboard_lamination_type"><?php esc_html_e('نوع سلفون:', 'tabesh'); ?></label>
                    <select id="dashboard_lamination_type" name="lamination_type" class="form-select">
                        <?php if (empty($lamination_types)): ?>
                            <option value="براق"><?php esc_html_e('براق', 'tabesh'); ?></option>
                            <option value="مات"><?php esc_html_e('مات', 'tabesh'); ?></option>
                            <option value="بدون سلفون"><?php esc_html_e('بدون سلفون', 'tabesh'); ?></option>
                        <?php else: ?>
                            <?php foreach ($lamination_types as $type): ?>
                                <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Step 10: License -->
        <div class="form-step" data-step="10">
            <div class="step-header">
                <span class="step-number">10</span>
                <h3 class="step-title"><?php esc_html_e('نوع مجوز', 'tabesh'); ?></h3>
                <button type="button" class="help-tip" data-tip="<?php esc_attr_e('اگر مجوز دارید گزینه اول را انتخاب کنید، در غیر این صورت می‌توانید از مجوز چاپکو استفاده کنید.', 'tabesh'); ?>">❓</button>
            </div>
            <div class="form-group">
                <label for="dashboard_license_type"><?php esc_html_e('مجوز:', 'tabesh'); ?></label>
                <select id="dashboard_license_type" name="license_type" required class="form-select">
                    <option value=""><?php esc_html_e('انتخاب کنید...', 'tabesh'); ?></option>
                    <?php foreach ($license_types as $type): ?>
                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="dashboard_license_upload" class="form-group" style="display:none;">
                <label><?php esc_html_e('بارگذاری مجوز:', 'tabesh'); ?></label>
                <input type="file" id="dashboard_license_file" name="license_file" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </div>

        <!-- Step 11: Extras -->
        <div class="form-step" data-step="11">
            <div class="step-header">
                <span class="step-number">11</span>
                <h3 class="step-title"><?php esc_html_e('خدمات اضافی', 'tabesh'); ?></h3>
                <button type="button" class="help-tip" data-tip="<?php esc_attr_e('خدمات اضافی اختیاری هستند و هزینه جداگانه دارند.', 'tabesh'); ?>">❓</button>
            </div>
            <div class="form-group extras-grid">
                <?php if (empty($extras)): ?>
                    <p class="no-extras"><?php esc_html_e('هیچ خدمات اضافی تنظیم نشده است.', 'tabesh'); ?></p>
                <?php else: ?>
                    <?php foreach ($extras as $extra): ?>
                        <?php if (is_string($extra) && !empty(trim($extra))): 
                            $extra_value = trim($extra);
                        ?>
                        <label class="checkbox-card">
                            <input type="checkbox" name="extras[]" value="<?php echo esc_attr($extra_value); ?>">
                            <span class="checkbox-label"><?php echo esc_html($extra_value); ?></span>
                        </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 12: Notes -->
        <div class="form-step" data-step="12">
            <div class="step-header">
                <span class="step-number">12</span>
                <h3 class="step-title"><?php esc_html_e('توضیحات', 'tabesh'); ?></h3>
                <button type="button" class="help-tip" data-tip="<?php esc_attr_e('هرگونه توضیح یا درخواست خاص را در این بخش بنویسید.', 'tabesh'); ?>">❓</button>
            </div>
            <div class="form-group">
                <label for="dashboard_notes"><?php esc_html_e('توضیحات (اختیاری):', 'tabesh'); ?></label>
                <textarea id="dashboard_notes" name="notes" rows="3" class="form-textarea" placeholder="<?php esc_attr_e('توضیحات یا درخواست‌های خاص خود را بنویسید...', 'tabesh'); ?>"></textarea>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="form-navigation">
            <button type="button" id="dashboard-prev-btn" class="btn btn-secondary" style="display:none;">
                <span class="btn-icon">→</span>
                <?php esc_html_e('قبلی', 'tabesh'); ?>
            </button>
            <button type="button" id="dashboard-next-btn" class="btn btn-primary">
                <?php esc_html_e('بعدی', 'tabesh'); ?>
                <span class="btn-icon">←</span>
            </button>
            <button type="button" id="dashboard-calculate-btn" class="btn btn-success" style="display:none;">
                <span class="btn-icon">💰</span>
                <?php esc_html_e('محاسبه قیمت', 'tabesh'); ?>
            </button>
        </div>
    </form>

    <!-- Price Result -->
    <div id="dashboard-price-result" class="price-result-card" style="display:none;">
        <div class="price-header">
            <h3 class="price-title">
                <span class="price-icon">📋</span>
                <?php esc_html_e('پیش‌فاکتور', 'tabesh'); ?>
            </h3>
        </div>
        <div class="price-details">
            <div class="price-row">
                <span class="price-label"><?php esc_html_e('قیمت هر جلد:', 'tabesh'); ?></span>
                <span class="price-value" id="dashboard-price-per-book">-</span>
            </div>
            <div class="price-row">
                <span class="price-label"><?php esc_html_e('تعداد:', 'tabesh'); ?></span>
                <span class="price-value" id="dashboard-price-quantity">-</span>
            </div>
            <div class="price-row">
                <span class="price-label"><?php esc_html_e('جمع:', 'tabesh'); ?></span>
                <span class="price-value" id="dashboard-price-subtotal">-</span>
            </div>
            <div class="price-row extras" id="dashboard-extras-row" style="display:none;">
                <span class="price-label"><?php esc_html_e('هزینه خدمات اضافی:', 'tabesh'); ?></span>
                <span class="price-value" id="dashboard-price-extras">-</span>
            </div>
            <div class="price-row discount" id="dashboard-discount-row" style="display:none;">
                <span class="price-label"><?php esc_html_e('تخفیف:', 'tabesh'); ?></span>
                <span class="price-value" id="dashboard-price-discount">-</span>
            </div>
            <div class="price-row total">
                <span class="price-label"><?php esc_html_e('مبلغ نهایی:', 'tabesh'); ?></span>
                <span class="price-value" id="dashboard-price-total">-</span>
            </div>
        </div>
        <div class="price-actions">
            <button type="button" id="dashboard-edit-order-btn" class="btn btn-secondary">
                <span class="btn-icon">✏️</span>
                <?php esc_html_e('ویرایش', 'tabesh'); ?>
            </button>
            <button type="button" id="dashboard-submit-order-btn" class="btn btn-success">
                <span class="btn-icon">✓</span>
                <?php esc_html_e('ثبت سفارش', 'tabesh'); ?>
            </button>
        </div>
    </div>

    <?php endif; ?>
</div>
