<?php
/**
 * Файл класса ImageContainer
 *
 * @copyright Copyright (c) 2018, Oleg Chulakov Studio
 * @link http://chulakov.com/
 */

namespace chulakov\filestorage\image;

use chulakov\filestorage\ImageComponent;
use Intervention\Image\Constraint;
use Intervention\Image\Image;
use yii\helpers\FileHelper;

class ImageContainer implements ImageInterface
{
    /**
     * @var Image
     */
    protected $image;
    /**
     * Сохранено ли изображение
     *
     * @var bool
     */
    protected $saved = false;

    /**
     * Конструктор контейнера обработки изображения
     *
     * @param Image $image
     */
    public function __construct(Image $image)
    {
        $this->image = $image;
    }

    public function isSaved()
    {
        return $this->saved;
    }

    /**
     * @inheritdoc
     */
    public function getWidth()
    {
        return $this->image->getWidth();
    }

    /**
     * @inheritdoc
     */
    public function getHeight()
    {
        return $this->image->getHeight();
    }

    /**
     * @inheritdoc
     */
    public function getMimeType()
    {
        return $this->image->mime;
    }

    /**
     * @inheritdoc
     */
    public function getExtension()
    {
        return $this->image->extension;
    }

    /**
     * @inheritdoc
     */
    public function getFileSize()
    {
        return $this->image->filesize();
    }

    /**
     * @inheritdoc
     */
    public function watermark($watermarkPath, $position = ImageComponent::POSITION_CENTER)
    {
        if (!empty($watermarkPath)) {
            $this->image->insert($watermarkPath, $position);
        }
    }

    /**
     * @inheritdoc
     */
    public function resize($width, $height)
    {
        $currentWidth = $this->getWidth();
        $currentHeight = $this->getHeight();

        if (!empty($width) && !empty($height)) {
            if ($this->checkSizeForResize($width, $height)) {
                $this->image->resize($width, $height, function (Constraint $constraint) {
                    $constraint->aspectRatio();
                });
            } elseif (!empty($width) && $currentWidth < $width) {
                $this->image->widen($currentWidth);
            } elseif (!empty($height) && $currentHeight < $height) {
                $this->image->heighten($currentHeight);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function convert($encode)
    {
        $this->image->encode($encode);
    }

    /**
     * @inheritdoc
     * @throws \yii\base\Exception
     */
    public function save($path, $quality)
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            FileHelper::createDirectory($dir);
        }
        $this->saved = true;
        return !!$this->image->save($path, $quality);
    }

    /**
     * Получить текущее изображение
     *
     * @return Image
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Проверка размера изображения
     *
     * @param $width
     * @param $height
     * @return bool
     */
    protected function checkSizeForResize($width, $height)
    {
        return ($this->getWidth() > $width) && ($this->getHeight() > $height);
    }
}