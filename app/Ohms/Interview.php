<?php namespace Ohms;

/*
 *  Model for the XML CacheFile
 *
 * @copyright Copyright &copy; 2012 Louie B. Nunn Center, University of Kentucky
 * @link http://www.uky.edu
 * @license http://www.uky.edu
 */

use Ohms\Interview\Legacy;
use Ohms\Interview\Version3;

class Interview
{
    /**
     * The cache file must be a plain *.xml file name inside tmpDir:
     * no path components, no traversal, no symlinks pointing elsewhere.
     */
    public static function isValidCacheFile($tmpDir, $cachefile)
    {
        if (!is_string($cachefile) || $cachefile === '' || strpos($cachefile, "\0") !== false) {
            return false;
        }
        if (basename($cachefile) !== $cachefile || strcasecmp(substr($cachefile, -4), '.xml') !== 0) {
            return false;
        }
        $dir = realpath($tmpDir);
        $path = realpath("{$tmpDir}/{$cachefile}");
        return $dir !== false && $path !== false && is_file($path)
            && strpos($path, $dir . DIRECTORY_SEPARATOR) === 0;
    }

    public static function getInstance($config, $configtmpDir, $cachefile = null)
    {
        $viewerconfig = $config;
        $tmpDir = $configtmpDir;
        if ($cachefile && !self::isValidCacheFile($tmpDir, $cachefile)) {
            throw new \Exception("Invalid CacheFile.");
        }
        if ($cachefile) {
            if ($myxmlfile = file_get_contents("{$tmpDir}/$cachefile")) {
                libxml_use_internal_errors(true);
                $filecheck = simplexml_load_string($myxmlfile);

                if (!$myxmlfile) {
                    $error_msg = "Error loading XML.\n<br />\n";
                    foreach (libxml_get_errors() as $error) {
                        $error_msg .= "\t" . $error->message;
                    }
                    throw new \Exception($error_msg);
                }
            } else {
                throw new \Exception("Invalid CacheFile.");
            }
        } else {
            throw new \Exception("Initialization requires valid CacheFile.");
        }

        $cacheversion = (string)$filecheck->record->version;
        if ($cacheversion=='') {
            return Legacy::getInstance($viewerconfig, $tmpDir, $cachefile);
        } else {
            return Version3::getInstance($viewerconfig, $tmpDir, $cachefile);
        }
    }
}
