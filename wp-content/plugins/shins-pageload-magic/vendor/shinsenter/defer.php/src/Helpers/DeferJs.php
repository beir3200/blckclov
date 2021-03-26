<?php

/**
 * Defer.php aims to help you concentrate on web performance optimization.
 * (c) 2021 AppSeeds https://appseeds.net/
 *
 * PHP Version >=5.6
 *
 * @category  Web_Performance_Optimization
 * @package   AppSeeds
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2021 AppSeeds
 * @license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
 * @link      https://code.shin.company/defer.php
 * @see       https://code.shin.company/defer.php/blob/master/README.md
 */

namespace AppSeeds\Helpers;

use AppSeeds\Elements\DocumentNode;

class DeferJs
{
    const DEFERJS_ID  = 'defer-js';
    const POLYFILL_ID = 'polyfill-js';
    const HELPERS_JS  = 'defer-script';
    const HELPERS_CSS = 'defer-css';

    protected $deferjs_src;
    protected $polyfill_src;
    private $_cache;

    public function __construct(
        $deferjs_src,
        $polyfill_src,
        $offline_cache_path,
        $offline_cache_ttl
    ) {
        $this->deferjs_src  = $deferjs_src;
        $this->polyfill_src = $polyfill_src;

        $this->_cache = new DeferCache([
            'path'       => $offline_cache_path ?: DeferConstant::SCR_DEFERJS_CACHE,
            'defaultTtl' => (int) $offline_cache_ttl ?: 86400,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DOM related functions
    |--------------------------------------------------------------------------
     */

    /**
     * Remove all defer helpers from DOMDocument
     *
     * @return ElementNode
     */
    public function cleanDeferTags(DocumentNode &$dom)
    {
        $tags = implode(',', [
            'script#' . self::DEFERJS_ID,
            'script#' . self::POLYFILL_ID,
        ]);

        $dom->find($tags)->detach();

        return $dom;
    }

    /**
     * Remove all defer helpers from DOMDocument
     *
     * @return ElementNode
     */
    public function cleanHelperTags(DocumentNode &$dom)
    {
        $tags = implode(',', [
            'script#' . self::HELPERS_JS,
            'style#' . self::HELPERS_CSS,
        ]);

        $dom->find($tags)->detach();

        return $dom;
    }

    /**
     * Return new inline <script> node
     *
     * @return ElementNode
     */
    public function getInlineScript(DocumentNode $dom)
    {
        if ($this->isLocal($this->deferjs_src)) {
            $defer = @file_get_contents($this->deferjs_src);
        } else {
            $defer = $this->getFromCache();
        }

        $name = $this->isWebUrl($this->deferjs_src)
                ? $this->deferjs_src
                : '@shinsenter/defer.js';

        $defer = '/*!' . $name . '*/' . PHP_EOL . DeferMinifier::minifyJs($defer);

        return $dom->newNode('script', $defer, [
            'id' => self::DEFERJS_ID,
        ]);
    }

    /**
     * Return new inline <script> node
     *
     * @param  mixed       $withPolyfill
     * @return ElementNode
     */
    public function getInlineGuide(DocumentNode $dom, $withPolyfill = true)
    {
        static $message;

        if (!isset($message)) {
            $message = sprintf(
                DeferConstant::TEMPLATE_MANUALLY_ADD_DEFER,
                implode('\n', [
                    'You should manually add the defer.js.',
                    'Like this:',
                    strtr(
                        $this->getDeferJsNode($dom, $withPolyfill)->getOuterHtml(),
                        ['/' => '\/', '\'' => '\\\'', '\"' => '\\"', "\n" => '\n']
                    ),
                ])
            );

            $message = DeferMinifier::minifyJs($message);
        }

        return $dom->newNode('script', $message, ['id' => self::DEFERJS_ID]);
    }

    /**
     * Return new <script src="defer.js"> node
     * @return ElementNode
     */
    public function getDeferJsNode(DocumentNode $dom)
    {
        // Fallback to inline script when a local path given
        if (!$this->isWebUrl($this->deferjs_src)) {
            return $this->getInlineScript($dom);
        }

        return $dom->newNode('script', [
            'id'  => self::DEFERJS_ID,
            'src' => $this->deferjs_src,
        ]);
    }

    /**
     * Return polyfill script node
     *
     * @return null|ElementNode
     */
    public function getPolyfillNode(DocumentNode $dom)
    {
        if ($this->isWebUrl($this->polyfill_src)) {
            $script = "'IntersectionObserver'in window||"
                        . "document.write('<script src=\"" . $this->polyfill_src . "\"><\\/script>');";
        } elseif ($this->isLocal($this->polyfill_src)) {
            $script = @file_get_contents($this->polyfill_src);
        }

        return empty($script) ? null : $dom->newNode('script', $script, ['id' => self::POLYFILL_ID]);
    }

    /**
     * Return helper script node
     *
     * @param  int              $default_defer_time
     * @param  null|string      $copy
     * @return null|ElementNode
     */
    public function getHelperJsNode(
        DocumentNode $dom,
        $default_defer_time = null,
        $copy = null
    ) {
        static $script;

        if (!isset($script)) {
            $script = @file_get_contents(DEFER_PHP_ROOT . '/public/helpers.min.js');

            if ($default_defer_time > 0) {
                $script = 'var ' . DeferConstant::JS_GLOBAL_DELAY_VAR
                    . '=' . $default_defer_time . ';' . $script;
            }
        }

        if ($script && $copy) {
            $copy   = '[\'' . strtr($copy, ["\n" => '\n', "\r" => '\n', "'" => '\\\'']) . '\']';
            $script = preg_replace('#\[\'Optimized[^\]]+\]#i', $copy, $script);
        }

        return empty($script) ? null : $dom->newNode('script', $script, ['id' => self::HELPERS_JS]);
    }

    /**
     * Return helper script node
     *
     * @return null|ElementNode
     */
    public function getHelperCssNode(DocumentNode $dom)
    {
        static $content;

        if (!isset($content)) {
            $content = @file_get_contents(DEFER_PHP_ROOT . '/public/styles.min.css');
        }

        return empty($content) ? null : $dom->newNode('style', $content, ['id' => self::HELPERS_CSS]);
    }

    /**
     * Get script when using custom defer type
     *
     * @param  mixed            $type
     * @return null|ElementNode
     */
    public function getCustomDeferTypeNode(DocumentNode $dom, $type)
    {
        if ($type == DeferConstant::TXT_DEFAULT_DEFERJS) {
            return null;
        }

        $script = sprintf('Defer.all(\'script[type="%s"]\')', $type);

        return $dom->newNode('script', $script);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper methods
    |--------------------------------------------------------------------------
     */

    /**
     * Check a path is an URL
     *
     * @since  2.0.0
     * @param  mixed $path
     * @return bool
     */
    public function isWebUrl($path)
    {
        if (preg_match('#^(https?:)?//#i', $path)) {
            return true;
        }

        return false;
    }

    /**
     * Check a path is a local path
     *
     * @since  2.0.0
     * @param  mixed $path
     * @return bool
     */
    public function isLocal($path)
    {
        return file_exists($path);
    }

    /**
     * Get cache instance
     *
     * @since  2.0.0
     * @param  mixed $driver
     * @return mixed
     */
    public function cache()
    {
        return $this->_cache;
    }

    /**
     * Get cache key
     *
     * @since  2.0.0
     * @return string
     */
    public function cacheKey()
    {
        return gethostname() . '@' . $this->deferjs_src;
    }

    /*
    |--------------------------------------------------------------------------
    | Static functions
    |--------------------------------------------------------------------------
     */

    /**
     * Get deferjs from cache
     *
     * @since  2.0.0
     * @return string
     */
    public function getFromCache()
    {
        $key = $this->cacheKey();

        if (!$this->cache()->has($key)) {
            $defer = $this->makeOffline($key);

            if (empty($defer)) {
                $defer = @file_get_contents(DeferConstant::SRC_DEFERJS_FALLBACK);

                if (empty($defer)) {
                    throw new DeferException('Could not load defer.js library! Please check your configuration.');
                }

                return $defer;
            }
        }

        return $this->cache()->get($key);
    }

    /**
     * Create fully local version of external assets
     * For General Data Protection Regulation GDPR (DSGVO)
     *
     * @since  2.0.0
     * @param  null|mixed $duration
     * @param  null|mixed $key
     * @return mixed
     */
    public function makeOffline($key = null, $duration = 9999999999)
    {
        if (empty($this->deferjs_src)) {
            return false;
        }

        $defer = @file_get_contents($this->deferjs_src);

        if (empty($defer)) {
            return false;
        }

        $this->cache()->set($key ?: $this->cacheKey(), $defer, $duration);

        return $defer;
    }

    /**
     * Static method to purge all cached objects in DeferCache
     *
     * @since  2.0.0
     * @param  null|mixed $key
     * @return self
     */
    public function purgeOffline($key = null)
    {
        $this->cache()->delete($key ?: $this->cacheKey());
    }
}
