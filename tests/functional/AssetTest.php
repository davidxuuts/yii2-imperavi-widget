<?php
/**
 * This file is part of yii2-imperavi-widget.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://github.com/vova07/yii2-imperavi-widget
 */

namespace davidxu\imperavi\tests\functional;

use davidxu\imperavi\tests\functional\data\overrides\TestAsset;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * @author Vasile Crudu <bazillio07@yandex.ru>
 *
 * @link https://github.com/vova07
 */
final class AssetTest extends TestCase
{
    /**
     * Test asset registering.
     */
    public function testRegister()
    {
        $view = $this->getView();

        $this->assertEmpty($view->assetBundles);

        $asset = TestAsset::register($view);
        $asset->addLanguage('zh');
        $asset->addPlugins(['clips', 'fullscreen']);

        $this->assertCount(2, $view->assetBundles);
        $this->assertArrayHasKey(JqueryAsset::className(), $view->assetBundles);
        $this->assertInstanceOf(AssetBundle::className(), $view->assetBundles[TestAsset::className()]);

        $content = $view->renderFile('@davidxu/imperavi/tests/data/views/rawlayout.php');

        $this->assertContains('redactor.css', $content);
        $this->assertContains('redactor.min.js', $content);
        $this->assertContains('jquery.js', $content);
        $this->assertContains('zh.js', $content);
        $this->assertContains('clips.css', $content);
        $this->assertContains('clips.js', $content);
        $this->assertContains('fullscreen.js', $content);
    }
}
