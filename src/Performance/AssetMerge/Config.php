<?php

namespace Performance\AssetMerge;

use Silex\Application;
use \InvalidArgumentException;

class Config {

    /** @var  Application */
    protected $app;
    protected $cssFiles;
    protected $jsFiles;
    protected $active = true;
    protected $fetchRemote = true;
    protected $alwaysReMerge = false;
//    protected $mergedCssFilePath = "/assets/merged/styles.css";
//    protected $mergedJsFilePath = "/assets/merged/js.js";
    protected $mergedCssRootDir = "/assets/merged/";
    protected $mergedJsRootDir = "/assets/merged/";
    protected $mergedCssFileName = "styles.css";
    protected $mergedJsFileName = "js.js";
    protected $webRoot;

    function __construct(Application $app) {
        $this->app = $app;
        if (!isset($app["assetmerge.config"])) {
            return;
        }
        $webRoot = isset($app["assetmerge.config"]["webRoot"]) ? $app["assetmerge.config"]["webRoot"] : $this->app["request"]->server->get("CONTEXT_DOCUMENT_ROOT");
        $this->setWebRoot($webRoot);

        if (isset($app["assetmerge.config"]["active"])) {
            $this->setActive($app["assetmerge.config"]["active"]);
        }
        if (isset($app["assetmerge.config"]["fetchRemote"])) {
            $this->setfetchRemote($app["assetmerge.config"]["fetchRemote"]);
        }
        if (isset($app["assetmerge.config"]["alwaysReMerge"])) {
            $this->setalwaysReMerge($app["assetmerge.config"]["alwaysReMerge"]);
        }
        if (isset($app["assetmerge.config"]["mergedCssFilePath"])) {
            $this->setMergedCssFilePath($app["assetmerge.config"]["mergedCssFilePath"]);
        }
        if (isset($app["assetmerge.config"]["mergedJsFilePath"])) {
            $this->setMergedJsFilePath($app["assetmerge.config"]["mergedJsFilePath"]);
        }
    }

    public function getWebRoot() {
        return $this->webRoot;
    }

    public function setWebRoot($webRoot) {
        $this->webRoot = $webRoot;
    }

//    public function getMergedCssFilePath($mode = false) {
//        if ($mode == 'full') {
//            return $this->getWebRoot() . $this->mergedCssFilePath;
//        }
//        return $this->mergedCssFilePath;
//    }
//
//    public function getMergedJsFilePath($mode = false) {
//        if ($mode == 'full') {
//            return $this->getWebRoot() . $this->mergedJsFilePath;
//        }
//        return $this->mergedJsFilePath;
//    }
//
//    public function setMergedCssFilePath($mergedCssFilePath) {
//        $path2check = $this->getWebRoot() . dirname($mergedCssFilePath);
//        if (!file_exists($path2check)) {
//            throw new InvalidArgumentException(__METHOD__ . " failed: path not found: {$path2check}");
//        }
//        $this->mergedCssFilePath = $mergedCssFilePath;
//    }
//
//    public function setMergedJsFilePath($mergedJsFilePath) {
//        $path2check = $this->getWebRoot() . dirname($mergedJsFilePath);
//        if (!file_exists($path2check)) {
//            throw new InvalidArgumentException(__METHOD__ . " failed: path not found: {$path2check}");
//        }
//        $this->mergedJsFilePath = $mergedJsFilePath;
//    }

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
