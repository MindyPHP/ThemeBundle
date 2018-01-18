# Подключение

# Version strategy && manifest.json

Для работы с версионированием статики и manifest.json укажите следующее:

```yaml
framework:
    assets:
        version_strategy: 'Mindy\Bundle\ThemeBundle\VersionStrategy\ThemeJsonManifestVersionStrategy'
```

Далее при установке темы статика будет установлена по следующему пути:

```
%kernel.project_dir%/public/themes/__name__
```

где `__name__` имя темы

# Manifest

Манифест содержит описание темы, название, автора

# Предпросмотр темы

Предпосмотр темы работает с помощью `cookie`. Для включения предпосмотра необходимо установить куку `__theme_preview`
с названием темы в качестве значения
