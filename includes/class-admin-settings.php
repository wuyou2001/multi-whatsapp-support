<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 后台管理设置面板控制器。
 *
 * 提供现代化、响应式设置界面：
 * - 动态客服通道增删、字段编辑与排序；
 * - 挂载位置选择（Header 自动注入、浮动悬浮球、短代码）；
 * - 视觉定制（渐变色、主色调、文案、自定义 CSS）；
 * - 侧边栏实时响应式渲染预览（Live Preview）；
 * - 严格遵循 AGENTS.md 规范：静默后台更新，绝无 GitHub 配置卡片干扰。
 */
class MWS_Admin_Settings
{
    /**
     * 主插件实例。
     *
     * @var Multi_WhatsApp_Support
     */
    protected $plugin;

    /**
     * 设置选项名。
     */
    const OPTION_NAME = 'mws_settings';

    /**
     * 构造函数。
     *
     * @param Multi_WhatsApp_Support $plugin
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;

        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_filter('plugin_action_links_' . plugin_basename(MWS_PLUGIN_FILE), array($this, 'add_action_links'));
    }

    /**
     * 注册管理菜单。
     */
    public function register_menu()
    {
        add_menu_page(
            __('WhatsApp Support Settings', 'multi-whatsapp-support'),
            __('WhatsApp 客服', 'multi-whatsapp-support'),
            'manage_options',
            'multi-whatsapp-support',
            array($this, 'render_settings_page'),
            'dashicons-format-chat',
            68
        );
    }

    /**
     * 插件列表快捷设置链接。
     *
     * @param array $links
     * @return array
     */
    public function add_action_links($links)
    {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=multi-whatsapp-support')) . '">' . esc_html__('设置', 'multi-whatsapp-support') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * 加载后台管理样式与脚本。
     *
     * @param string $hook
     */
    public function enqueue_assets($hook)
    {
        if ($hook !== 'toplevel_page_multi-whatsapp-support') {
            return;
        }

        $ver = mws_get_version();

        // 原生颜色选择器与拖拽排序
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_style(
            'mws-admin-css',
            MWS_PLUGIN_URL . 'assets/css/admin.css',
            array('wp-color-picker'),
            $ver
        );

        wp_enqueue_script(
            'mws-admin-js',
            MWS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-sortable'),
            $ver,
            true
        );

        wp_localize_script('mws-admin-js', 'mwsAdminData', array(
            'svgWa' => MWS_Frontend_Renderer::get_whatsapp_svg('mws-preview-wa-svg'),
            'i18n'  => array(
                'confirmDelete' => __('确定要删除该客服通道吗？', 'multi-whatsapp-support'),
                'channelDefault' => __('客服通道', 'multi-whatsapp-support'),
                'newChannel'    => __('新客服通道', 'multi-whatsapp-support'),
            ),
        ));
    }

    /**
     * 注册设置选项及清洗回调。
     */
    public function register_settings()
    {
        register_setting('mws_settings_group', self::OPTION_NAME, array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));
    }

