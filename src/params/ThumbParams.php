<?php
/**
 * Файл класса ThumbParams
 *
 * @copyright Copyright (c) 2017, Oleg Chulakov Studio
 * @link http://chulakov.com/
 */

namespace chulakov\filestorage\params;

/**
 * Class ThumbParams
 * @package chulakov\filestorage\params
 */
class ThumbParams extends ImageParams
{
    /**
     * @var string Категория превью
     */
    public $group = 'thumbs';
    /**
     * Шаблон сохранения thumbnails файлов
     *
     * @var string
     */
    public $pathPattern = '{group}/{basename}/{width}x{height}.{ext}';
    /**
     * Шаблон удаления файлов.
     * Испольует glob для поиска всех файлов.
     *
     * @var string
     */
    public $deletePattern = '{group}/{basename}/*';

    /**
     * Конструктор параметров
     *
     * @param integer $width
     * @param integer $height
     */
    public function __construct($width = 195, $height = 144)
    {
        parent::__construct($width, $height);
    }
}
