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


## Usage

To use MChef, run the following command:

```sh
php mchef.php [command] [options]
```

To see an overview of commands, run:

```sh
php mchef.php
```

To run the example recipe use:

```sh
php mchef.php example-mrecipe.json
```