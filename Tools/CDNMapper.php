<?php
// 用来获得CDN资源工具

namespace neco\Tools;
use neco\String\Utils as StringUtils;
use neco\Tools\ConfigBag;

class CDNMapper
{
    private $staticMpper = [];
    private $cdn_domain = '';
    private $schema = '';
    private $isSsl = false;
    private $hashStatic = false;
    // private static $gloabJsLoaded = false;

    // 转成实际地址
    private function transPath($file){
        $dir = explode('\\vendor',__DIR__);
        $dir = explode('\\',$dir[1]);
        for($i=0; $i < count($dir); $i++ ) $str .= '../'; 
        if (strpos($file, '/') !== 0) {
            $file = realpath(__DIR__ . '/'. $str) . '/' . $file;
        }
        return $file;
    }

    // 初始化压缩解析
    private function _init_static_map($mapperFile = ''){
        $mapperFile = $mapperFile ? $mapperFile : ConfigBag::getConfigByKey('global.CDN_DIR') . '/static_map.php';
        $mapperFile = self::transPath($mapperFile);
        if (!file_exists($mapperFile)) 
            return false;    
        $this->staticMpper = require $mapperFile;
        $this->schema = StringUtils::getRequestSchema();
        $this->isSsl = $this->schema === 'https';
        $this->cdn_domain = ConfigBag::getConfigByKey('url.CDN_DOMAIN');
        $this->hashStatic = ConfigBag::getConfigByKey('feature.hash_assets') === true;
        if (!$this->hashStatic) 
            return false;   
        return true;     
    }

    /**
     * 重新渲染页面用静态资源替换原有的
     * @param string &$contents [description]
     */
    public function renderOutput(&$contents){
        if(!self::_init_static_map())
            return false;    
        self::renderCss($contents);
        self::renderImg($contents);
        self::renderScript($contents);
        self::renderLink($contents);
        // self::purifyHtml($contents);
        // $this->loadGloabJs($contents);
    }

    // 地址替换
    private function replaceCallBack($matches){
        return str_replace($matches[1], '"' . self::getCDNUrl(trim($matches[1], '"')) . '"', $matches[0]);
    }

    /**
     * 获得对应CDN资源
     * @param  string $src
     * @return string
     */
    private function getCDNUrl($src){
        if (!$this->hashStatic || !$this->staticMpper)
            return $src;
        $srcInfo = parse_url($src);
        $file = ltrim($srcInfo['path'], '/');

        // 如果是https请求，将http的url装换成https。
        // if ( strpos($this->cdn_domain, $srcInfo['host']) !== false && strpos($src, 'https') !== 0) {
        //     return StringUtils::convetHttp2Https($src);
        // }

        if (isset($srcInfo['host']) &&
            strpos($this->cdn_domain, $srcInfo['host']) !== false &&
            isset($this->staticMpper[$file]) ) {
            return rtrim($this->cdn_domain, '/') . '/' . ltrim($this->staticMpper[$file]['newFileName'], '/');
        }
        return $src;
    }

    public function renderCss(&$contents)
    {
        $contents = preg_replace_callback(
            '@<link.+?href=(".+?").*?>@',
            function ($matches) {
                return self::replaceCallBack($matches);
            },
            $contents);

        return $this;
    }

    public function renderImg(&$contents)
    {
        $contents = preg_replace_callback(
            '@<img.+?src=(".*?").*?>@',
            function ($matches) {
                return self::replaceCallBack($matches);
            },
            $contents);

        $contents = preg_replace_callback(
            '@<img.+?data\-original=(".*?").*?>@',
            function ($matches) {
                return self::replaceCallBack($matches);
            },
            $contents);
        return $this;
    }

    public function renderScript(&$contents)
    {
        $contents = preg_replace_callback(
            '@<script.+?src=(".+?").*?>@',
            function ($matches) {
                return self::replaceCallBack($matches);
            },
            $contents);
        return $this;
    }

    /**
     * 渲染 a 标签，用来自动适应 http or https.
     *
     * @author Cong Peijun <congpeijun@tuozhongedu.com>
     * @param  string &$contents
     * @return Manager $this
     */
    public function renderLink(&$contents)
    {
        if ($this->schema === 'http') {
            return $this;
        }
        $domain = ConfigBag::getConfigByKey('global.DOMAIN');
        $contents = preg_replace_callback(
            '@<a.+?href="(.+?)".*?>@',
            function ($matches) use ($domain) {
                if (strpos($matches[1], $domain) !== false
                    && strpos($matches[1], 'http') === 0
                    && strpos($matches[1], 'https') === false) {
                    // 本站域名转换为 https
                    return str_replace($matches[1], 'https' . substr($matches[1], 4), $matches[0]);
                }
                return $matches[0];
            },
            $contents
        );
        return $this;
    }

    // public function purifyHtml(&$contents)
    // {
    //     if (ConfigBag::getConfigByKey('feature.minify_html')) {
    //         $HTMLMinify = new HTMLMinify($contents, ['optimizationLevel' => HTMLMinify::OPTIMIZATION_ADVANCED]);
    //         $contents = $HTMLMinify->process();
    //     }
    //     return $this;
    // }

    // 增加全局js
    // private function loadGloabJs(&$contents)
    // {
    //     if (self::$gloabJsLoaded || IS_AJAX) {
    //         return $this;
    //     }
    //     $pos = strpos($contents, '<script');
    //     if ($pos !== false) {
    //         $before = substr($contents, 0, $pos);
    //         $contents = sprintf(
    //             '%s</script><script>
    //             var jiemo = {
    //                 "staticUrl": null,
    //                 "imgHomeUrl": null
    //             };
    //             jiemo.staticUrl="%s";jiemo.imgHomeUrl="%s";
    //             </script>%s',
    //             $before,
    //             rtrim($this->staticUrl, '/'),
    //             $this->imgHomeUrl,
    //             substr($contents, $pos)
    //         );
    //     }
    //     self::$gloabJsLoaded = true;
    //     return $this;
    // }




}
