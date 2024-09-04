# MChef

MChef is a command-line tool designed to manage and automate various tasks related to deploying Moodle instances with plugins in Docker containers. It leverages the `splitbrain/php-cli` library to provide a robust CLI interface.

## Features

- Recipe management
- Plugin integration
- Docker support

## Requirements

- PHP 8.x or higher
- Composer (https://getcomposer.org/download/)

## Installation

1. Clone the repository:

    ```sh
    git clone https://github.com/gthomas2/mchef.git
    cd mchef
    ```

2. Install dependencies using Composer:

    ```sh
    composer install
    ```

    or alternatively, if you installed composer in the project directory
    ```sh
    php composer.phar install
    ```

3. Install the application itself:

    ```sh
    php mchef.php -i
    ```

    This will also create a symlink so you can use the command
    ```sh
    mchef.php [command] [options]
    ```
    afterwards.


## Usage

You should create a folder for hosting your project.
In this folder you will need a recipe file - see the example-mrecipe.json file
To use MChef, run the following command in your project folder:

```sh
mchef.php [command] [options]
```

For example - if you have a recipe called recipe.json in your project folder you would run:
```sh
mchef.php recipe.json
```

To see an overview of commands, run:

```sh
mchef.php
```

To run the example recipe use:

```sh
mchef.php example-mrecipe.json
```

Search in  "/src/Model/Recipe.php" for all the possible ingredients of your recipe.
Enjoy cooking.
