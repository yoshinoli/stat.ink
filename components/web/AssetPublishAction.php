<?php
/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

declare(strict_types=1);

namespace app\components\web;

use DirectoryIterator;
use ReflectionClass;
use Yii;
use yii\base\Action;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;
use yii\web\Response;
use yii\web\YiiAsset;

class AssetPublishAction extends Action
{
    public $directories = [
        '@app/assets' => 'app\assets',
    ];
    public $classes = [
        AssetBundle::class,
        JqueryAsset::class,
        YiiAsset::class,
    ];

    public function run()
    {
        $resp = Yii::$app->response;
        $resp->format = 'yaml';

        return $this->enumerateClasses();
    }

    protected function enumerateClasses(): array
    {
        $list = array_reduce(
            array_map(
                [$this, 'enumerateDirectoryClasses'],
                array_keys($this->directories),
                array_values($this->directories)
            ),
            'array_merge',
            $this->classes
        );
        natsort($list);
        return array_values(array_unique($list));
    }

    protected function enumerateDirectoryClasses(string $directory, string $namespace): array
    {
        $result = [];
        $it = new DirectoryIterator(Yii::getAlias($directory));
        foreach ($it as $entry) {
            $fileName = $entry->getBasename();
            if (substr($fileName, 0, 1) === '.') {
                continue;
            }

            if ($entry->isDir()) {
                $result = array_merge(
                    $result,
                    $this->enumerateDirectoryClasses(
                        $entry->getPathname(),
                        $namespace . '\\' . $fileName
                    )
                );
                continue;
            }

            if ($entry->getExtension() === 'php') {
                // クラス名は本当はマルチバイトを利用可能だが規約違反なので無視する
                if (!preg_match('!^([a-zA-Z_][a-zA-Z0-9_]*)\.php$!', $fileName, $match)) {
                    continue;
                }

                try {
                    $fqcn = $namespace . '\\' . $match[1];
                    if (!class_exists($fqcn)) {
                        continue;
                    }

                    $ref = new ReflectionClass($fqcn);
                    if ($ref->isInstantiable()) {
                        $result[] = $fqcn;
                    }
                } catch (\Exception $e) {
                }
            }
        }

        return $result;
    }
}