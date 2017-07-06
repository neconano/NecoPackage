<?php
namespace neco\Phing;

use PhingFile;
use Symfony\Component\Yaml\Yaml;
use Task;
use YamlFileParser;

class ConvertConfig2iniTask extends Task
{
    private $configFile = '';
    private $destFile = '';

    // private function transPath($file){
    //     $dir = explode('\\vendor',__DIR__);
    //     $dir = explode('\\',$dir[1]);
    //     for($i=0; $i < count($dir); $i++ ) $str .= '../'; 
    //     if (strpos($file, '/') !== 0) {
    //         $file = realpath(__DIR__ . '/'. $str) . '/' . $file;
    //     }
    //     return $file;
    // }

    private function transPath($file){
        if (strpos($file, '/') !== 0) {
            $file = realpath(__DIR__ . '/../../../') . '/' . $file;
        }
        return $file;
    }

    public function setDestFile($file){
        $this->destFile = $this->transPath($file);
    }

    public function setConfigFile($file){
        $this->configFile = $this->transPath($file);
    }

    private function ensureFolderExist($file){
        $folder = dirname($file);
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
    }

    private function parseConfigItem($key, $value, &$configs, $originConfig){
        $keys = explode('.', $key);
        while ($k = array_shift($keys)) {
            $configs = &$configs[$k];
            if(is_array($originConfig))
                $re_config = $originConfig[$k];
        }
        $configs = isset($re_config) ? $re_config : $value;
    }

    private function arr2ini(array $a, array $parent = array()){
        $out = '';
        foreach ($a as $k => $v){
            if (is_array($v)){
                //subsection case
                //merge all the sections into one array...
                $sec = array_merge((array) $parent, (array) $k);
                //add section information to the output
                $out .= '[' . join('.', $sec) . ']' . PHP_EOL;
                //recursively traverse deeper
                $out .= $this->arr2ini($v, $sec);
            }
            else{
                //plain key->value case
                if(is_string($v))
                    $out .= "$k = '$v'" . PHP_EOL;
                else
                    $out .= "$k = $v" . PHP_EOL;
            }
        }
        return $out;
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

        $this->ensureFolderExist($this->destFile);
        file_put_contents(
            $this->destFile,
            $this->arr2ini($out)
        );
    }


}
