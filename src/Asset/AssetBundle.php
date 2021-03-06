<?php
declare(strict_types = 1);

namespace Yiisoft\Asset;

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\View\WebView;

/**
 * AssetBundle represents a collection of asset files, such as CSS, JS, images.
 *
 * Each asset bundle has a unique name that globally identifies it among all asset bundles used in an application. The
 * name is the [fully qualified class name](http://php.net/manual/en/language.namespaces.rules.php) of the class
 * representing it.
 *
 * An asset bundle can depend on other asset bundles. When registering an asset bundle with a view, all its dependent
 * asset bundles will be automatically registered.
 */
class AssetBundle
{
    /**
     * @var string the Web-accessible directory that contains the asset files in this bundle.
     *
     * If {@see sourcePath} is set, this property will be *overwritten* by {@see AssetManager} when it publishes the
     * asset files from {@see sourcePath}.
     *
     * You can use either a directory or an alias of the directory.
     */
    public $basePath;

    /**
     * @var string the base URL for the relative asset files listed in {@see js} and {@see css}.
     *
     * If {@see {sourcePath} is set, this property will be *overwritten* by {@see {AssetManager} when it publishes the
     * asset files from {@see {sourcePath}.
     *
     * You can use either a URL or an alias of the URL.
     */
    public $baseUrl;

    /**
     * @var array list of CSS files that this bundle contains. Each CSS file can be specified in one of the three
     * formats as explained in {@see js}.
     *
     * Note that only a forward slash "/" should be used as directory separator.
     */
    public $css = [];

    /**
     * @var array the options that will be passed to {@see View::registerCssFile()} when registering the CSS files in
     * this bundle.
     */
    public $cssOptions = [];

    /**
     * @var array list of bundle class names that this bundle depends on.
     *
     * For example:
     *
     * ```php
     * public $depends = [
     *    \Yiisoft\Web\YiiAsset::class,
     *    \yii\bootstrap\BootstrapAsset::class,
     * ];
     * ```
     */
    public $depends = [];

    /**
     * @var array list of JavaScript files that this bundle contains. Each JavaScript file can be specified in one of
     * the following formats:
     *
     * - an absolute URL representing an external asset. For example,
     *   `http://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js` or
     *   `//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js`.
     * - a relative path representing a local asset (e.g. `js/main.js`). The actual file path of a local asset can be
     *   determined by prefixing [[basePath]] to the relative path, and the actual URL of the asset can be determined
     *   by prefixing [[baseUrl]] to the relative path.
     * - an array, with the first entry being the URL or relative path as described before, and a list of key => value
     *   pairs that will be used to overwrite {@see jsOptions} settings for this entry.
     *
     * Note that only a forward slash "/" should be used as directory separator.
     */
    public $js = [];

    /**
     * @var array the options that will be passed to {@see View::registerJsFile()} when registering the JS files in this
     * bundle.
     */
    public $jsOptions = [];

    /**
     * @var array the options to be passed to {@see AssetManager::publish()} when the asset bundle  is being published.
     * This property is used only when {@see sourcePath} is set.
     */
    public $publishOptions = [];

    /**
     * @var string the directory that contains the source asset files for this asset bundle. A source asset file is a
     * file that is part of your source code repository of your Web application.
     *
     * You must set this property if the directory containing the source asset files is not Web accessible. By setting
     * this property, [[AssetManager]] will publish the source asset files to a Web-accessible directory automatically
     * when the asset bundle is registered on a page.
     *
     * If you do not set this property, it means the source asset files are located under {@see basePath}.
     *
     * You can use either a directory or an alias of the directory.
     *
     * {@see publishOptions}
     */
    public $sourcePath;

    /**
     * Publishes the asset bundle if its source code is not under Web-accessible directory.
     *
     * It will also try to convert non-CSS or JS files (e.g. LESS, Sass) into the corresponding CSS or JS files using
     * {@see AssetManager::converter|asset converter}.
     *
     * @param AssetManager $am the asset manager to perform the asset publishing
     * @return void
     */
    public function publish(AssetManager $am): void
    {
        if ($this->sourcePath !== null && !isset($this->basePath, $this->baseUrl)) {
            [$this->basePath, $this->baseUrl] = $am->publish($this->sourcePath, $this->publishOptions);
        }

        if (isset($this->basePath, $this->baseUrl) && ($converter = $am->getConverter()) !== null) {
            foreach ($this->js as $i => $js) {
                if (is_array($js)) {
                    $file = array_shift($js);
                    if ($this->isRelative($file)) {
                        $js = ArrayHelper::merge($this->jsOptions, $js);
                        array_unshift($js, $converter->convert($file, $this->basePath));
                        $this->js[$i] = $js;
                    }
                } elseif ($this->isRelative($js)) {
                    $this->js[$i] = $converter->convert($js, $this->basePath);
                }
            }
            foreach ($this->css as $i => $css) {
                if (is_array($css)) {
                    $file = array_shift($css);
                    if ($this->isRelative($file)) {
                        $css = ArrayHelper::merge($this->cssOptions, $css);
                        array_unshift($css, $converter->convert($file, $this->basePath));
                        $this->css[$i] = $css;
                    }
                } elseif ($this->isRelative($css)) {
                    $this->css[$i] = $converter->convert($css, $this->basePath);
                }
            }
        }
    }

    /**
     * Registers this asset bundle with a view.
     *
     * @param WebView $webView to be registered with
     *
     * @return AssetBundle the registered asset bundle instance
     */
    public static function register(WebView $webView): AssetBundle
    {
        return $webView->registerAssetBundle(static::class);
    }

    /**
     * Registers the CSS and JS files with the given view.
     *
     * @param Webview $view the view that the asset files are to be registered with.
     * @return void
     */
    public function registerAssetFiles(WebView $view): void
    {
        $manager = $view->getAssetManager();

        foreach ($this->js as $js) {
            if (is_array($js)) {
                $file = array_shift($js);
                $options = ArrayHelper::merge($this->jsOptions, $js);
                $view->registerJsFile($manager->getAssetUrl($this, $file), $options);
            } elseif ($js !== null) {
                $view->registerJsFile($manager->getAssetUrl($this, $js), $this->jsOptions);
            }
        }

        foreach ($this->css as $css) {
            if (is_array($css)) {
                $file = array_shift($css);
                $options = ArrayHelper::merge($this->cssOptions, $css);
                $view->registerCssFile($manager->getAssetUrl($this, $file), $options);
            } elseif ($css !== null) {
                $view->registerCssFile($manager->getAssetUrl($this, $css), $this->cssOptions);
            }
        }
    }

    /**
     * Returns a value indicating whether a URL is relative.
     * A relative URL does not have host info part.
     * @param string $url the URL to be checked
     * @return bool whether the URL is relative
     */
    protected function isRelative(string $url): bool
    {
        return strncmp($url, '//', 2) && strpos($url, '://') === false;
    }
}
