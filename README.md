# Multi WhatsApp Support (企业级多客服 WhatsApp 支持)

[![Version](https://img.shields.io/badge/version-1.0.0-emerald.svg)](https://github.com/wuyou2001/multi-whatsapp-support)
[![WordPress](https://img.shields.io/badge/WordPress-5.4+-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4+-777bb4.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPLv2-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**Multi WhatsApp Support** 是一款专为高端品牌电商与出海独立站打造的企业级多通道 WhatsApp 客服支持插件。完美承袭了国际奢品电商站点（如 eclara.shop）的视觉质感——包含极具层次感的高斯毛玻璃遮罩、微阴影悬浮卡片、丝滑展开微动效与多端自适应布局。

内置符合 **AGENTS.md 全局自动发布规范** 的 GitHub 静默自动更新器，无需任何第三方插件，开箱即用。

---

## 🌟 核心特性

- 🏢 **多客服通道分流矩阵 (Multi-Agent Routing)**：
  - 支持动态添加任意数量的客服专线；
  - 支持设置编号（Badge）、客服名称（如 VIP Concierge 2）、电话号码（自动格式化与国际区号支持）、服务范围副标题、操作按钮文案及独立预设问候语（Prefill Message）；
  - 支持在线状态指示灯（Green Status Dot）。
- 🎨 **顶级设计品质 (Enterprise Luxury UI)**：
  - 承袭高端毛玻璃（Frosted Glass）遮罩与微阴影卡片悬停反馈；
  - 顺滑展开与平移动画，采用符合原生应用手感的贝塞尔曲线；
  - 完美兼容各种屏幕分辨率（桌面端精致气泡弹窗，移动端智能转换为触控友好的底部抽屉）。
- 🎯 **双重智能挂载模式**：
  - **顶部 Header 导航自动注入**：针对 WoodMart、Astra、Flatsome 等主流电商主题，自动定位并挂载桌面端胶囊按钮与移动端圆形快捷按钮；
  - **全局浮动气泡球 (FAB)**：支持屏幕四个角落自由定位、边距微调、呼吸脉冲绿点提醒及鼠标悬停 Tooltip；
  - 支持双通道同时开启、单通道开启或纯短代码手动调用。
- ⚙️ **现代化直观设置面板**：
  - 支持通道拖拽排序（Drag & Drop Reorder）、实时增删通道；
  - 侧边栏实时交互预览面板（Live Preview），所见即所得；
  - 集成 WordPress 原生色盘选择器，自定义弹窗渐变与品牌强调色。
- ⚡ **短代码与主题开发支持**：
  - 提供功能丰富的 `[whatsapp_support]` 短代码与标准 PHP 辅助函数 `mws_render_whatsapp()`。
- 🔄 **全自动静默在线更新 (Silent GitHub Updater)**：
  - 严格遵循 `AGENTS.md` 规范：以主文件头 `* Version: 1.0.0` 为**单点真实源**；
  - 后台全自动静默检测 GitHub Releases 与 Tags，严禁在用户设置页添加任何杂乱配置，保障纯净体验。

---

## 🚀 预设开箱即用配置

插件激活后默认预设 3 条企业级标准客服通道：

| 通道编号 | 专属标签 (Tag) | 电话号码 (WhatsApp) | 业务说明 (Subtitle) | 动作 |
| :---: | :--- | :--- | :--- | :---: |
| **1** | `Customer Care 1` | `+86 190 6845 6595` | Pre-sales & General Assistance | Chat |
| **2** | `VIP Concierge 2` | `+86 197 0028 2871` | Bespoke Sourcing & VIP Orders | Chat |
| **3** | `Order Inquiry 3` | `+86 197 0028 2870` | Order Tracking & Fast Shipping | Chat |

- **弹窗标题**：`WhatsApp Support`
- **弹窗副标题**：`Online 24/7 Available`
- **底部安全背书**：`🔒 Official WhatsApp Support • Instant Response`

---

## 💻 短代码使用说明 (Shortcodes)

您可以在文章、页面、Elementor、WPBakery、Gutenberg 或侧边栏小工具中灵活嵌入短代码：

### 1. 默认弹窗触发按钮
```text
[whatsapp_support]
```
可配合 `text` 属性自定义按钮文字：
```text
[whatsapp_support text="联系客服顾问" align="center"]
```

### 2. 行内平铺客服卡片列表（极适合 Contact Us 联络页面）
```text
[whatsapp_support mode="cards" title="官方 WhatsApp 联络中心" align="center"]
```

### 3. 单个专属通道直达卡片
```text
[whatsapp_support mode="card" channel="2"]
```

### 4. 开发者 PHP 模板函数
```php
<?php
if (function_exists('mws_render_whatsapp')) {
    echo mws_render_whatsapp(array(
        'mode'  => 'button',
        'text'  => 'Chat on WhatsApp',
        'align' => 'right'
    ));
}
?>
```

---

## 📂 插件目录结构

```text
multi-whatsapp-support/
├── multi-whatsapp-support.php        # 插件主入口文件，包含单点真实源版本号与更新器接入
├── includes/
│   ├── class-github-updater.php      # GitHub 自动静默更新器（符合 AGENTS.md 规范）
│   ├── class-admin-settings.php      # 现代管理后台设置页、实时效果预览与通道管理
│   └── class-frontend-renderer.php   # 前端组件、Header 挂载、弹窗、SVG 图标与短代码
├── assets/
│   ├── css/
│   │   ├── frontend.css              # 高端毛玻璃、微阴影、浮动气泡与响应式交互样式
│   │   └── admin.css                 # 现代化后台管理与侧边栏实时预览样式
│   └── js/
│       ├── frontend.js               # 前端弹窗状态控制、Header 自动注入与事件追踪
│       └── admin.js                  # 后台拖拽排序、实时效果预览同步与动态字段处理
└── README.md                         # 完整使用说明与技术文档
```

---

## 🛠️ 主题兼容性建议

- **WoodMart 主题**：
  - 默认桌面端选择器 `.whb-general-header .whb-col-right`
  - 默认移动端选择器 `.whb-general-header .whb-mobile-right`
  - 自动插入到 My Account 图标前，完美契合 WoodMart Header Builder。
- **通用主题 (Astra / OceanWP / GeneratePress / Storefront)**：
  - 在“显示与挂载位置”中将桌面端选择器调整为您的 Header 右侧容器类名（如 `.site-header .header-tools` 或 `nav`）；
  - 或直接选用“仅右下角悬浮气泡 (Floating Bubble Only)”模式，全站零配置自适应生效。

---

## 📈 数据埋点与事件监听

为了便于外贸电商分析转化漏斗，每次访客点击任意客服卡片时，前端均会向 `document` 派发全局自定义事件 `mws:chat_click`：

```javascript
document.addEventListener('mws:chat_click', function(e) {
  console.log('用户点击了客服:', e.detail.channelIndex, e.detail.channelName);
  // 可直接调用 gtag / Facebook Pixel 进行转化跟踪:
  // gtag('event', 'whatsapp_click', { 'channel': e.detail.channelName });
});
```

---

## 📄 开源许可证
GPLv2 or later.
