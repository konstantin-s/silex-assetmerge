<?php

namespace Performance\AssetMerge;

use Silex\Application;
use \InvalidArgumentException;

class Config {

    /** @var  Application */
    protected $app;
    protected $cssFiles;
    protected $jsFiles;
    protected $active;
    protected $fetchRemote;
    protected $alwaysReMerge;
    protected $mergedCssRootDir;
    protected $mergedJsRootDir;
    protected $mergedCssFileName = "styles.css";
    protected $mergedJsFileName = "js.js";
    protected $webRoot;

    function __construct(Application $app) {
        $this->app = $app;
        $config = array(
            "active" => true,
            "alwaysReMerge" => false,
            "fetchRemote" => true,
            "mergedCssRootDir" => "/assets/merged/",
            "mergedJsRootDir" => "/assets/merged/",
            "webRoot" => $this->app["request"]->server->get("CONTEXT_DOCUMENT_ROOT")
        );

        if (isset($app["assetmerge.config"]) && !empty($app["assetmerge.config"])) {
            $config = array_merge($config, $app["assetmerge.config"]);
        }

        $this->setWebRoot($config["webRoot"]);
        $this->setActive($config["active"]);
        $this->setalwaysReMerge($config["alwaysReMerge"]);
        $this->setfetchRemote($config["fetchRemote"]);
        $this->setMergedCssRootDir($config["mergedCssRootDir"]);
        $this->setMergedJsRootDir($config["mergedJsRootDir"]);
    }

    public function getWebRoot() {
        return $this->webRoot;
    }

    public function setWebRoot($webRoot) {
        $this->webRoot = $webRoot;
    }

    public function getMergedCssRootDir($mode = false) {
        if ($mode == 'full') {
            return $this->getWebRoot() . $this->mergedCssRootDir;
        }
        return $this->mergedCssRootDir;
    }

    public function getMergedJsRootDir($mode = false) {
        if ($mode == 'full') {
            return $this->getWebRoot() . $this->mergedJsRootDir;
        }
        return $this->mergedJsRootDir;
    }

    public function setMergedCssRootDir($mergedCssRootDir) {
        $path2check = $this->getWebRoot() . $mergedCssRootDir;
        if (!file_exists($path2check)) {
            throw new InvalidArgumentException(__METHOD__ . " failed: path not found: {$path2check}");
        }
        $this->mergedCssRootDir = $mergedCssRootDir;
    }

    public function setMergedJsRootDir($mergedJsRootDir) {
        $path2check = $this->getWebRoot() . $mergedJsRootDir;
        if (!file_exists($path2check)) {
            throw new InvalidArgumentException(__METHOD__ . " failed: path not found: {$path2check}");
        }
        $this->mergedJsRootDir = $mergedJsRootDir;
    }

    public function getActive() {
        return $this->active;
    }

    public function getFetchRemote() {
        return $this->fetchRemote;
    }

    public function getAlwaysReMerge() {
        return $this->alwaysReMerge;
    }

    public function setActive($active) {
        $this->active = $active;
    }

    public function setFetchRemote($fetchRemote) {
        $this->fetchRemote = $fetchRemote;
    }

    public function setAlwaysReMerge($alwaysReMerge) {
        $this->alwaysReMerge = $alwaysReMerge;
    }

    public function getCssFiles() {
        return $this->cssFiles;
    }

    public function setCssFiles(array $cssFiles) {
        foreach ($cssFiles as $css_file) {
            if (!file_exists($this->getWebRoot() . $css_file)) {
                throw new InvalidArgumentException(__METHOD__ . " {$css_file} not found");
            }
        }
        $this->cssFiles = $cssFiles;
    }

    public function addCssFile($cssFile) {
        $this->cssFiles[] = $cssFile;
    }

    public function getCssFilesHash() {
        if (empty($this->cssFiles)) {
            throw new InvalidArgumentException(__METHOD__ . " cssFiles not setted");
        }
        return md5(join("", $this->cssFiles));
    }

    public function getJsFilesHash() {
        if (empty($this->jsFiles)) {
            throw new InvalidArgumentException(__METHOD__ . " jsFiles not setted");
        }
        return md5(join("", $this->jsFiles));
    }

    public function getJsFiles() {
        return $this->jsFiles;
    }

    public function setJsFiles(array $jsFiles) {
        foreach ($jsFiles as $js_file) {

            if (filter_var($js_file, FILTER_VALIDATE_URL) && pathinfo($js_file, PATHINFO_EXTENSION) == "js") {
                continue;
            }

            if (!file_exists($this->getWebRoot() . $js_file)) {
                throw new InvalidArgumentException(__METHOD__ . " {$js_file} not found");
            }
        }
        $this->jsFiles = $jsFiles;
    }

    public function addJsFile($jsFile) {
        $this->jsFiles[] = $jsFile;
    }

    public function getMergedCssFileName() {
        return $this->mergedCssFileName;
    }

    public function getMergedJsFileName() {
        return $this->mergedJsFileName;
    }

    public function setMergedCssFileName($mergedCssFileName) {
        $this->mergedCssFileName = $mergedCssFileName;
    }

    public function setMergedJsFileName($mergedJsFileName) {
        $this->mergedJsFileName = $mergedJsFileName;
    }

}
