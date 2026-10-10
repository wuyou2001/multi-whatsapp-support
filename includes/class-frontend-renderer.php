<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 前端组件渲染器。
 *
 * 负责输出多通道 WhatsApp 客服组件：
 * 1. 顶部 Header 自动挂载触发器（桌面端胶囊与移动端圆形按钮）；
 * 2. 页面浮动悬浮气泡（Floating Bubble）；
 * 3. 弹窗对话框（Popover Dialog）与毛玻璃遮罩；
 * 4. 简码 [whatsapp_support] 及其行内嵌入组件渲染。
 */
class MWS_Frontend_Renderer
{
    /**
     * 主插件实例。
     *
     * @var Multi_WhatsApp_Support
     */
    protected $plugin;

    /**
     * 构造函数。
     *
     * @param Multi_WhatsApp_Support $plugin
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;

        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_footer', array($this, 'render_footer_elements'), 99);
        add_shortcode('whatsapp_support', array($this, 'render_shortcode'));
    }

    /**
     * 注册并加载前端 CSS 与 JS。
     */
    public function enqueue_assets()
    {
        $settings = $this->plugin->get_settings();
        if (empty($settings['enabled'])) {
            return;
        }

        $ver = mws_get_version();
        $css_ver = file_exists(MWS_PLUGIN_DIR . 'assets/css/frontend.css') ? filemtime(MWS_PLUGIN_DIR . 'assets/css/frontend.css') : $ver;
        $js_ver  = file_exists(MWS_PLUGIN_DIR . 'assets/js/frontend.js') ? filemtime(MWS_PLUGIN_DIR . 'assets/js/frontend.js') : $ver;

        wp_enqueue_style(
            'mws-frontend-css',
            MWS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $css_ver
        );

        // 注入自定义视觉变量
        $custom_css = $this->generate_custom_css($settings);
        wp_add_inline_style('mws-frontend-css', $custom_css);

        wp_enqueue_script(
            'mws-frontend-js',
            MWS_PLUGIN_URL . 'assets/js/frontend.js',
            array(),
            $js_ver,
            true
        );

        // 传递前端配置参数
        $js_config = array(
            'enabled'                => !empty($settings['enabled']),
            'displayMode'            => isset($settings['display_mode']) ? $settings['display_mode'] : 'both',
            'headerEnabled'          => !empty($settings['header_enabled']),
            'headerSelectorDesktop'  => !empty($settings['header_selector_desktop']) ? $settings['header_selector_desktop'] : '.whb-general-header .whb-col-right',
            'headerSelectorMobile'   => !empty($settings['header_selector_mobile']) ? $settings['header_selector_mobile'] : '.whb-general-header .whb-mobile-right',
            'headerInsertPosition'   => !empty($settings['header_insert_position']) ? $settings['header_insert_position'] : 'before_account',
            'headerBtnTag'           => !empty($settings['header_btn_tag']) ? $settings['header_btn_tag'] : 'Live Support',
            'headerBtnText'          => !empty($settings['header_btn_text']) ? $settings['header_btn_text'] : 'WhatsApp',
            'floatingEnabled'        => !empty($settings['floating_enabled']),
            'floatingPosition'       => !empty($settings['floating_position']) ? $settings['floating_position'] : 'bottom-right',
            'floatingHideMobile'     => !empty($settings['floating_hide_mobile']),
        );

        wp_localize_script('mws-frontend-js', 'mwsConfig', $js_config);
    }

    /**
     * 生成前端动态自定义 CSS 变量与样式。
     *
     * @param array $settings
     * @return string
     */
    protected function generate_custom_css($settings)
    {
        $g_start = !empty($settings['gradient_start']) ? $settings['gradient_start'] : '#075e54';
        $g_mid   = !empty($settings['gradient_mid'])   ? $settings['gradient_mid']   : '#128c7e';
        $g_end   = !empty($settings['gradient_end'])   ? $settings['gradient_end']   : '#25d366';
        $accent  = !empty($settings['accent_color'])  ? $settings['accent_color']  : '#25d366';

        $off_x   = isset($settings['floating_offset_x']) ? intval($settings['floating_offset_x']) : 24;
        $off_y   = isset($settings['floating_offset_y']) ? intval($settings['floating_offset_y']) : 24;

        $css = ":root {
            --mws-g-start: {$g_start};
            --mws-g-mid: {$g_mid};
            --mws-g-end: {$g_end};
            --mws-accent: {$accent};
            --mws-float-x: {$off_x}px;
            --mws-float-y: {$off_y}px;
        }\n";

        if (!empty($settings['custom_css'])) {
            $css .= "\n/* User Custom CSS */\n" . wp_strip_all_tags($settings['custom_css']);
        }

