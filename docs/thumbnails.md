#Thumbnails

##Генерация `thumbnail` изображений

Компонент `FileStorage` обеспечивает генерацию `thumbnail` изображений, а также кеширование результирующих изображений.
Использовать генерацию `thumbnail` можно в контроллере, навесив данное событие на определенный роутер. 

Сам `thumbnails` представляет из себя поведение и подключается к модели следующим образом: 
```php

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(),
            [
                [
                    'class' => ThumbBehavior::className()
                ]
            ]);
    }
    
```
В тоже время модель должна реализовывать стандартную схему таблицы(из миграции) для компонента `filestorage`, 
подробнее смотрите в [readme.md](/README.md).

Ниже представлен пример использования генерации `thumbnail`:

```php

    /**
     * Генерирование кеша
     *
     * @return string
     */
    public function actionPhotos()
    {
        $cacheImages = []; // urls to image

        /** @var Image[] $images */
        $images = Image::findAll(['group_code' => 'photos']);

        $thumbParams = new ThumbParams(150, 100);
        $thumbParams->extension = 'gif';
        $thumbParams->quality = 95;

        foreach ($images as $image) {
            /**
             *  Получить из кеша изображение размером 640x480
             *  или же сгенерировать, закешировать и выдать
             */
            if ($thumb = $image->thumb($thumbParams)) {
                $cacheImages[] = $thumb;
            }
        }
        
        return json_encode(ArrayHelper::toArray($cacheImages));
    }
```
####, где: 
- `Image` - модель искомого файла;
- `ThumbParams` - параметры генерации `thumbnails`;
- `$image->thumb()` - генерация  `thumbnail` и получение `url` на него.

Сам ThumbParams имеет следующие поля: 

```
    /**
     *  Ширина
     *
     * @var integer
     */
    public $width;
    /**
     * Высота
     *
     * @var integer
     */
    public $height;
    /**
     * Расширение
     *
     * @var string
     */
    public $extension;
    /**
     * Качество
     *
     * @var int
     */
    public $quality = 100;
    /**
     * Путь к файлу с watermark
     *
     * @var string
     */
    public $watermarkPath;
    /**
     * Позиция watermark
     *
     * @var integer
     */
    public $watermarkPosition;
```

Из каждой модели `Image` можно получить сразу же `url` на сгенерированное изображение. Данный функционал обеспечивает функция: `thumb()`