<?php
/**
 * Dashboard - User Orders Tab Partial
 *
 * Contains the order tracking content for the user dashboard.
 * Displays user's orders with status tracking.
 *
 * @package Tabesh
 * @since 1.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$user = Tabesh()->user;
$orders = $user->get_user_orders();
$archived_orders = $user->get_user_archived_orders();
?>

<div class="dashboard-tab-content" id="user-orders-content">
    <div class="tab-header">
        <h2 class="tab-title">
            <span class="tab-title-icon">📦</span>
            <?php esc_html_e('پیگیری سفارشات', 'tabesh'); ?>
        </h2>
        <p class="tab-description"><?php esc_html_e('وضعیت سفارشات خود را مشاهده و پیگیری کنید.', 'tabesh'); ?></p>
    </div>

    <!-- Search Bar -->
    <div class="search-container compact">
        <div class="search-input-wrapper">
            <span class="search-icon">🔍</span>
            <input 
                type="text" 
                id="orders-search-input" 
                class="search-input" 
                placeholder="<?php esc_attr_e('جستجو بر اساس عنوان کتاب، شماره سفارش، قطع...', 'tabesh'); ?>"
                autocomplete="off"
            >
            <button class="search-clear" id="orders-search-clear" style="display: none;" aria-label="<?php esc_attr_e('پاک کردن', 'tabesh'); ?>">✕</button>
        </div>
        <div class="search-results" id="orders-search-results" style="display: none;">
            <div class="search-results-content"></div>
        </div>
    </div>

    <!-- Orders Container -->
    <div class="orders-container">
        <?php if (empty($orders) && empty($archived_orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3><?php esc_html_e('هنوز سفارشی ثبت نشده است', 'tabesh'); ?></h3>
                <p><?php esc_html_e('با ثبت سفارش جدید، می‌توانید پروژه‌های خود را پیگیری کنید.', 'tabesh'); ?></p>
                <button class="btn btn-primary go-to-order-form" data-target="order-form">
                    <span class="btn-icon">📝</span>
                    <?php esc_html_e('ثبت سفارش جدید', 'tabesh'); ?>
                </button>
            </div>
        <?php else: ?>
            <!-- Active Orders -->
            <?php if (!empty($orders)): ?>
                <div class="orders-section">
                    <h3 class="section-title">
                        <span class="section-icon">📋</span>
                        <?php esc_html_e('سفارشات فعال', 'tabesh'); ?>
                        <span class="count-badge"><?php echo count($orders); ?></span>
                    </h3>

                    <div class="orders-list" id="active-orders-list">
                        <?php foreach ($orders as $order): 
                            $status_steps = $user->get_status_steps($order->status);
                        ?>
                            <div class="order-card" data-order-id="<?php echo esc_attr($order->id); ?>">
                                <div class="order-card-header">
                                    <div class="order-title-row">
                                        <h4 class="order-book-title">
                                            📖 <?php echo esc_html($order->book_title ?: __('بدون عنوان', 'tabesh')); ?>
                                        </h4>
                                        <span class="order-number">#<?php echo esc_html($order->order_number); ?></span>
                                    </div>
                                    <span class="order-status status-<?php echo esc_attr($order->status); ?>">
                                        <?php echo esc_html($user->get_status_label($order->status)); ?>
                                    </span>
                                </div>

                                <div class="order-card-body">
                                    <!-- Quick Info -->
                                    <div class="order-quick-info">
                                        <div class="info-item">
                                            <span class="info-icon">📄</span>
                                            <span class="info-text"><?php echo esc_html($order->page_count_total); ?> <?php esc_html_e('صفحه', 'tabesh'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-icon">📚</span>
                                            <span class="info-text"><?php echo esc_html($order->quantity); ?> <?php esc_html_e('نسخه', 'tabesh'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-icon">📐</span>
                                            <span class="info-text"><?php echo esc_html($order->book_size); ?></span>
                                        </div>
                                        <div class="info-item primary">
                                            <span class="info-icon">💵</span>
                                            <span class="info-text"><?php echo number_format($order->total_price); ?> <?php esc_html_e('تومان', 'tabesh'); ?></span>
                                        </div>
                                    </div>

                                    <!-- Progress Stepper -->
                                    <div class="progress-stepper">
                                        <?php 
                                        $step_index = 0;
                                        foreach ($status_steps as $status => $step): 
                                            $step_index++;
                                            $is_completed = $step['completed'];
                                            $is_current = ($status === $order->status);
                                        ?>
                                            <div class="progress-step <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $is_current ? 'current' : ''; ?>">
                                                <div class="step-connector"></div>
                                                <div class="step-circle">
                                                    <?php if ($is_completed): ?>
                                                        <span class="step-check">✓</span>
                                                    <?php else: ?>
                                                        <span class="step-number"><?php echo $step_index; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="step-label"><?php echo esc_html($step['label']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Footer -->
                                    <div class="order-card-footer">
                                        <span class="order-date">
                                            <span class="date-icon">📅</span>
                                            <?php echo esc_html(date_i18n('j F Y', strtotime($order->created_at))); ?>
                                        </span>
                                        <div class="order-actions">
                                            <button class="btn btn-secondary btn-support" 
                                                    data-order-id="<?php echo esc_attr($order->id); ?>" 
                                                    data-order-number="<?php echo esc_attr($order->order_number); ?>" 
                                                    data-book-title="<?php echo esc_attr($order->book_title); ?>">
                                                <span class="btn-icon">📞</span>
                                                <?php esc_html_e('پشتیبانی', 'tabesh'); ?>
                                            </button>
                                            <button class="btn btn-primary btn-details" data-order-id="<?php echo esc_attr($order->id); ?>">
                                                <span class="btn-icon">👁️</span>
                                                <?php esc_html_e('جزئیات', 'tabesh'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Archived Orders -->
            <?php if (!empty($archived_orders)): ?>
                <div class="orders-section archived-section">
                    <h3 class="section-title">
                        <span class="section-icon">🗄️</span>
                        <?php esc_html_e('سفارشات بایگانی شده', 'tabesh'); ?>
                        <span class="count-badge"><?php echo count($archived_orders); ?></span>
                    </h3>

                    <div class="orders-list archived-list" id="archived-orders-list">
                        <?php foreach ($archived_orders as $order): ?>
                            <div class="order-card archived" data-order-id="<?php echo esc_attr($order->id); ?>">
                                <div class="order-card-header">
                                    <div class="order-title-row">
                                        <h4 class="order-book-title">
                                            📖 <?php echo esc_html($order->book_title ?: __('بدون عنوان', 'tabesh')); ?>
                                        </h4>
                                        <span class="order-number">#<?php echo esc_html($order->order_number); ?></span>
                                    </div>
                                    <span class="order-status status-<?php echo esc_attr($order->status); ?>">
                                        <?php echo esc_html($user->get_status_label($order->status)); ?>
                                    </span>
                                </div>

                                <div class="order-card-body">
                                    <div class="order-quick-info">
                                        <div class="info-item">
                                            <span class="info-icon">📅</span>
                                            <span class="info-text"><?php echo esc_html(date_i18n('Y/m/d', strtotime($order->created_at))); ?></span>
                                        </div>
                                        <div class="info-item primary">
                                            <span class="info-icon">💵</span>
                                            <span class="info-text"><?php echo number_format($order->total_price); ?> <?php esc_html_e('تومان', 'tabesh'); ?></span>
                                        </div>
                                    </div>

                                    <div class="order-card-footer">
                                        <button class="btn btn-primary btn-details" data-order-id="<?php echo esc_attr($order->id); ?>">
                                            <span class="btn-icon">👁️</span>
                                            <?php esc_html_e('مشاهده جزئیات', 'tabesh'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Order Details Modal -->
    <div class="order-modal" id="order-details-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="order-modal-title"><?php esc_html_e('جزئیات سفارش', 'tabesh'); ?></h2>
                <button class="modal-close" id="order-modal-close">✕</button>
            </div>
            <div class="modal-body" id="order-modal-body">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p><?php esc_html_e('در حال بارگذاری...', 'tabesh'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Support Modal -->
    <div class="support-modal" id="support-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><?php esc_html_e('درخواست پشتیبانی', 'tabesh'); ?></h2>
                <button class="modal-close" id="support-modal-close">✕</button>
            </div>
            <div class="modal-body">
                <div class="support-order-info" id="support-order-info"></div>

                <div class="support-options">
                    <h3><?php esc_html_e('روش‌های تماس:', 'tabesh'); ?></h3>

                    <div class="support-option">
                        <div class="support-option-icon">📞</div>
                        <div class="support-option-content">
                            <h4><?php esc_html_e('تماس تلفنی', 'tabesh'); ?></h4>
                            <div class="phone-numbers">
                                <a href="tel:+989929828425" class="phone-link">0992-982-8425</a>
                                <a href="tel:+989125538967" class="phone-link">0912-553-8967</a>
                                <a href="tel:+982537237301" class="phone-link">025-3723-7301</a>
                            </div>
                        </div>
                    </div>

                    <div class="support-option">
                        <div class="support-option-icon">🎫</div>
                        <div class="support-option-content">
                            <h4><?php esc_html_e('ارسال تیکت', 'tabesh'); ?></h4>
                            <a href="https://pchapco.com/panel/?p=send-ticket" target="_blank" rel="noopener" class="btn btn-primary">
                                <span class="btn-icon">📝</span>
                                <?php esc_html_e('ارسال تیکت پشتیبانی', 'tabesh'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
