<?php

namespace Performance\AssetMerge;

use Silex\Application;
use \InvalidArgumentException;

class Merger {

    /** @var  Application */
    protected $app;

    /** @var  Config */
    protected $config;

    function __construct(Application $app) {
        $this->app = $app;
        $this->config = $app['assetmerge_config'];
    }

    /**
     * <pre>
     * If inactive outputs self::getScriptsRaw(),
     * else creates merged files(if needed) and outputs self::getScriptsMerged(),
     * </pre>
     * @return string output of:
     * @see self::getScriptsRaw()
     * @see self::getScriptsMerged()
     */
    public function getScripts() {

        $headCode = "";

        if ($this->config->getActive() == false) {
            $headCode = $this->getScriptsRaw();
            return $headCode;
        }

        if ($this->config->getAlwaysReMerge() == true || !$this->isMergedFilesExists()) {
            $MergedCssRules = $this->createMergedCssRules();
            $MergedCssRules_filtered = $this->filterCssRules($MergedCssRules);
            $this->saveMergedCssRules($MergedCssRules_filtered);

            $MergedJsCode = $this->createMergedJsCode();
            $MergedJsCode_filtered = $this->filterJsCode($MergedJsCode);
            $this->saveMergedJsCode($MergedJsCode_filtered);
        }

        $headCode = $this->getScriptsMerged();

        return $headCode;
    }

    /**
     * Simple output of css&js files defined in config
     * @return string HTML code (link and script tags) with links to unmerged original files
     */
    public function getScriptsRaw() {
        $headCode = "";
        foreach ($this->config->getCssFiles() as $css_file) {
            $headCode .= "<link href=\"" . $css_file . "\" rel=\"stylesheet\" type=\"text/css\"/> \n ";
        }
        foreach ($this->config->getJsFiles() as $js_file) {
            $headCode .= "<script src=\"" . $js_file . "\"></script> \r ";
        }
        return $headCode;
    }

    /**
     * <pre>
     * </pre>
     * @return string HTML code (link and script tags) with links to local MERGED files (and to remote, if FetchRemote off)
     * @throws InvalidArgumentException If can`t find merged files
     */
    public function getScriptsMerged() {
        $headCode = "";
        if (!$this->isMergedFilesExists()) {
            throw new InvalidArgumentException(__METHOD__ . " failed: Merged Files not found!");
        }

        $headCode .= '<link href="' . $this->getMergedCssFilePath() . '" rel="stylesheet" type="text/css"/>';

        if ($this->config->getFetchRemote() != true) {
            foreach ($this->config->getJsFiles() as $js_file) {
                if (filter_var($js_file, FILTER_VALIDATE_URL) && pathinfo($js_file, PATHINFO_EXTENSION) == "js") {
                    $headCode .= '<script src="' . $js_file . '" type="text/javascript"></script>';
                }
            }
        }

        $headCode .= '<script src="' . $this->getMergedJsFilePath() . '" type="text/javascript"></script>';
        return $headCode;
    }

    private function filterCssRules($rules) {
        $filtered_rules = str_replace("sourceMappingURL", "", $rules);
        return $filtered_rules;
    }

    private function filterJsCode($code) {
        $filtered_code = str_replace("sourceMappingURL", "", $code);
        return $filtered_code;
    }

    private function createMergedCssRules() {
        $merged_css = "";
        foreach ($this->config->getCssFiles() as $css_file) {
            if (file_exists($this->config->getWebRoot() . $css_file)) {
                $merged_css .= "\n/*file:{$css_file}*/\n" . file_get_contents($this->config->getWebRoot() . $css_file);
            }
        }
        return $merged_css;
    }

    private function saveMergedCssRules($MergedCssRules) {
        if (strlen($MergedCssRules) <= 0) {
            throw new InvalidArgumentException(__METHOD__ . " failed: empty rules");
        }
        $dir = $this->config->getMergedCssRootDir('full') . $this->config->getCssFilesHash();
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        $result = file_put_contents($this->getMergedCssFilePath('full'), $MergedCssRules);
        if ($result === false) {
            throw new InvalidArgumentException(__METHOD__ . " failed: file not saved");
        }
        return true;
    }

    private function createMergedJsCode() {
        $merged_js = "";
        foreach ($this->config->getJsFiles() as $js_file) {
            if (file_exists($this->config->getWebRoot() . $js_file)) {
                $merged_js .= "\n//file:{$js_file}\n" . file_get_contents($this->config->getWebRoot() . $js_file);
            } elseif (filter_var($js_file, FILTER_VALIDATE_URL) && pathinfo($js_file, PATHINFO_EXTENSION) == "js" && $this->config->getFetchRemote() == true) {
                $merged_js .= "\n//file:{$js_file}\n" . file_get_contents($js_file);
            }
        }
        return $merged_js;
    }

    /**
     * Saves merged JS code to file. File stored in subdirectory, placed in merged files root dir which defined by configuration.
     * Subdirectory will be automatically created with name = hash of concatenated JS filenames.
     * @param string $MergedJsCode String with merged JS code/
     * @return boolean
     * @throws InvalidArgumentException
     */
    private function saveMergedJsCode($MergedJsCode) {
        if (strlen($MergedJsCode) <= 0) {
            throw new InvalidArgumentException(__METHOD__ . " failed: empty code");
        }
        $dir = $this->config->getMergedJsRootDir('full') . $this->config->getJsFilesHash();
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        $result = file_put_contents($this->getMergedJsFilePath('full'), $MergedJsCode);
        if ($result === false) {
            throw new InvalidArgumentException(__METHOD__ . " failed: file not saved");
        }
        return true;
    }

    private function isMergedFilesExists() {
        return file_exists($this->getMergedCssFilePath('full')) && file_exists($this->getMergedJsFilePath('full'));
    }

    private function getMergedCssFilePath($mode = false) {
        return $this->config->getMergedCssRootDir($mode) . $this->config->getCssFilesHash() . "/" . $this->config->getMergedCssFileName();
    }

    private function getMergedJsFilePath($mode = false) {
        return $this->config->getMergedJsRootDir($mode) . $this->config->getJsFilesHash() . "/" . $this->config->getMergedJsFileName();
    }

}
