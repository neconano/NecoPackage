<?php
namespace neco\Phing;

use Exception;
use FileSet;
use Project;
use Task;
use WebSharks\CssMinifier\Core as CssMinifier;

class ConvertStaticTask extends Task
{
    protected $mapName = 'static_map.php';
    protected $mapNameJs = 'static_map.js';

    protected $filesets = [];

    protected $targetDir = '';

    protected $failonerror = false;

    private $map = [];

    private $projectPath = false;
    private $fullPath = false;

    private $mapCache = [];

    public function addFileSet(FileSet $fs)
    {
        $this->filesets[] = $fs;
        return $this;
    }

    public function setFailonerror($value)
    {
        $this->failonerror = $value;
    }

    /**
     * Sets the value of targetDir.
     *
     * @param mixed $targetDir the target dir
     *
     * @return self
     */
    public function setTargetDir($targetDir)
    {
        $this->targetDir = $targetDir;

        return $this;
    }

    /**
     * Sets the value of mapName.
     *
     * @param mixed $mapName the map name
     *
     * @return self
     */
    public function setMapName($mapName)
    {
        $this->mapName = $mapName;

        return $this;
    }

    /**
     * 加载已经缓存过的 map
     *
     * @author Cong Peijun <congpeijun@tuozhongedu.com>
     */
    private function loadMapCache()
    {
        $cacheFile = $this->targetDir . '/' . $this->mapName;

        if (file_exists($cacheFile)) {
            $this->mapCache = require $cacheFile;
        }
    }

    public function main()
    {
        if (!$this->project->getProperty('feature.hash_assets')) {
            $this->log('静态资源重命名hash化已关闭，请配置 `feature.hash_assets: true`', Project::MSG_WARN);
            return true;
        }
        $this->loadMapCache();
        foreach ($this->filesets as $fs) {
            try {
                $this->processFileSet($fs);
            } catch (BuildException $be) {
                // directory doesn't exist or is not readable
                if ($this->failonerror) {
                    throw $be;
                } else {
                    $this->log($be->getMessage(), $this->quiet ? Project::MSG_VERBOSE : Project::MSG_WARN);
                }
            }
        }
        $this->saveMap();
    }

    public function saveMap($type = 'php')
    {
        ksort($this->map);
        file_put_contents(
            $this->targetDir . '/' . $this->mapName,
            '<?php return ' . var_export($this->map, true) . ';'
        );

        foreach ($this->map as $key => &$value) {
            $value = $value['newFile'];
        }

        file_put_contents(
            $this->targetDir . '/' . $this->mapNameJs,
            json_encode($this->map)
        );

    }

    /**
     * @param FileSet $fs
     * @throws BuildException
     */
    protected function processFileSet(FileSet $fs)
    {
        $files = $fs->getDirectoryScanner($this->project)->getIncludedFiles();
        $this->fullPath || $this->fullPath = realpath($fs->getDir($this->project));
        foreach ($files as $file) {
            $this->convert($file);
        }
    }

    private function ensureFolerExist($target)
    {
        if (file_exists(dirname($target)) === false) {
            mkdir(dirname($target), 0777 - umask(), true);
        }
    }

    private function hadConverted($file, $override = false)
    {
        if ($override !== false) {
            if (isset($this->mapCache[$file]) && $this->mapCache[$file]['hash'] == $sha1) {
                $this->map[$file] = $this->mapCache[$file];
                return true;
            }
            return isset($this->map[$file]);
        }
        return false;
    }

    /**
     * calcule the file sha1 value.
     *
     * @author Cong Peijun <congpeijun@tuozhongedu.com>
     * @param  $file
     * @param  string $content
     * @return string
     */
    private function getFileSha1($file, $content)
    {
        if ($this->mapCache && isset($this->mapCache[$file]) && isset($this->mapCache[$file]['contains'])) {
            foreach ($this->mapCache[$file]['contains'] as $key => $v) {
                $this->convert($key);

                if (isset($this->map[$key]) && $this->map[$key]['newFile']) {
                    // $this->log($key);
                    // $this->log($this->map[$key]['newFile']);
                    $content .= $this->map[$key]['newFile'];
                }
            }
        }
        return sha1($content);
    }

