<?php
/**
 * Файл класса ImageManager
 *
 * @copyright Copyright (c) 2017, Oleg Chulakov Studio
 * @link http://chulakov.com/
 */

namespace chulakov\filestorage\managers;

use yii\base\BaseObject;
use chulakov\filestorage\uploaders\Event;
use chulakov\filestorage\uploaders\ObserverInterface;
use chulakov\filestorage\uploaders\UploadInterface;

/**
 * Class SaveManager
 * @package chulakov\filestorage\managers
 */
class FileManager extends BaseObject implements FileInterface, ListenerInterface
{
    /**
     * Подключение трейта для работы с изображениями
     */
    use ImageTrait;

    /**
     * @var UploadInterface
     */
    protected $uploader;

    /**
     * Событие на сохранение
     *
     * @param Event $event
     * @return mixed
     */
    public function handle(Event $event)
    {
        if ($event->sender && $event->sender instanceof UploadInterface) {
            $this->uploader = $event->sender;
            if ($this->isImage()) {
                $this->processing();

                $this->uploader->setExtension($this->getExtension());
                $this->uploader->setType($this->getType());

                $savedPath = $this->uploader->uploadPath($event->savedPath);
                if ($event->needSave && $this->saveImage($savedPath)) {
                    $event->needSave = false;
                }
                if ($event->needDelete && $this->deleteFile($event->filePath)) {
                    $event->needDelete = false;
                }
                //http://image.intervention.io/api/filesize
                //Returns the size of the image file in bytes or false if image instance is not created from a file.
                $this->uploader->setSize($this->getSize());
            }
        }
    }

    /**
     * Присоединение к Observer
     *
     * @param ObserverInterface $observer
     * @return mixed
     */
    public function attach(ObserverInterface $observer)
    {
        $observer->on(Event::SAVE_EVENT, [$this, 'handle']);
    }

    /**
     * Обработка файла
     */
    protected function processing()
    {
        $this->loadImage($this->uploader->getFile());
        $this->transformation();
    }

    /**
     * Сохранение изображения
     *
     * @param string $savedPath
     * @return \Intervention\Image\Image
     */
    protected function saveImage($savedPath)
    {
        return $this->imageManager->getImage()->save($savedPath, $this->quality);
    }

    /**
     * Удаление файла
     *
     * @param string $filePath
     * @return bool
     */
    protected function deleteFile($filePath)
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        return true;
    }

    /**
     * Проверка файла на изображение
     *
     * @return bool
     */
    public function isImage()
    {
        if ($this->uploader) {
            return strpos($this->getType(), 'image') !== false;
        }
        return false;
    }

    /**
     * Получить расширение файла
     *
     * @return mixed
     */
    public function getExtension()
    {
        if (!empty($this->encode)) {
            return $this->encode;
        }
        if ($this->isImage() &&
            $ext = $this->imageManager->getImage()->extension) {
            if (!empty($ext)) {
                return $ext;
            }
        }
        return $this->uploader->getExtension();
    }

    /**
     * Получение MIME типа файла
     *
     * @return string
     */
    public function getType()
    {
        if ($this->imageManager->hasImage()) {
            return $this->imageManager->getImage()->mime;
        }
        return $this->uploader->getType();
    }

    /**
     * Получение размера файла
     *
     * @return integer
     */
    public function getSize()
    {
        if ($this->isImage() &&
            $size = $this->imageManager->getImage()->filesize()) {
            if (!empty($size)) {
                return $size;
            }
        }
        return $this->uploader->getSize();
    }
}