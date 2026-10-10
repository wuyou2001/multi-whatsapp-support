<?php
/**
 * Plugin Name: Multi WhatsApp Support (企业级多客服 WhatsApp 支持)
 * Plugin URI: https://github.com/wuyou2001/multi-whatsapp-support
 * Description: 高端企业级多客服 WhatsApp 沟通套件。支持多通道客服矩阵、全自动 Header 导航挂载、全局悬浮气泡、毛玻璃微阴影质感、智能移动端自适应与短代码 [whatsapp_support]，内置 GitHub 静默自动更新。
 * Version: 1.0.1
 * Author: Wwnine
 * Author URI: https://github.com/wuyou2001
 * Text Domain: multi-whatsapp-support
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// 核心常量定义
define('MWS_PLUGIN_FILE', __FILE__);
define('MWS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MWS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * 单点真实源版本提取器：动态读取主文件头部注释 Version
 *
 * @return string
 */
function mws_get_version()
{
    static $version = null;
    if ($version === null) {
        $file_data = function_exists('get_file_data')
            ? get_file_data(MWS_PLUGIN_FILE, array('Version' => 'Version'))
            : array();
        $version = !empty($file_data['Version']) ? trim($file_data['Version']) : '1.0.1';
    }
    return $version;
}

// 接入 GitHub 静默自动更新器（严格遵循 AGENTS.md 规范，后台自动静默检测更新）
if (is_admin()) {
    require_once MWS_PLUGIN_DIR . 'includes/class-github-updater.php';
    new WP_GitHub_Plugin_Updater(MWS_PLUGIN_FILE, '', 'wuyou2001/multi-whatsapp-support');
}

// 加载核心模块
require_once MWS_PLUGIN_DIR . 'includes/class-frontend-renderer.php';
require_once MWS_PLUGIN_DIR . 'includes/class-admin-settings.php';

/**
 * 插件全局单例核心调度器
 */
class Multi_WhatsApp_Support
{
    /**
     * 单例实例
     *
     * @var Multi_WhatsApp_Support|null
     */
    private static $instance = null;

    /**
     * 前端渲染组件
     *
     * @var MWS_Frontend_Renderer
     */
    public $frontend;

    /**
     * 后台设置组件
     *
     * @var MWS_Admin_Settings
     */
    public $settings;

    /**
     * 运行时配置缓存
     *
     * @var array|null
     */
    private $cached_settings = null;

    /**
     * 获取单例
     *
     * @return Multi_WhatsApp_Support
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     */
    private function __construct()
    {
        $this->frontend = new MWS_Frontend_Renderer($this);
        if (is_admin()) {
            $this->settings = new MWS_Admin_Settings($this);
        }

        add_action('init', array($this, 'load_textdomain'));
    }

    /**
     * 加载语言包
     */
    public function load_textdomain()
    {
        load_plugin_textdomain('multi-whatsapp-support', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * 预设默认配置项（严格符合业务规范与 eclara.shop 质感）
     *
     * @return array
     */
    public static function get_default_settings()
    {
        return array(
            'enabled'      => 1,
            'display_mode' => 'both', // 'both' | 'floating' | 'header' | 'manual'

            // 弹窗头部文案与渐变质感
            'popover_title'    => 'WhatsApp Support',
            'popover_subtitle' => 'Online 24/7 Available',
            'popover_footer'   => '🔒 Official WhatsApp Support • Instant Response',
            'gradient_start'   => '#075e54',
            'gradient_mid'     => '#128c7e',
            'gradient_end'     => '#25d366',
            'accent_color'     => '#25d366',

            // Header 导航挂载
            'header_enabled'           => 1,
            'header_selector_desktop'  => '.whb-general-header .whb-col-right',
            'header_selector_mobile'   => '.whb-general-header .whb-mobile-right',
            'header_insert_position'   => 'before_account',
            'header_btn_tag'           => 'Live Support',
            'header_btn_text'          => 'WhatsApp',

            // 悬浮气泡球
            'floating_enabled'     => 1,
            'floating_position'    => 'bottom-right',
            'floating_offset_x'    => 24,
            'floating_offset_y'    => 24,
            'floating_pulse'       => 1,
            'floating_tooltip'     => 'WhatsApp Support',
            'floating_hide_mobile' => 0,

            // 预设客服专线通道
            'channels' => array(
                array(
                    'badge'   => '1',
                    'label'   => 'Customer Care 1',
                    'number'  => '+86 190 6845 6595',
                    'sub'     => 'Pre-sales & General Assistance',
                    'action'  => 'Chat',
                    'prefill' => 'Hello, I have an inquiry.',
                    'online'  => 1,
                ),
                array(
                    'badge'   => '2',
                    'label'   => 'VIP Concierge 2',
                    'number'  => '+86 197 0028 2871',
                    'sub'     => 'Bespoke Sourcing & VIP Orders',
                    'action'  => 'Chat',
                    'prefill' => 'Hello VIP Concierge, I would like bespoke assistance.',
                    'online'  => 1,
                ),
                array(
                    'badge'   => '3',
                    'label'   => 'Order Inquiry 3',
                    'number'  => '+86 197 0028 2870',
                    'sub'     => 'Order Tracking & Fast Shipping',
                    'action'  => 'Chat',
                    'prefill' => 'Hello, I would like to inquire about my order.',
                    'online'  => 1,
                ),
            ),

            'custom_css' => '',
        );
    }

    /**
     * 获取全站当前生效配置
     *
     * @return array
     */
    public function get_settings()
    {
        if ($this->cached_settings !== null) {
            return $this->cached_settings;
        }

        $defaults = self::get_default_settings();
        $stored   = get_option('mws_settings', array());

        if (!is_array($stored) || empty($stored)) {
            $this->cached_settings = $defaults;
        } else {
            $this->cached_settings = wp_parse_args($stored, $defaults);
            if (empty($this->cached_settings['channels']) || !is_array($this->cached_settings['channels'])) {
                $this->cached_settings['channels'] = $defaults['channels'];
            }
        }

        return $this->cached_settings;
    }

    /**
     * 插件激活回调：初始化默认配置项
     */
    public static function activate()
    {
        if (!get_option('mws_settings')) {
            update_option('mws_settings', self::get_default_settings());
        }
    }
}

// 激活挂钩
register_activation_hook(__FILE__, array('Multi_WhatsApp_Support', 'activate'));

// 实例化主插件
function multi_whatsapp_support()
{
    return Multi_WhatsApp_Support::instance();
}
add_action('plugins_loaded', 'multi_whatsapp_support');

/**
 * 主题/模版开发者全局函数：输出或返回 WhatsApp 组件
 *
 * @param array $args
 * @return string
 */
function mws_render_whatsapp($args = array())
{
    $instance = Multi_WhatsApp_Support::instance();
    if ($instance && $instance->frontend) {
        return $instance->frontend->render_shortcode($args);
    }
    return '';
}
