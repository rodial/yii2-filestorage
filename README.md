#FileStorage - загрузка и хранение файлов

Компонент позволяющий загружать и хранить файлы, генерировать `thumbnails` изображений. 

####Возможности компонента: 
- `resize` изображения;
- конвертирование в разные расширения;
- наложение водяных меток;
- генерирование изображений: `thumbnails`, `cover`, `contain`, `widen`, `heighten` от оригинального файла с различными настройками;

На данной странице описано базовое использование и базовые настройки данного компонента. Остальное можно прочитать в следующих разделах: 

 - [Слушатели](docs/listeners.md);
 - [Генерация изображений](docs/image-generation.md);
 - [Генерация файлового пути](docs/path-service.md).

##Установка

1) Чтобы установить компонент, нужно в `composer.json` добавить следующие строки: 
```
"require": {
    "oleg-chulakov-studio/yii2-filestorage": "dev-master"
},
...
"repositories": [
    {
        "type": "vcs",
        "url":  "git@bitbucket.org:OlegChulakovStudio/yii2-filestorage.git"
    }
]
```

2) Выполнить в корне проекта следующую команду, чтобы обновить зависимости проекта и подгрузить компонент: 
```bash
composer update
```

##Настройка

1) Подключение компонента хранилища. 

В `config/main.php` нужно подключить компонент, он понадобится для сохранения файлов. 
Подключение выглядит так: 
```php
        'fileStorage' => [
            'class' => \chulakov\filestorage\FileStorage::className(),
            'storageBaseUrl' => false, // Базовый url
            'storagePath' => '@webroot', // Путь сохранения
            'storageDir' => 'uploaded',  // Папка с сохраняемыми файлами
            'fileMode' => 0755, // Уровень доступа к сохраняемым файлам
            'storagePattern' => '{group}/{id}', // Корневой шаблон генерации пути сохранения файлов
        ],
        'imageComponent' => [
            'class' => \chulakov\filestorage\ImageComponent::className(),
            'driver' => 'gd', // Базовые драйвера: gd и imagick
        ]
```

2) Выполнить миграции. 

Чтобы выполнить миграции нужно вызвать следующую комманду из корня приложения:
```bash
php yii migrate/up --migrationPath=@vendor/chulakov/filestorage/src/migration
```

При выполнении миграции будет создана таблица `file` со следующим содержимым:

```
+---------------+------------------+------+-----+---------+----------------+
| Field         | Type             | Null | Key | Default | Extra          |
+---------------+------------------+------+-----+---------+----------------+
| id            | int(11)          | NO   | PRI | NULL    | auto_increment |
| group_code    | varchar(16)      | NO   | MUL | NULL    |                |
| object_id     | int(11)          | YES  | MUL | NULL    |                |
| object_type   | varchar(16)      | YES  |     | NULL    |                |
| ori_name      | varchar(255)     | NO   |     | NULL    |                |
| ori_extension | varchar(16)      | NO   |     | NULL    |                |
| sys_file      | varchar(255)     | NO   | UNI | NULL    |                |
| mime          | varchar(255)     | NO   |     | NULL    |                |
| size          | int(11) unsigned | NO   |     | 0       |                |
| created_at    | int(11)          | NO   |     | NULL    |                |
| updated_at    | int(11)          | NO   |     | NULL    |                |
+---------------+------------------+------+-----+---------+----------------+
```
####, где 
- `group_code` - код группы;
- `object_id` - ID прикрепленного объекта;
- `object_type` - Тип прикрепленного объекта;
- `ori_name` - оригинальное название файла;
- `ori_extension` - оригинальное расширение файла;
- `sys_file` - системное название файла;
- `mime` - mime тип файла;
- `size` - размер файла;
- `created_at` - дата создания файла;
- `updated_at` - дата обновления файла.

3) Подключение поведения модели.

Система сохранения работает с помощью поведений, они ответственны за доставку файла модели.
Данные поведения прикрепляются к моделям, в дальнейшем обрабатывают файл и прикрепляют файл в указанный атрибут модели. 

####Имеется два загрузочных поведения: 
- `FileUploadBehavior` - поведение рассчитано на загрузку одного файла;
- `FilesUploadBehavior` - поведение рассчитано на загрузку нескольких файлов. 

Поведения подключаются так: 
```php
 public function behaviors()
    {
        return [
            [
                'class' => FileUploadBehavior::className(), // Поведение
                'attribute' => 'image', // Атрибут модели
                'group' => 'photos', // Сохраняемая группа
                'type' => 'detail', // Тип файла в группе объектов
                'storage' => 'fileStorage', // Компонент хранения файлов 
                'repository' => UploadedFile::class, // Репозиторий
            ],
        ];
    }
```
4) Подключение репозиториев.

К каждому поведению нужно настроить способ получения файла, а именно - настроить репозиторий. 

Всего имеется два репозитория для обычной и удаленной загрузки.

####Репозитории: 
- `UploadedFile` - загрузка обычных файлов(через POST запрос);
- `RemoteUploadedFile` - загрузка удаленных файлов(через ссылку на файл).

5) Настройка репозиториев.

Сам репозиторий основан на шаблоне Observer, то бишь он имеет слушателя и наблюдателя.

####В данном случае: 
- `UploadedFile` - наблюдатель;
- `ImageManager` - слушатель.

В конечном итоге, весь компонент работает на событийной модели обработки и сохранения файлов. 

Реализуется такая цепочка:
`получаем файл` -> `срабатывает событие` -> `производится обработка файла` -> `сохранение файла`.

