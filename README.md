## ThirdLogin - 简介

一款可以使Typecho博客系统支持使用第三方账号登录、注册的插件。

如果此插件对你有用的话，还请给个Star支持一下~

## 版权说明

该插件的最初始的版本为[jiangmuzi/TeConnect](https://github.com/jiangmuzi/TeConnect)，最后维护时间为2016年。

后由大卫二次开发并修复：[ledccn/TeConnect](https://github.com/ledccn/TeConnect)，最后维护时间为2020年。

本人于2023年年底，在原有的基础上删除了多个已经关闭，或目前基本上无人使用的第三方登录渠道，并新增了基于微信开放平台的扫码登录渠道。因微信开放平台的“特立独行”（部分接口参数与其他平台不一致），导致开发过程中需要更改公共代码，为在配合原插件调试时避免冲突而更改了插件的包名，在此特别说明并向两位作者的贡献表示感谢。

## 插件功能

使Typecho支持用第三方账号进行登录/注册（注册功能取决于插件设置）；除支持的登录渠道外其他功能基本与[ledccn/TeConnect](https://github.com/ledccn/TeConnect)一致，可前往原仓库查看，此处不再重复说明。所有登录方式均对接渠道官方的OAuth接口，不支持第三方聚合登录/转发平台。

目前支持的登录方式：

* 微信（仅支持[微信开放平台](https://open.weixin.qq.com)）
* QQ
* 微博
* 百度
* GitHub
* Google
* 微软

## 兼容性

经过实际测试，目前该插件可以在Typecho1.2.1版本上稳定运行，并支持PHP8.0（因更高的PHP版本尚不稳定因此未做测试，但不代表在更高的PHP版本上无法运行，如有需要可自行测试）。

对于MySQL的版本尚未进行测试，但在开发环境中的MySQL5.7上运行良好。

## 安装

前往[Releases](https://github.com/baiwulin/ThirdLogin/releases)页面下载插件，解压至目录usr/plugins中，在后台启用插件即可。也可在仓库中下载zip压缩包后解压至目录usr/plugins中，需要将插件目录重命名为`ThirdLogin`。

具体参数在插件设置页面中已有说明，此处不再赘述。

## 问题反馈

此插件已在以下两个平台开源：

* GitHub：[https://github.com/baiwulin/ThirdLogin](https://github.com/baiwulin/ThirdLogin)
* Gitee：[https://gitee.com/baiwulin/ThirdLogin](https://gitee.com/baiwulin/ThirdLogin)

如果在使用中遇到需要反馈的问题，请在发布此插件的博文（[https://www.baiwulin.com/50.html](https://www.baiwulin.com/50.html)）下留言，开源仓库仅用作代码托管，平时不关注Issues。
