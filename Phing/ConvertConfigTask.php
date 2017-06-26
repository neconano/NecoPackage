<?php
namespace neco\Phing;

use PhingFile;
use Symfony\Component\Yaml\Yaml;
use Task;
use YamlFileParser;

class ConvertConfigTask extends Task
{
    private $configFile = '';

    private $destFile = '';

    private function transPath($file)
    {
        if (strpos($file, '/') !== 0) {
            $file = realpath(__DIR__ . '/../../') . '/' . $file;
        }
        return $file;
    }

    public function setDestFile($file)
    {
        $this->destFile = $this->transPath($file);
    }

    public function setConfigFile($file)
    {
        $this->configFile = $this->transPath($file);
    }

    private function ensureFolderExist($file)
    {
        $folder = dirname($file);

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
    }

    public function main()
    {
        if (!file_exists($this->configFile)) {
            throw new \Exception('配置文件不存在' . $this->configFile);
        }

        $phingFile = new PhingFile($this->configFile);
        $originConfig = Yaml::parse($phingFile->getAbsoluteFile());
        $properties = $this->project->getProperties();
        $parser = new YamlFileParser();
        $configs = $parser->parseFile($phingFile);
        $out = [];
        foreach ($configs as $key => &$value) {
            if (isset($properties[$key])) {
                $value = $properties[$key];
                $this->parseConfigItem($key, $value, $out, $originConfig);
            }
        }

        $out['global']['APP_DEBUG'] = !!$out['global']['APP_DEBUG'];
        $this->ensureFolderExist($this->destFile);
        file_put_contents(
            $this->destFile,
            sprintf('<?php return %s;', var_export($out, true))
        );
    }

    private function parseConfigItem($key, $value, &$configs, $originConfig)
    {
        $keys = explode('.', $key);
        while ($k = array_shift($keys)) {
            $configs = &$configs[$k];
            $originConfig = $originConfig[$k];
        }
        $configs = is_array($originConfig) ? $originConfig : $value;
    }
}
