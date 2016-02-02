<?php

namespace Performance\AssetMerge;

use Silex\Application;
use \InvalidArgumentException;

/**
 * Merges JS and CSS files to single file, cache it, returns HTML code with merged files
 */
class Merger {

    /** @var  Application */
    protected $app;

    /** @var  Config */
    protected $config;

    function __construct(Application $app) {
        $this->app = $app;
        $this->config = $app['assetmerge_config'];
    }

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
            $fileContents = file_get_contents($this->config->getWebRoot() . $css_file);
            if ($fileContents === false) {
                throw new InvalidArgumentException(__METHOD__ . " failed: cannot read {$css_file} ");
            }
            $merged_css .= "\n/*file:{$css_file}*/\n" . $fileContents;
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
        file_put_contents($this->getMergedCssFilePath('full'), $MergedCssRules);
        return true;
    }

    private function createMergedJsCode() {
        $merged_js = "";
        foreach ($this->config->getJsFiles() as $js_file) {

            if (filter_var($js_file, FILTER_VALIDATE_URL) && pathinfo($js_file, PATHINFO_EXTENSION) == "js" && $this->config->getFetchRemote() == true) {
                $fileContents = file_get_contents($js_file);
            } else {
                $fileContents = file_get_contents($this->config->getWebRoot() . $js_file);
            }
            if ($fileContents === false) {
                throw new InvalidArgumentException(__METHOD__ . " failed: cannot read {$js_file} ");
            }
            $merged_js .= "\n//file:{$js_file}\n" . $fileContents;
        }
        return $merged_js;
    }

    private function saveMergedJsCode($MergedJsCode) {
        if (strlen($MergedJsCode) <= 0) {
            throw new InvalidArgumentException(__METHOD__ . " failed: empty code");
        }
        $dir = $this->config->getMergedJsRootDir('full') . $this->config->getJsFilesHash();
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        file_put_contents($this->getMergedJsFilePath('full'), $MergedJsCode);
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
