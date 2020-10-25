Алетернатива Apache конфигурация nginx для роутинга запросов.
```
server {
    # ...
    location / {
        # ...
        rewrite ^/(.*)$ /index.php?u=/$1;
        try_files $uri $uri/ =404;
        # ...
    }
    # ...
}
```

Структура приложения
```
├── example                            
│   ├── app                                     
│   │   ├── app.ini
│   │   ├── app.php
│   │   ├── assets
│   │   │   ├── components
│   │   │   │   ├── css
│   │   │   │   ├── fonts
│   │   │   │   └── js
│   │   │   │       ├── bootstrap.min.js
│   │   │   │       ├── jquery.easings.min.js
│   │   │   │       ├── jquery.min.js
│   │   │   │       └── modernizr.js
│   │   │   ├── css
│   │   │   │   ├── 404.css
│   │   │   │   └── list.css
│   │   │   ├── img
│   │   │   │   ├── lightgrain.gif
│   │   │   │   └── menu.png
│   │   │   ├── js
│   │   │   │   └── 404.js
│   │   │   └── sass
│   │   ├── classes
│   │   │   ├── config.php
│   │   │   ├── functions.php
│   │   │   ├── routes.php
│   │   │   └── template.php
│   │   ├── db.db
│   │   └── templates
│   │       ├── 404.tpl
│   │       ├── index.tpl
│   │       ├── list.tpl
│   │       └── login.tpl
│   ├── bower.json
│   ├── .composer.json
│   ├── .env
│   ├── .gitignore
│   ├── .htaccess
│   ├── index.php
│   ├── package.json
│   ├── README.md
│   └── tools.sh
├── example.php
```