6) Настройка слушателей.

Слушатели подписываются на события наблюдателя, после чего каждый слушатель получает информацию о файле в момент сохранения файла. Каждый слушатель может производить над файлом свои действия, в результате чего производится видоизменение файла и различные побочные дейтсвия.

Все слушатели должны реализовывать `ListenerInterface`, только так они могут подписаться на наблюдателя.

В компоненте реализованы два базовых слушателя: `ImageManager` и `ThumbManager`. 

`ImageManager` работает с только изображениями, производит  `resize`, накладывает `watermark`, меняет расширение и т.д. Он имеет следующие настройки: 

- `width` - ширина изображения;
- `height` - высота изображения;
- `encode` - расширение изображения;
- `quality` - качество изображения;
- `watermark` - путь на водяную метку;
- `watermarkPosition` - позиция водяной метки;
- `imageClass` - компонент работы с изображениями.

`ThumbManager`, аналогично предыдущему, работает только с изображениями. Его задача: генерировать thumbnail изображения. Все сохраненные thumbnail сохраняются в отдельную папку под названием thumbs. Базовая структура сохранения такая: 

`'{relay}/{group}/{basename}/{type}_{width}x{height}.{ext}'`
####, где:
 - `relay` - полный `root` путь до группы;
 - `group` - название сохраняемой группы; 
 - `basename` - базовое имя файла;
 - `type` - тип изображения. Есть несколько базовых типов: `thumbs`, `cover`, `contain`, `widen`, `heighten`. Каждый тип говорит о том, каким методом данное изображение было сгененировано;
 - `width` - ширина;
 - `height` - высота;
 - `ext` - расширение файла.

Все параметры по настройки `ThumbManager` аналогичны `ImageManager`. Каждая настройка применяется над файлом, после сохранения все данные настройки будут отображены на конечном сохраненном файле.

Полные настройки имеют следующий вид: 
```php
    public function behaviors()
    {
        return [
            [
                'class' => FileUploadBehavior::className(), // подключаемое поведение
                'attribute' => 'image', // атрибут, куда будет помещен файл
                'group' => 'photos', // группа сохраняемого изображения
                'type' => 'detail', // тип сохраняемого изображения в группе
                'storage' => 'fileStorage', // компонент хранения
                'repository' => UploadedFile::class, // выбранный загрузчик
                'repositoryOptions' => [ // опции репозитория
                    'listeners' => // список слушателей
                        [
                            [
                                'class' => ThumbsManager::className(), // Класс слушателя
                                'width' => 640, // Ширина
                                'height' => 480, // Высота
                                'encode' => 'jpg', // Расширение
                                'quality' => 100, // Качество в процентах
                                'watermarkPath' => '/path/to/image/watermark.png', // Наложенная водяная метка
                                'watermarkPosition' => Position::CENTER, // Позиция водяной метки
                                'imageComponent' => 'imageComponent', // Имя компонента для работы изображениями
                            ],
                            [
                                'class' => ImageManager::className(), // Класс слушателя
                                'width' => 640, // Ширина
                                'height' => 480, // Высота
                                'encode' => 'jpg', // Расширение
                                'quality' => 100, // Качество в процентах
                                'watermarkPath' => '/path/to/image/watermark.png', // Наложенная водяная метка
                                'watermarkPosition' => Position::CENTER, // Позиция водяной метки
                                'imageComponent' => 'imageComponent', // Имя компонента для работы изображениями
                                'accessRole' => 'role_example', // Роль разрешенная для работы с изображениями
                            ]
                        ],
                ]
            ],
        ];
    }
```

7) Пример реализации контроллера с загрузкой файла.

В примере реализации контроллера с загрузкой файла можно увидеть метод использования функционала поведения. 
В результате чего будет получен и сохранен загружаемый файл.

```php

    /**
     * Загрузка изображения
     *
     * @return string
     * @throws \yii\base\InvalidParamException
     * @throws NotUploadFileException
     */
    public function actionIndex()
    {
        $form = new FileForm(); // Инициализация формы

        $request = \Yii::$app->request;

        if ($request->isPost) {
            $form->load(\Yii::$app->request->post(), ''); // Загрузка параметров
            if ($form->validate() && $form->upload()) { // Валидация и загрузка файлов
                return json_encode(['success' => true]); // Выдача сообщения о успешной загрузки
            }
        }

        throw new NotUploadFileException('Файл не был загружен.');
    }
    
```
Все остальные примеры можно посмотреть в [папке с примерами](examples).

## Тестирование

Реализовано базовое `unit` тестирование. 

Чтобы запустить тесты, нужно выполнить данную команду в корне компонента `filestorage`: 

```bash
./../../vendor/bin/phpunit
```

####Реализованы следующие тесты: 
- `ObserverTest` - тестирование работоспособности событийной системы, а именно слушателя и наблюдателя;
- `UploadedFileTest` - тестирование репозитория базовой загрузки;
- `RemoteUploadedFileTest` - тестирование репозитория удаленной загрузки; 
- `ImageManagerTest` - тестирование менеджера работы с изображениями;
- `ThumbManagerTest` - тестирование менеджера для генерирования thumbnail;
- `ImageContainer` - тестирование сервиса для работы с изображениями;
- `PathServiceTest` - тестирование сервиса для работы с путями;
- `UsageTest` - тестирование полной цепочки действий, от начала загрузки до обработки файлов.

Также, во время тестирования происходит работа с тестовой базой данных, она находится в `/data/database/test.db`. После каждого теста связанного с базой данных - она очищается!