        return $css;
    }

    /**
     * 输出 WhatsApp 官方 SVG 图标。
     *
     * @param string $class
     * @return string
     */
    public static function get_whatsapp_svg($class = '')
    {
        $class_attr = $class ? ' class="' . esc_attr($class) . '"' : '';
        return '<svg' . $class_attr . ' viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.19 8.19 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24m4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.12-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.12.17 1.77 2.71 4.3 3.8 2.53 1.09 2.53.73 2.98.69.46-.05 1.47-.6 1.68-1.18.21-.59.21-1.09.15-1.19-.06-.1-.23-.17-.48-.29z"/></svg>';
    }

    /**
     * 将电话号码与预设文字转换为安全的 wa.me 链接。
     *
     * @param string $number
     * @param string $prefill
     * @return string
     */
    public static function build_whatsapp_url($number, $prefill = '')
    {
        $clean_number = preg_replace('/[^0-9]/', '', (string)$number);
        if (empty($clean_number)) {
            return '#';
        }

        $url = 'https://wa.me/' . $clean_number;
        if (!empty($prefill)) {
            $url .= '?text=' . rawurlencode($prefill);
        }

        return $url;
    }

    /**
     * 输出页面底部组件（模板、遮罩与浮动按钮）。
     */
    public function render_footer_elements()
    {
        $settings = $this->plugin->get_settings();
        if (empty($settings['enabled'])) {
            return;
        }

        $display_mode = isset($settings['display_mode']) ? $settings['display_mode'] : 'both';
        $show_floating = ($display_mode === 'both' || $display_mode === 'floating') && !empty($settings['floating_enabled']);

        // 1. 全局毛玻璃遮罩
        echo '<div class="mws-backdrop" id="mwsBackdrop" aria-hidden="true"></div>' . "\n";

        // 2. 悬浮气泡按钮 (Floating Trigger)
        if ($show_floating) {
            $this->render_floating_trigger($settings);
        }

        // 3. 弹窗模板 (Popover Template)，供 JS 挂载与克隆
        $this->render_popover_template($settings);
    }

    /**
     * 渲染全局悬浮气泡。
     *
     * @param array $settings
     */
    protected function render_floating_trigger($settings)
    {
        $position = !empty($settings['floating_position']) ? esc_attr($settings['floating_position']) : 'bottom-right';
        $tooltip  = !empty($settings['floating_tooltip']) ? esc_attr($settings['floating_tooltip']) : 'WhatsApp Support';
        $has_pulse = !empty($settings['floating_pulse']);

        ?>
        <div class="mws-floating-wrap mws-pos-<?php echo $position; ?>" id="mwsFloatingWrap">
            <button type="button" class="mws-floating-btn" id="mwsFloatingBtn" aria-label="<?php echo $tooltip; ?>" aria-haspopup="dialog" aria-expanded="false">
                <span class="mws-floating-icon">
                    <?php echo self::get_whatsapp_svg('mws-svg-wa'); ?>
                    <?php if ($has_pulse) : ?>
                        <span class="mws-pulse-dot" aria-hidden="true"></span>
                    <?php endif; ?>
                </span>
                <?php if (!empty($tooltip)) : ?>
                    <span class="mws-floating-tooltip"><?php echo $tooltip; ?></span>
                <?php endif; ?>
            </button>
        </div>
        <?php
    }

    /**
     * 渲染弹窗 Popover 模板。
     *
     * @param array $settings
     */
    protected function render_popover_template($settings)
    {
        $title    = !empty($settings['popover_title'])    ? $settings['popover_title']    : 'WhatsApp Support';
        $subtitle = !empty($settings['popover_subtitle']) ? $settings['popover_subtitle'] : 'Online 24/7 Available';
        $footer   = !empty($settings['popover_footer'])   ? $settings['popover_footer']   : '🔒 Official WhatsApp Support • Instant Response';
        $channels = !empty($settings['channels']) && is_array($settings['channels']) ? $settings['channels'] : array();

        ?>
        <template id="mws-popover-template">
            <div class="mws-popover" id="mwsPopover" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr($title); ?>">
                <!-- Header -->
                <div class="mws-pop-header">
                    <div class="mws-pop-header-left">
                        <div class="mws-pop-header-icon">
                            <?php echo self::get_whatsapp_svg('mws-svg-white'); ?>
                        </div>
                        <div class="mws-pop-header-titles">
                            <h3 class="mws-pop-header-title"><?php echo esc_html($title); ?></h3>
                            <p class="mws-pop-header-subtitle"><?php echo esc_html($subtitle); ?></p>
                        </div>
                    </div>
                    <button type="button" class="mws-pop-close" id="mwsCloseBtn" aria-label="<?php esc_attr_e('Close WhatsApp Modal', 'multi-whatsapp-support'); ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>

                <!-- Body (客服通道列表) -->
                <div class="mws-pop-body">
                    <?php
                    if (!empty($channels)) {
                        foreach ($channels as $idx => $channel) {
                            $this->render_channel_card($channel, $idx + 1);
                        }
                    } else {
                        echo '<p class="mws-no-channels">' . esc_html__('No support channels configured.', 'multi-whatsapp-support') . '</p>';
                    }
                    ?>
                </div>

                <!-- Footer -->
                <?php if (!empty($footer)) : ?>
                    <div class="mws-pop-footer">
                        <span><?php echo esc_html($footer); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </template>
        <?php
    }

    /**
     * 渲染单个客服通道卡片。
     *
     * @param array $channel
     * @param int $default_idx
     * @param bool $echo
     * @return string|void
     */
    public function render_channel_card($channel, $default_idx = 1, $echo = true)
    {
        $badge_num = !empty($channel['badge']) ? $channel['badge'] : (string)$default_idx;
        $label     = !empty($channel['label']) ? $channel['label'] : sprintf(__('Agent %d', 'multi-whatsapp-support'), $default_idx);
        $number    = !empty($channel['number']) ? $channel['number'] : '';
        $sub       = !empty($channel['sub']) ? $channel['sub'] : '';
        $action    = !empty($channel['action']) ? $channel['action'] : 'Chat';
        $prefill   = !empty($channel['prefill']) ? $channel['prefill'] : '';
        $is_online = !isset($channel['online']) || !empty($channel['online']);

        $url = self::build_whatsapp_url($number, $prefill);

        ob_start();
        ?>
        <a href="<?php echo esc_url($url); ?>"
           target="_blank"
           rel="noopener noreferrer"
           class="mws-card"
           data-channel-idx="<?php echo esc_attr($default_idx); ?>"
           data-channel-name="<?php echo esc_attr($label); ?>">
            <div class="mws-card-left">
                <span class="mws-card-badge-num"><?php echo esc_html($badge_num); ?></span>
                <div class="mws-card-info">
                    <span class="mws-card-label">
                        <?php echo esc_html($label); ?>
                        <?php if ($is_online) : ?>
                            <span class="mws-card-online-dot" title="<?php esc_attr_e('Online', 'multi-whatsapp-support'); ?>" aria-label="<?php esc_attr_e('Online', 'multi-whatsapp-support'); ?>"></span>
                        <?php endif; ?>
                    </span>
                    <?php if (!empty($number)) : ?>
                        <span class="mws-card-phone"><?php echo esc_html($number); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($sub)) : ?>
                        <span class="mws-card-sub"><?php echo esc_html($sub); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <span class="mws-card-btn">
                <?php echo esc_html($action); ?>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </span>
        </a>
        <?php
        $output = ob_get_clean();

        if ($echo) {
            echo $output;
        } else {
            return $output;
        }
    }

    /**
     * 短代码处理：[whatsapp_support]
     *
     * 支持属性：
     * - mode: 'button' (默认：触发按钮唤起弹窗), 'cards' (直接行内输出卡片列表), 'card' (单张卡片)
     * - channel: 通道编号 (如 1, 2, 3，用于 mode="card")
     * - class: 额外 CSS 类名
     * - text: 按钮文字
     * - title: 卡片列表标题
     * - align: 对齐方式 ('left', 'center', 'right')
     *
     * @param array $atts
     * @return string
     */
    public function render_shortcode($atts)
    {
        $settings = $this->plugin->get_settings();
        $channels = !empty($settings['channels']) && is_array($settings['channels']) ? $settings['channels'] : array();

        $args = shortcode_atts(
            array(
                'mode'    => 'button',
                'channel' => '1',
                'class'   => '',
                'text'    => !empty($settings['header_btn_text']) ? $settings['header_btn_text'] : 'WhatsApp Support',
                'title'   => !empty($settings['popover_title']) ? $settings['popover_title'] : 'WhatsApp Support',
                'align'   => 'center',
            ),
            $atts,
            'whatsapp_support'
        );

        $mode  = sanitize_key($args['mode']);
        $align = sanitize_key($args['align']);
        $extra_class = esc_attr($args['class']);

        // 1. 卡片行内平铺模式
        if ($mode === 'cards') {
            $html = '<div class="mws-shortcode-cards-wrap mws-align-' . esc_attr($align) . ' ' . $extra_class . '">';
            if (!empty($args['title'])) {
                $html .= '<h4 class="mws-shortcode-title">' . esc_html($args['title']) . '</h4>';
            }
            $html .= '<div class="mws-shortcode-cards-grid">';
            foreach ($channels as $idx => $channel) {
                $html .= $this->render_channel_card($channel, $idx + 1, false);
            }
            $html .= '</div></div>';
            return $html;
        }

        // 2. 单个通道直达卡片模式
        if ($mode === 'card') {
            $idx = intval($args['channel']) - 1;
            if (isset($channels[$idx])) {
                $channel = $channels[$idx];
                return '<div class="mws-shortcode-single-card ' . $extra_class . '">' . $this->render_channel_card($channel, $idx + 1, false) . '</div>';
            }
            return '';
        }

        // 3. 独立触发按钮模式（唤起全局弹窗）
        $btn_html = '<div class="mws-shortcode-btn-wrap mws-align-' . esc_attr($align) . ' ' . $extra_class . '">
            <button type="button" class="mws-shortcode-btn mws-trigger-modal" aria-haspopup="dialog" aria-expanded="false">
                <span class="mws-shortcode-btn-icon">' . self::get_whatsapp_svg('mws-svg-white') . '</span>
                <span class="mws-shortcode-btn-text">' . esc_html($args['text']) . '</span>
            </button>
        </div>';

        return $btn_html;
    }
}
