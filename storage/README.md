# The storage directory

The directory of this file is called storage directory, which will be removed when we execute the shell script named
`build.sh` in the root directory of this project. The storage directory is used to hold data the application uses.
All the filenames of the data files must end with `.php`. The filename ends with `.store.php` is created by the class
called `Store`. Several special files or directories is taken by the system. You can add the files as you need, but
please avoid using the files or directories listed as follow:

* error - The directory to hold error logs.
* log - The directory to hold runtime logs.
* user - The directory to hold the information of the users in the system.
* initialized.php - The file to indicate whether the application is initialized. If this file exists, means that the
application is initialized, otherwise the application is not initialized and the installation program will be executed
on the first time the user vist the application in the browser.

## Deploy notes

When you want to deploy or redeploy the application, just run the shell script named `build.sh` in the root directory of
this project. Then move the `core` and `service` directory in the `dist` directory to you deploy directory. **DO NOT
modify or remove or overwite the storage directory in the production environment.**
