<?php
/**
 * This file is part of yii2-imperavi-widget.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://github.com/vova07/yii2-imperavi-widget
 */

namespace davidxu\imperavi\bundles;

use yii\web\AssetBundle;

/**
 * @author Vasile Crudu <bazillio07@yandex.ru>
 *
 * @link https://github.com/vova07/yii2-imperavi-widget
 */
class ImageManagerAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@davidxu/imperavi/assets';

    /**
     * @inheritdoc
     */
    public $js = [
        'custom/plugins/imagemanager/imagemanager.js',
    ];
}
