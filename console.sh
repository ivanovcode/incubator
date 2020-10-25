#!/bin/bash
ENV=.env

DB_NAME=$(grep DB_NAME "$ENV" | cut -d '=' -f2 | cut -d '"' -f2)
DB_USER=$(grep DB_USER "$ENV" | cut -d '=' -f2 | cut -d '"' -f2)
DB_PASSWORD=$(grep DB_PASSWORD "$ENV" | cut -d '=' -f2 | cut -d '"' -f2)
SSH_HOST=$(grep SSH_HOST "$ENV" | cut -d '=' -f2 | cut -d '"' -f2)
SSH_USER=$(grep SSH_USER "$ENV" | cut -d '=' -f2 | cut -d '"' -f2)
SSH_PASSWORD=$(grep SSH_PASSWORD "$ENV" | cut -d '=' -f2 | cut -d '"' -f2)

DUMP=./tmp/dump/"$DB_NAME".sql

if [ -f "$DUMP" ]; then
    rm "$DUMP"
fi

argument="$1"
    case $argument in
      "--import" )
            sshpass -p"$SSH_PASSWORD" ssh "$SSH_USER"@"$SSH_HOST" "mysqldump "$DB_NAME" -u"$DB_USER" -p"$DB_PASSWORD"" > "$DUMP" 2>/dev/null
            mysql -u"$DB_USER" -p"$DB_PASSWORD" -D "$DB_NAME" -e "DROP DATABASE "$DB_NAME"" 2>/dev/null
            mysql -u"$DB_USER" -p"$DB_PASSWORD" -e "CREATE DATABASE "$DB_NAME"" 2>/dev/null
            mysql -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$DUMP" 2>/dev/null
            echo "Импорт данных с удаленного сервера "$SSH_HOST" выполнен"
      ;;
      "--export" )
            mysqldump "$DB_NAME" -u"$DB_USER" -p"$DB_PASSWORD" > "$DUMP" 2>/dev/null
            sshpass -p"$SSH_PASSWORD" ssh "$SSH_USER"@"$SSH_HOST" -p22 "mysql -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME"" < dump.sql
            git add .
            git add -u
            git commit -m "release $(date +%Y%m%d_%H%M)"
            git push origin api
            echo "Экспорт данных на удаленный сервер "$SSH_HOST" выполнен"
      ;;
      "--restart" )
            sudo service nginx stop
            sudo service apache2 stop
            sudo service php7.0-fpm stop
            sudo service mysql stop
            sudo service nginx start
            sudo service apache2 start
            sudo service php7.0-fpm start
            sudo service mysql start
      ;;
      "--install" )
            sudo apt update
            sudo apt install nodejs
            sudo apt install npm
            sudo apt install wget
            ln -s /usr/bin/nodejs /usr/bin/node
            wget https://getcomposer.org/download/1.8.6/composer.phar
            php composer.phar install
            rm composer.phar
            rm composer.lock
            npm install
            ./node_modules/bower/bin/bower install
      ;;
  esac
