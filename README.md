# MVC Goals
* Small and simple in design.
* Simple ORM with easy access to related tables.
* Allow the use of SQL.
* Hydrated models.
* Can run on old PHP 5.6 or newer versions allowing you to host anywhere.
* Allow the use of composer to have access to others code.
* Reuse others code when appropriate (no need to reinvent the wheel).

# Setup New Project
Rough instructions on how to setup a new project.
```
download mvc.zip
unzip mvc.zip 
mv mvc/* .
mv mvc/.* .
rm -fr mvc*
composer install
edit file .env
```

# Apache
Set the www root to the "public" folder and there is a .htaccess file in the zip.

# NGINX
Under the server config for the site point the www root to the "public" folder.
Setup PHP FastCGI and the location rules look similar to this:
```
location / {
  try_files $uri $uri/ /index.php/$uri$is_args$args;
}
```