    /**
     * 严格数据清洗过滤器。
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input)
    {
        $defaults = Multi_WhatsApp_Support::get_default_settings();
        $output   = $defaults;

        if (!is_array($input)) {
            return $output;
        }

        // 开关
        $output['enabled'] = !empty($input['enabled']) ? 1 : 0;
        $output['display_mode'] = in_array($input['display_mode'], array('both', 'floating', 'header', 'manual'), true)
            ? $input['display_mode']
            : 'both';

        // 弹窗文案
        $output['popover_title']    = sanitize_text_field(isset($input['popover_title']) ? $input['popover_title'] : $defaults['popover_title']);
        $output['popover_subtitle'] = sanitize_text_field(isset($input['popover_subtitle']) ? $input['popover_subtitle'] : $defaults['popover_subtitle']);
        $output['popover_footer']   = sanitize_text_field(isset($input['popover_footer']) ? $input['popover_footer'] : $defaults['popover_footer']);

        // 颜色
        $output['gradient_start'] = sanitize_hex_color(isset($input['gradient_start']) ? $input['gradient_start'] : $defaults['gradient_start']);
        $output['gradient_mid']   = sanitize_hex_color(isset($input['gradient_mid']) ? $input['gradient_mid'] : $defaults['gradient_mid']);
        $output['gradient_end']   = sanitize_hex_color(isset($input['gradient_end']) ? $input['gradient_end'] : $defaults['gradient_end']);
        $output['accent_color']   = sanitize_hex_color(isset($input['accent_color']) ? $input['accent_color'] : $defaults['accent_color']);

        // 顶部 Header 挂载
        $output['header_enabled']           = !empty($input['header_enabled']) ? 1 : 0;
        $output['header_selector_desktop']  = sanitize_text_field(isset($input['header_selector_desktop']) ? $input['header_selector_desktop'] : $defaults['header_selector_desktop']);
        $output['header_selector_mobile']   = sanitize_text_field(isset($input['header_selector_mobile']) ? $input['header_selector_mobile'] : $defaults['header_selector_mobile']);
        $output['header_insert_position']   = in_array($input['header_insert_position'], array('before_account', 'prepend', 'append'), true)
            ? $input['header_insert_position']
            : 'before_account';
        $output['header_btn_tag']           = sanitize_text_field(isset($input['header_btn_tag']) ? $input['header_btn_tag'] : $defaults['header_btn_tag']);
        $output['header_btn_text']          = sanitize_text_field(isset($input['header_btn_text']) ? $input['header_btn_text'] : $defaults['header_btn_text']);

        // 浮动球
        $output['floating_enabled']     = !empty($input['floating_enabled']) ? 1 : 0;
        $output['floating_position']    = in_array($input['floating_position'], array('bottom-right', 'bottom-left', 'top-right', 'top-left'), true)
            ? $input['floating_position']
            : 'bottom-right';
        $output['floating_offset_x']    = isset($input['floating_offset_x']) ? max(0, min(200, intval($input['floating_offset_x']))) : 24;
        $output['floating_offset_y']    = isset($input['floating_offset_y']) ? max(0, min(200, intval($input['floating_offset_y']))) : 24;
        $output['floating_pulse']       = !empty($input['floating_pulse']) ? 1 : 0;
        $output['floating_tooltip']     = sanitize_text_field(isset($input['floating_tooltip']) ? $input['floating_tooltip'] : $defaults['floating_tooltip']);
        $output['floating_hide_mobile'] = !empty($input['floating_hide_mobile']) ? 1 : 0;

        // 通道列表
        $clean_channels = array();
        if (!empty($input['channels']) && is_array($input['channels'])) {
            foreach ($input['channels'] as $idx => $ch) {
                if (!is_array($ch)) {
                    continue;
                }
                $clean_channels[] = array(
                    'badge'   => sanitize_text_field(isset($ch['badge']) ? $ch['badge'] : ($idx + 1)),
                    'label'   => sanitize_text_field(isset($ch['label']) ? $ch['label'] : ''),
                    'number'  => sanitize_text_field(isset($ch['number']) ? $ch['number'] : ''),
                    'sub'     => sanitize_text_field(isset($ch['sub']) ? $ch['sub'] : ''),
                    'action'  => sanitize_text_field(isset($ch['action']) && !empty($ch['action']) ? $ch['action'] : 'Chat'),
                    'prefill' => sanitize_text_field(isset($ch['prefill']) ? $ch['prefill'] : ''),
                    'online'  => !empty($ch['online']) ? 1 : 0,
                );
            }
        }

        // 如果用户清空了所有通道，至少保留一个示例通道防报错
        if (empty($clean_channels)) {
            $clean_channels = $defaults['channels'];
        }
        $output['channels'] = $clean_channels;

        // 自定义 CSS
        $output['custom_css'] = isset($input['custom_css']) ? wp_strip_all_tags($input['custom_css']) : '';

        return $output;
    }

    /**
     * 渲染后台设置页面主视图。
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('您没有权限访问此页面。', 'multi-whatsapp-support'));
        }

        $settings = $this->plugin->get_settings();
        $channels = !empty($settings['channels']) && is_array($settings['channels']) ? $settings['channels'] : array();
        $version  = mws_get_version();

        ?>
        <div class="wrap mws-admin-wrap">
            <!-- 顶部头信息 -->
            <div class="mws-admin-header">
                <div class="mws-header-left">
                    <div class="mws-header-icon">
                        <?php echo MWS_Frontend_Renderer::get_whatsapp_svg('mws-header-svg'); ?>
                    </div>
                    <div>
                        <h1 class="mws-admin-title">
                            <?php esc_html_e('Multi WhatsApp Support', 'multi-whatsapp-support'); ?>
                            <span class="mws-badge-version">v<?php echo esc_html($version); ?></span>
                        </h1>
                        <p class="mws-admin-subtitle">
                            <?php esc_html_e('企业级多客服 WhatsApp 响应式支持套件 • 承袭高端毛玻璃质感与毫秒级即时沟通', 'multi-whatsapp-support'); ?>
                        </p>
                    </div>
                </div>
                <div class="mws-header-actions">
                    <button type="submit" form="mws-settings-form" class="button button-primary mws-btn-save-header">
                        <?php esc_html_e('保存所有设置', 'multi-whatsapp-support'); ?>
                    </button>
                </div>
            </div>

            <?php settings_errors(); ?>

            <div class="mws-admin-layout">
                <!-- 左侧表单主体 -->
                <div class="mws-layout-main">
                    <form method="post" action="options.php" id="mws-settings-form">
                        <?php
                        settings_fields('mws_settings_group');
                        ?>

                        <!-- 选项卡导航 -->
                        <div class="mws-nav-tabs">
                            <a href="#tab-channels" class="mws-nav-tab is-active" data-tab="tab-channels">
                                <span class="dashicons dashicons-groups"></span> <?php esc_html_e('客服通道配置', 'multi-whatsapp-support'); ?>
                            </a>
                            <a href="#tab-placement" class="mws-nav-tab" data-tab="tab-placement">
                                <span class="dashicons dashicons-layout"></span> <?php esc_html_e('显示与挂载位置', 'multi-whatsapp-support'); ?>
                            </a>
                            <a href="#tab-style" class="mws-nav-tab" data-tab="tab-style">
                                <span class="dashicons dashicons-admin-appearance"></span> <?php esc_html_e('视觉与文案定制', 'multi-whatsapp-support'); ?>
                            </a>
                            <a href="#tab-shortcode" class="mws-nav-tab" data-tab="tab-shortcode">
                                <span class="dashicons dashicons-shortcode"></span> <?php esc_html_e('短代码与开发', 'multi-whatsapp-support'); ?>
                            </a>
                        </div>

                        <!-- Tab 1: 客服通道配置 -->
                        <div class="mws-tab-panel is-active" id="tab-channels">
                            <div class="mws-card">
                                <div class="mws-card-header">
                                    <div>
                                        <h2 class="mws-card-title"><?php esc_html_e('客服通道列表 (Channels Matrix)', 'multi-whatsapp-support'); ?></h2>
                                        <p class="mws-card-desc"><?php esc_html_e('添加多个 WhatsApp 客服专线，可按部门、业务类型（如售前、VIP专线、查单服务）灵活分流。拖拽左侧手柄可实时调整排序。', 'multi-whatsapp-support'); ?></p>
                                    </div>
                                    <button type="button" class="button button-secondary mws-btn-add-channel" id="mwsAddChannelBtn">
                                        <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('添加新客服通道', 'multi-whatsapp-support'); ?>
                                    </button>
                                </div>

                                <div class="mws-channel-list" id="mwsChannelList">
                                    <?php foreach ($channels as $index => $ch) : ?>
                                        <?php $this->render_channel_form_item($index, $ch); ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: 显示与挂载位置 -->
                        <div class="mws-tab-panel" id="tab-placement">
                            <div class="mws-card">
                                <h2 class="mws-card-title"><?php esc_html_e('核心显示模式', 'multi-whatsapp-support'); ?></h2>
                                <table class="form-table mws-form-table">
                                    <tr>
                                        <th scope="row"><?php esc_html_e('插件总开关', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <label class="mws-switch">
                                                <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[enabled]" value="1" <?php checked(!empty($settings['enabled'])); ?>>
                                                <span class="mws-switch-slider"></span>
                                            </label>
                                            <span class="mws-field-hint"><?php esc_html_e('关闭后全站将停止加载弹窗与悬浮触发器。', 'multi-whatsapp-support'); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('展示形式', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <select name="<?php echo self::OPTION_NAME; ?>[display_mode]" id="mws_display_mode" class="regular-text">
                                                <option value="both" <?php selected($settings['display_mode'], 'both'); ?>><?php esc_html_e('Header 导航挂载 + 右下角悬浮气泡 (推荐 - 双通道展示)', 'multi-whatsapp-support'); ?></option>
                                                <option value="floating" <?php selected($settings['display_mode'], 'floating'); ?>><?php esc_html_e('仅右下角悬浮气泡 (Floating Bubble Only)', 'multi-whatsapp-support'); ?></option>
                                                <option value="header" <?php selected($settings['display_mode'], 'header'); ?>><?php esc_html_e('仅 Header 导航挂载 (Header Mount Only)', 'multi-whatsapp-support'); ?></option>
                                                <option value="manual" <?php selected($settings['display_mode'], 'manual'); ?>><?php esc_html_e('手动模式 (仅通过短代码或自定义代码调用)', 'multi-whatsapp-support'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Header 挂载细节 -->
                            <div class="mws-card">
                                <div class="mws-card-header">
                                    <div>
                                        <h2 class="mws-card-title"><?php esc_html_e('顶部 Header 自动挂载设置', 'multi-whatsapp-support'); ?></h2>
                                        <p class="mws-card-desc"><?php esc_html_e('深度适配 WoodMart、Flatsome、Astra、Storefront 等主流主题 Header 结构。', 'multi-whatsapp-support'); ?></p>
                                    </div>
                                    <label class="mws-switch">
                                        <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[header_enabled]" value="1" <?php checked(!empty($settings['header_enabled'])); ?>>
                                        <span class="mws-switch-slider"></span>
                                    </label>
                                </div>
                                <table class="form-table mws-form-table">
                                    <tr>
                                        <th scope="row"><?php esc_html_e('桌面端容器选择器 (CSS Selector)', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <input type="text" name="<?php echo self::OPTION_NAME; ?>[header_selector_desktop]" value="<?php echo esc_attr($settings['header_selector_desktop']); ?>" class="large-text code">
                                            <p class="description"><?php esc_html_e('默认已填入 WoodMart 主题通用 Header 右侧容器：.whb-general-header .whb-col-right', 'multi-whatsapp-support'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('移动端挂载选择器 (Mobile CSS Selector)', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <input type="text" name="<?php echo self::OPTION_NAME; ?>[header_selector_mobile]" value="<?php echo esc_attr($settings['header_selector_mobile']); ?>" class="large-text code">
                                            <p class="description"><?php esc_html_e('默认已填入 WoodMart 移动端 Header 容器：.whb-general-header .whb-mobile-right', 'multi-whatsapp-support'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('挂载插入位置', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <select name="<?php echo self::OPTION_NAME; ?>[header_insert_position]" class="regular-text">
                                                <option value="before_account" <?php selected($settings['header_insert_position'], 'before_account'); ?>><?php esc_html_e('插在用户个人中心 (My Account) 图标前方 (推荐)', 'multi-whatsapp-support'); ?></option>
                                                <option value="prepend" <?php selected($settings['header_insert_position'], 'prepend'); ?>><?php esc_html_e('插入到容器最前方 (Prepend)', 'multi-whatsapp-support'); ?></option>
                                                <option value="append" <?php selected($settings['header_insert_position'], 'append'); ?>><?php esc_html_e('插入到容器最后方 (Append)', 'multi-whatsapp-support'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Header 按钮小标签与主文案', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <div class="mws-inline-fields">
                                                <div>
                                                    <label class="mws-sublabel"><?php esc_html_e('顶部绿色小标签', 'multi-whatsapp-support'); ?></label>
                                                    <input type="text" name="<?php echo self::OPTION_NAME; ?>[header_btn_tag]" value="<?php echo esc_attr($settings['header_btn_tag']); ?>" class="regular-text">
                                                </div>
                                                <div>
                                                    <label class="mws-sublabel"><?php esc_html_e('主标题文字', 'multi-whatsapp-support'); ?></label>
                                                    <input type="text" name="<?php echo self::OPTION_NAME; ?>[header_btn_text]" value="<?php echo esc_attr($settings['header_btn_text']); ?>" class="regular-text">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- 悬浮球配置 -->
                            <div class="mws-card">
                                <div class="mws-card-header">
                                    <div>
                                        <h2 class="mws-card-title"><?php esc_html_e('悬浮气泡球设置 (Floating Bubble)', 'multi-whatsapp-support'); ?></h2>
                                        <p class="mws-card-desc"><?php esc_html_e('在屏幕角落始终悬浮 WhatsApp 快捷沟通球，支持脉冲波纹提醒。', 'multi-whatsapp-support'); ?></p>
                                    </div>
                                    <label class="mws-switch">
                                        <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[floating_enabled]" value="1" <?php checked(!empty($settings['floating_enabled'])); ?>>
                                        <span class="mws-switch-slider"></span>
                                    </label>
                                </div>
                                <table class="form-table mws-form-table">
                                    <tr>
                                        <th scope="row"><?php esc_html_e('悬浮位置', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <select name="<?php echo self::OPTION_NAME; ?>[floating_position]" id="mws_floating_position" class="regular-text">
                                                <option value="bottom-right" <?php selected($settings['floating_position'], 'bottom-right'); ?>><?php esc_html_e('右下角 (Bottom Right - 默认推荐)', 'multi-whatsapp-support'); ?></option>
                                                <option value="bottom-left" <?php selected($settings['floating_position'], 'bottom-left'); ?>><?php esc_html_e('左下角 (Bottom Left)', 'multi-whatsapp-support'); ?></option>
                                                <option value="top-right" <?php selected($settings['floating_position'], 'top-right'); ?>><?php esc_html_e('右上角 (Top Right)', 'multi-whatsapp-support'); ?></option>
                                                <option value="top-left" <?php selected($settings['floating_position'], 'top-left'); ?>><?php esc_html_e('左上角 (Top Left)', 'multi-whatsapp-support'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('边距偏移量 (X / Y)', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <div class="mws-inline-fields">
                                                <div>
                                                    <label class="mws-sublabel"><?php esc_html_e('水平边距 (px)', 'multi-whatsapp-support'); ?></label>
                                                    <input type="number" name="<?php echo self::OPTION_NAME; ?>[floating_offset_x]" value="<?php echo esc_attr($settings['floating_offset_x']); ?>" min="0" max="200" class="small-text">
                                                </div>
                                                <div>
                                                    <label class="mws-sublabel"><?php esc_html_e('垂直边距 (px)', 'multi-whatsapp-support'); ?></label>
                                                    <input type="number" name="<?php echo self::OPTION_NAME; ?>[floating_offset_y]" value="<?php echo esc_attr($settings['floating_offset_y']); ?>" min="0" max="200" class="small-text">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('悬浮提示文案 (Tooltip)', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <input type="text" name="<?php echo self::OPTION_NAME; ?>[floating_tooltip]" value="<?php echo esc_attr($settings['floating_tooltip']); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('动态波纹脉冲动画', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[floating_pulse]" value="1" <?php checked(!empty($settings['floating_pulse'])); ?>>
                                                <?php esc_html_e('启用右上角呼吸脉冲绿点，提升用户点击率', 'multi-whatsapp-support'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('移动端隐藏悬浮球', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[floating_hide_mobile]" value="1" <?php checked(!empty($settings['floating_hide_mobile'])); ?>>
                                                <?php esc_html_e('在移动端 (< 768px) 隐藏浮动球，避免遮挡结账与导航按钮', 'multi-whatsapp-support'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Tab 3: 视觉与文案定制 -->
                        <div class="mws-tab-panel" id="tab-style">
                            <div class="mws-card">
                                <h2 class="mws-card-title"><?php esc_html_e('弹窗文案定制', 'multi-whatsapp-support'); ?></h2>
                                <table class="form-table mws-form-table">
                                    <tr>
                                        <th scope="row"><?php esc_html_e('弹窗主标题', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <input type="text" name="<?php echo self::OPTION_NAME; ?>[popover_title]" id="mws_popover_title" value="<?php echo esc_attr($settings['popover_title']); ?>" class="large-text">
                                            <p class="description"><?php esc_html_e('默认：WhatsApp Support', 'multi-whatsapp-support'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('弹窗副标题', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <input type="text" name="<?php echo self::OPTION_NAME; ?>[popover_subtitle]" id="mws_popover_subtitle" value="<?php echo esc_attr($settings['popover_subtitle']); ?>" class="large-text">
                                            <p class="description"><?php esc_html_e('默认：Online 24/7 Available', 'multi-whatsapp-support'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('底部安全保证与背书文案', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <input type="text" name="<?php echo self::OPTION_NAME; ?>[popover_footer]" id="mws_popover_footer" value="<?php echo esc_attr($settings['popover_footer']); ?>" class="large-text">
                                            <p class="description"><?php esc_html_e('留空则不显示底部背书栏。', 'multi-whatsapp-support'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div class="mws-card">
                                <h2 class="mws-card-title"><?php esc_html_e('品牌配色与渐变 (Colors & Gradients)', 'multi-whatsapp-support'); ?></h2>
                                <table class="form-table mws-form-table">
                                    <tr>
                                        <th scope="row"><?php esc_html_e('弹窗头部渐变背景色', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <div class="mws-color-group">
                                                <div>
                                                    <label class="mws-sublabel"><?php esc_html_e('渐变起始色 (深色)', 'multi-whatsapp-support'); ?></label>
                                                    <input type="text" name="<?php echo self::OPTION_NAME; ?>[gradient_start]" value="<?php echo esc_attr($settings['gradient_start']); ?>" class="mws-color-picker" data-default-color="#075e54" id="mws_color_gstart">
                                                </div>
                                                <div>
                                                    <label class="mws-sublabel"><?php esc_html_e('渐变过渡色 (中段)', 'multi-whatsapp-support'); ?></label>
                                                    <input type="text" name="<?php echo self::OPTION_NAME; ?>[gradient_mid]" value="<?php echo esc_attr($settings['gradient_mid']); ?>" class="mws-color-picker" data-default-color="#128c7e" id="mws_color_gmid">
                                                </div>
                                                <div>
                                                    <label class="mws-sublabel"><?php esc_html_e('渐变结束色 (亮绿)', 'multi-whatsapp-support'); ?></label>
                                                    <input type="text" name="<?php echo self::OPTION_NAME; ?>[gradient_end]" value="<?php echo esc_attr($settings['gradient_end']); ?>" class="mws-color-picker" data-default-color="#25d366" id="mws_color_gend">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('主强调色 (按钮与悬停高亮)', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <input type="text" name="<?php echo self::OPTION_NAME; ?>[accent_color]" value="<?php echo esc_attr($settings['accent_color']); ?>" class="mws-color-picker" data-default-color="#25d366" id="mws_color_accent">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('自定义 CSS 样式', 'multi-whatsapp-support'); ?></th>
                                        <td>
                                            <textarea name="<?php echo self::OPTION_NAME; ?>[custom_css]" rows="5" class="large-text code" placeholder=".mws-popover { font-family: inherit; }"><?php echo esc_textarea(isset($settings['custom_css']) ? $settings['custom_css'] : ''); ?></textarea>
                                            <p class="description"><?php esc_html_e('可直接重写弹窗圆角、阴影或字体，无需修改主题样式表。', 'multi-whatsapp-support'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Tab 4: 短代码与开发 -->
                        <div class="mws-tab-panel" id="tab-shortcode">
                            <div class="mws-card">
                                <h2 class="mws-card-title"><?php esc_html_e('短代码 (Shortcodes) 使用指南', 'multi-whatsapp-support'); ?></h2>
                                <p class="mws-card-desc"><?php esc_html_e('您可以在任意文章、页面、Elementor、WPBakery 或侧边栏小工具中使用短代码：', 'multi-whatsapp-support'); ?></p>

                                <div class="mws-code-guide">
                                    <div class="mws-code-block">
                                        <h4>1. 默认触发按钮（点击即唤起弹窗）</h4>
                                        <code>[whatsapp_support]</code>
                                        <p><?php esc_html_e('可配合 text 属性覆盖按钮文字：', 'multi-whatsapp-support'); ?> <code>[whatsapp_support text="联系客服经理"]</code></p>
                                    </div>

                                    <div class="mws-code-block">
                                        <h4>2. 页面行内平铺所有客服卡片（适合 Contact Us 页面）</h4>
                                        <code>[whatsapp_support mode="cards" title="官方 WhatsApp 联络通道"]</code>
                                    </div>

                                    <div class="mws-code-block">
                                        <h4>3. 直达单个专属客服卡片</h4>
                                        <code>[whatsapp_support mode="card" channel="2"]</code>
                                        <p><?php esc_html_e('输出第 2 个客服专线卡片，点击直接跳转 WhatsApp。', 'multi-whatsapp-support'); ?></p>
                                    </div>

                                    <div class="mws-code-block">
                                        <h4>4. PHP 主题开发友好函数</h4>
                                        <code>&lt;?php if (function_exists('mws_render_whatsapp')) { echo mws_render_whatsapp(array('mode' =&gt; 'button')); } ?&gt;</code>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mws-submit-bar">
                            <button type="submit" class="button button-primary button-hero">
                                <?php esc_html_e('保存更改', 'multi-whatsapp-support'); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 右侧实时效果预览卡片 (Live Preview) -->
                <div class="mws-layout-side">
                    <div class="mws-preview-sticky">
                        <div class="mws-preview-header">
                            <span class="mws-preview-badge"><?php esc_html_e('实时效果预览 (Live Preview)', 'multi-whatsapp-support'); ?></span>
                            <span class="mws-preview-tip"><?php esc_html_e('所见即所得', 'multi-whatsapp-support'); ?></span>
                        </div>

                        <!-- 仿真弹窗 -->
                        <div class="mws-sim-modal" id="mwsSimModal">
                            <div class="mws-sim-header" id="mwsSimHeader">
                                <div class="mws-sim-header-left">
                                    <div class="mws-sim-header-icon">
                                        <?php echo MWS_Frontend_Renderer::get_whatsapp_svg('mws-sim-svg'); ?>
                                    </div>
                                    <div>
                                        <div class="mws-sim-title" id="mwsSimTitle"><?php echo esc_html($settings['popover_title']); ?></div>
                                        <div class="mws-sim-subtitle" id="mwsSimSubtitle"><?php echo esc_html($settings['popover_subtitle']); ?></div>
                                    </div>
                                </div>
                                <div class="mws-sim-close">✕</div>
                            </div>
                            <div class="mws-sim-body" id="mwsSimBody">
                                <!-- 由 JS 实时同步通道卡片 -->
                            </div>
                            <div class="mws-sim-footer" id="mwsSimFooter">
                                <?php echo esc_html($settings['popover_footer']); ?>
                            </div>
                        </div>

                        <!-- 仿真悬浮球 -->
                        <div class="mws-sim-fab-wrap">
                            <span class="mws-sim-fab-label"><?php esc_html_e('悬浮气泡球预览：', 'multi-whatsapp-support'); ?></span>
                            <div class="mws-sim-fab" id="mwsSimFab">
                                <?php echo MWS_Frontend_Renderer::get_whatsapp_svg('mws-sim-fab-svg'); ?>
                                <span class="mws-sim-dot"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 通道模板 (供 JS 动态新增) -->
        <template id="mws-channel-template">
            <?php
            $this->render_channel_form_item('__INDEX__', array(
                'badge'   => '__BADGE__',
                'label'   => __('新客服通道', 'multi-whatsapp-support'),
                'number'  => '',
                'sub'     => '',
                'action'  => 'Chat',
                'prefill' => '',
                'online'  => 1,
            ));
            ?>
        </template>
        <?php
    }

    /**
     * 渲染通道表单卡片。
     *
     * @param int|string $index
     * @param array $channel
     */
    protected function render_channel_form_item($index, $channel)
    {
        $badge   = !empty($channel['badge']) ? $channel['badge'] : ($index !== '__INDEX__' ? ($index + 1) : '1');
        $label   = !empty($channel['label']) ? $channel['label'] : '';
        $number  = !empty($channel['number']) ? $channel['number'] : '';
        $sub     = !empty($channel['sub']) ? $channel['sub'] : '';
        $action  = !empty($channel['action']) ? $channel['action'] : 'Chat';
        $prefill = !empty($channel['prefill']) ? $channel['prefill'] : '';
        $online  = !isset($channel['online']) || !empty($channel['online']);

        $name_prefix = self::OPTION_NAME . '[channels][' . $index . ']';

        ?>
        <div class="mws-channel-item" data-index="<?php echo esc_attr($index); ?>">
            <div class="mws-channel-handle" title="<?php esc_attr_e('拖拽调整排序', 'multi-whatsapp-support'); ?>">
                <span class="dashicons dashicons-menu"></span>
            </div>

            <div class="mws-channel-fields">
                <div class="mws-channel-row">
                    <div class="mws-ch-col mws-ch-badge">
                        <label><?php esc_html_e('编号/标识', 'multi-whatsapp-support'); ?></label>
                        <input type="text" name="<?php echo esc_attr($name_prefix); ?>[badge]" value="<?php echo esc_attr($badge); ?>" class="mws-input-badge" placeholder="1">
                    </div>
                    <div class="mws-ch-col mws-ch-label">
                        <label><?php esc_html_e('客服名称 / Tag (如 VIP Concierge 2)', 'multi-whatsapp-support'); ?></label>
                        <input type="text" name="<?php echo esc_attr($name_prefix); ?>[label]" value="<?php echo esc_attr($label); ?>" class="mws-input-label" placeholder="Customer Care 1" required>
                    </div>
                    <div class="mws-ch-col mws-ch-number">
                        <label><?php esc_html_e('WhatsApp 电话号码 (含国家区号)', 'multi-whatsapp-support'); ?></label>
                        <input type="text" name="<?php echo esc_attr($name_prefix); ?>[number]" value="<?php echo esc_attr($number); ?>" class="mws-input-number" placeholder="+86 190 6845 6595" required>
                    </div>
                </div>

                <div class="mws-channel-row">
                    <div class="mws-ch-col mws-ch-sub">
                        <label><?php esc_html_e('副标题 / 服务范围说明 (如 Pre-sales & General Assistance)', 'multi-whatsapp-support'); ?></label>
                        <input type="text" name="<?php echo esc_attr($name_prefix); ?>[sub]" value="<?php echo esc_attr($sub); ?>" class="mws-input-sub" placeholder="Pre-sales & General Assistance">
                    </div>
                    <div class="mws-ch-col mws-ch-action">
                        <label><?php esc_html_e('按钮文字', 'multi-whatsapp-support'); ?></label>
                        <input type="text" name="<?php echo esc_attr($name_prefix); ?>[action]" value="<?php echo esc_attr($action); ?>" class="mws-input-action" placeholder="Chat">
                    </div>
                    <div class="mws-ch-col mws-ch-online">
                        <label><?php esc_html_e('在线绿点', 'multi-whatsapp-support'); ?></label>
                        <label class="mws-switch mws-switch-sm">
                            <input type="checkbox" name="<?php echo esc_attr($name_prefix); ?>[online]" value="1" <?php checked($online); ?> class="mws-input-online">
                            <span class="mws-switch-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="mws-channel-row">
                    <div class="mws-ch-col mws-ch-full">
                        <label><?php esc_html_e('预设对话开场白 (Prefill Message, 访客点击后自动填入对话框)', 'multi-whatsapp-support'); ?></label>
                        <input type="text" name="<?php echo esc_attr($name_prefix); ?>[prefill]" value="<?php echo esc_attr($prefill); ?>" class="mws-input-prefill" placeholder="Hello, I have an inquiry about...">
                    </div>
                </div>
            </div>

            <div class="mws-channel-actions">
                <button type="button" class="button-link mws-btn-remove-channel" title="<?php esc_attr_e('删除此通道', 'multi-whatsapp-support'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
        <?php
    }
}