    /**
     * 转换资源文件
     *
     * @author Cong Peijun <congpeijun@tuozhongedu.com>
     * @param  $string  $file 资源文件名称
     * @param  boolean $override 是否覆盖已经转换过的文件
     * @return [type]         [description]
     */
    private function convert($file, $override = false)
    {
        $fileFullPath = $this->fullPath . '/' . $file;
        $fileInfo = pathinfo($file);

        if (!file_exists($fileFullPath)) {
            $this->log('File does not exist : ' . $fileFullPath);
            return false;
        }
        $content = file_get_contents($fileFullPath);
        $sha1 = $this->getFileSha1($file, $content);
        $newFileName = sprintf(
            '%s_%s.%s',
            $fileInfo['filename'],
            substr($sha1, 0, 8),
            $fileInfo['extension']
        );

        $newFile = sprintf(
            '%s%s',
            $fileInfo['dirname'] . '/',
            $newFileName
        );

        $target = sprintf(
            '%s/%s',
            $this->targetDir,
            $newFile
        );

        if (isset($this->mapCache[$file]) && $this->mapCache[$file]['hash'] == $sha1 && ($override === false)) {
            $this->map[$file] = $this->mapCache[$file];
            // $this->log('File converted ' . $file . ' => ' . $newFile);
            return false;
        }

        // 第一次转换，未缓存
        if (isset($this->map[$file]) && $override === false) {
            // $this->log('File converted ' . $file . ' => ' . $newFile);
            return false;
        }

        $this->map[$file] = [
            'hash' => $sha1,
            'filepath' => $file,
            'filename' => $fileInfo['basename'],
            'newFileName' => $newFileName,
            'newFile' => $newFile,
        ];

        $this->ensureFolerExist($target);

        if ($fileInfo['extension'] == 'css') {
            $this->parseCss($content, $this->map[$file]);
        }

        if ($fileInfo['extension'] == 'js') {
            $this->parseJs($content, $this->map[$file]);
        }

        $this->log('Converting file ' . $file . ' => ' . $newFile);

        $fileInfo['newFileName'] = $newFileName;
        $this->getDeps($file);
        $this->cleanExpiredFiles($fileInfo);
        file_put_contents($target, $content);
    }

    private function cleanExpiredFiles($fileInfo)
    {
        $dir = $this->targetDir . '/' . $fileInfo['dirname'] . '/';
        foreach (glob($dir . $fileInfo['filename'] . '_*.' . $fileInfo['extension']) as $value) {
            if ($value !== $dir . $fileInfo['newFileName']) {
                $this->log(sprintf('Delete expired file %s', str_replace($this->targetDir . '/', '', $value)));
                unlink($value);
            }
        }
    }

    private function getDeps($filename)
    {
        if (!isset($this->mapCache[$filename]) || !isset($this->mapCache[$filename]['deps'])) {
            return false;
        }

        foreach ($this->mapCache[$filename]['deps'] as $key => $value) {
            $this->convert($key, true);
        }
    }

    /**
     * 获取资源文件绝对路径 相对 static
     * @param  array $map
     * @param  string $filename
     * @return array|string 转换后文件相关信息, 如果文件不存在返回原文件信息
     */
    private function getAbstractPath(&$map, $filename)
    {
        $base = $map['filepath'];
        $dirname = pathinfo($base, PATHINFO_DIRNAME);
        $fullfilename = $this->fullPath . '/' . $dirname . '/' . $filename;

        if (!($absfile = realpath($fullfilename))) {
            $this->log('File does not exist:  ' . $fullfilename, Project::MSG_WARN);
            return $filename;
        }

        $filename = ltrim(str_replace([$this->fullPath, $map['filename']], '', $absfile), '/');
        $this->convert($filename);
        $this->map[$filename]['deps'][$map['filepath']] = 1;
        $this->map[$filename]['newFile'] = str_replace('\\', '/', $this->map[$filename]['newFile']);
        return $this->map[$filename];
    }

    /**
     * 解析 css 内资源文件，并进行转换
     * @param  string &$content
     * @param  $map
     * @return string
     */
    private function parseCss(&$content, &$map)
    {
        $content = preg_replace_callback('#url\((.+?)\)#', function ($matches) use (&$map) {
            $matches[1] = trim(trim($matches[1], '"'), '\'');

            if (
                // Base64 data image
                strpos($matches[1], 'data:image') === 0 ||
                // blank
                strpos($matches[1], 'about:blank') === 0
            ) {
                return $matches[0];
            }

            $uriInfo = parse_url($matches[1]);
            $resultMap = $this->getAbstractPath($map, $uriInfo['path']);
            if (is_array($resultMap)) {
                $this->map[$map['filepath']]['contains'][$resultMap['filepath']] = 1;
                $resultMap = $resultMap['newFile'];
            }
            return sprintf(
                'url(/%s%s)',
                $resultMap,
                isset($uriInfo['fragment']) ? '#' . $uriInfo['fragment'] : '' // 带锚点的url
            );
        }, $content);

        if ($this->project->getProperty('feature.minify_css') && strpos($map['filename'], 'min') === false) {
            $content = CssMinifier::compress($content);
        }
        return $content;
    }

    /**
     * 解析js,压缩js
     *
     * @author Cong Peijun <congpeijun@tuozhongedu.com>
     * @param  string &$content
     * @param  array $map
     * @return string
     */
    private function parseJs(&$content, &$map)
    {
        if ($this->project->getProperty('feature.minify_js') && strpos($map['filename'], 'min') === false) {
            try {
                $content = forward_static_call(array('\\JShrink\\Minifier', 'minify'), $content);
                return $content;
            } catch (Exception $jsme) {
                $this->log(
                    sprintf('Could not minify file %s: %s', $map['filepath'], $jsme->getMessage()),
                    Project::MSG_ERR
                );
            }
        }
    }
}
