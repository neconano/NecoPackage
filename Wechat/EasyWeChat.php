<?php
namespace  neco\Wechat;

use EasyWeChat\Foundation\Application;
use think\Config;

/**
 * Class Wechat  微信类
 * @package neco\Wechat
 * @method static \EasyWeChat\Core\AccessToken                access_token
 * @method static \EasyWeChat\Server\Guard                    server
 * @method static \EasyWeChat\User\User                       user
 * @method static \EasyWeChat\User\Tag                        user_tag
 * @method static \EasyWeChat\User\Group                      user_group
 * @method static \EasyWeChat\Js\Js                           js
 * @method static \Overtrue\Socialite\SocialiteManager        oauth
 * @method static \EasyWeChat\Menu\Menu                       menu
 * @method static \EasyWeChat\Notice\Notice                   notice
 * @method static \EasyWeChat\Material\Material               material
 * @method static \EasyWeChat\Material\Temporary              material_temporary
 * @method static \EasyWeChat\Staff\Staff                     staff
 * @method static \EasyWeChat\Url\Url                         url
 * @method static \EasyWeChat\QRCode\QRCode                   qrcode
 * @method static \EasyWeChat\Semantic\Semantic               semantic
 * @method static \EasyWeChat\Stats\Stats                     stats
 * @method static \EasyWeChat\Payment\Merchant                merchant
 * @method static \EasyWeChat\Payment\Payment                 payment
 * @method static \EasyWeChat\Payment\LuckyMoney\LuckyMoney   lucky_money
 * @method static \EasyWeChat\Payment\MerchantPay\MerchantPay merchant_pay
 * @method static \EasyWeChat\Reply\Reply                     reply
 * @method static \EasyWeChat\Broadcast\Broadcast             broadcast
 * @method static \EasyWeChat\Card\Card                       card
 * @method static \EasyWeChat\Device\Device                   device
 * @method static \EasyWeChat\ShakeAround\ShakeAround         shakearound
 * @method static \EasyWeChat\User\MiniAppUser                mini_app_user
 * @method static \EasyWeChat\OpenPlatform\OpenPlatform       open_platform
 *
 */
class EasyWeChat
{
    protected static $app;

    public static function app()
    {
        if (!isset(self::$app)) {
            $extra_config = include CONF_PATH_COMMON.'extra.php';
            $wechat = Config::get('wechat');
            $guzzle = Config::get('guzzle');
            self::$app = new Application($extra_config+$wechat+$guzzle);
        }
        return self::$app;
    }

    public static function __callStatic($name, $arguments)
    {
        return self::app()->$name;
    }
}