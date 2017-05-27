<?php

namespace Performance\AssetMerge;

use Silex\Application;
use \InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Merges JS and CSS files to single file, cache it, returns HTML code with merged files
 */
class Merger {

    /** @var  Application */
    protected $app;

    /** @var  Config */
    protected $config;

    function __construct(Application $app) {
        $this->app    = $app;
        $this->config = $app['assetmerge_config'];
        if ($this->config->getFlushMerged()) {
            $this->flushMerged();
        }
    }

    /**
     * Merge if needed and return html for head
     * @return string <link href="path_to_merged
     */
    public function getCssOnly() {
        if ($this->config->getAlwaysReMerge() == true || !file_exists($this->getMergedCssFilePath('full'))) {
            $MergedCssRules          = $this->createMergedCssRules();
            $MergedCssRules_filtered = $this->filterCssRules($MergedCssRules);
            $this->saveMergedCssRules($MergedCssRules_filtered);
        }

        $cssCode = "";
        if (file_exists($this->getMergedCssFilePath('full'))) {
            $cssCode .= '<link href="' . $this->getMergedCssFilePath() . '" rel="stylesheet" type="text/css"/>';
        }

        return $cssCode;
    }

    /**
     * Merge if needed and return html for head
     * @return string '<script src="path_to_merged
     */
    public function getJSOnly() {

        if ($this->config->getAlwaysReMerge() == true || !file_exists($this->getMergedJsFilePath('full'))) {
            $MergedJsCode          = $this->createMergedJsCode();
            $MergedJsCode_filtered = $this->filterJsCode($MergedJsCode);
            $this->saveMergedJsCode($MergedJsCode_filtered);
        }

        $cssCode = '';
        if (file_exists($this->getMergedJsFilePath('full'))) {
            $cssCode .= '<script src="' . $this->getMergedJsFilePath() . '" type="text/javascript"></script>';
        }

        return $cssCode;
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
            $MergedCssRules          = $this->createMergedCssRules();
            $MergedCssRules_filtered = $this->filterCssRules($MergedCssRules);
            $this->saveMergedCssRules($MergedCssRules_filtered);

            $MergedJsCode          = $this->createMergedJsCode();
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

    /**
     * Delete all dirs, containing merged JS and CSS files, in configured CssRootDir and JsRootDir
     */
    public function flushMerged() {
        $fs = new Filesystem();

        $cssMergedDirs = $this->getDirsToFlush($this->config->getMergedCssRootDir(true));
        $fs->remove($cssMergedDirs);

        $jsMergedDirs = $this->getDirsToFlush($this->config->getMergedJsRootDir(true));
        $fs->remove($jsMergedDirs);
    }

    /**
     * Gets all subdirs of dir in parameter except "dot" dirs and "fonts" named dir
     * @param string $dirToIterate Root dir which content will be removed
     * @return array
     */
    private function getDirsToFlush($dirToIterate) {
        $toDeleteDirs = array();
        $dir          = new \DirectoryIterator($dirToIterate);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDir() || $fileinfo->isDot() || $fileinfo->getFilename() == "fonts") {
                continue;
            }
            $toDeleteDirs[] = $fileinfo->getPathname();
        }
        return $toDeleteDirs;
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
        $result = file_put_contents($this->getMergedCssFilePath('full'), $MergedCssRules);
        if ($result === false) {
            throw new InvalidArgumentException(__METHOD__ . " failed: file not saved");
        }
        return true;
    }

    private function createMergedJsCode() {
        $merged_js = "";
        foreach ($this->config->getJsFiles() as $js_file) {
            if (empty($js_file)) {
                continue;
            }

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

    /**
     * Returns path to file with merged CSS scripts
     * @param bool $mode If true returns  path with webRoot config param( by default webRoot: $this->app["request"]->server->get("CONTEXT_DOCUMENT_ROOT")
     * @return type
     */
    private function getMergedCssFilePath($mode = false) {
        return $this->config->getMergedCssRootDir($mode) . $this->config->getCssFilesHash() . "/" . $this->config->getMergedCssFileName();
    }

    /**
     * Returns path to file with merged JS scripts
     * @param bool $mode If true returns  path with webRoot config param( by default webRoot: $this->app["request"]->server->get("CONTEXT_DOCUMENT_ROOT")
     * @return type
     */
    private function getMergedJsFilePath($mode = false) {
        return $this->config->getMergedJsRootDir($mode) . $this->config->getJsFilesHash() . "/" . $this->config->getMergedJsFileName();
    }

}
