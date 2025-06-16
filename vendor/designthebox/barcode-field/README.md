# Filament Barcode Scanner Input

[![Latest Version on Packagist](https://img.shields.io/packagist/v/designthebox/barcode-field.svg?style=flat-square)](https://packagist.org/packages/designthebox/barcode-field)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/designthebox/barcode-field/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/designthebox/barcode-field/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/designthebox/barcode-field/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/designthebox/barcode-field/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/designthebox/barcode-field.svg?style=flat-square)](https://packagist.org/packages/designthebox/barcode-field)

## Table of Contents
- [Overview](#overview)
- [Screenshot](#screenshot)
- [Installation](#installation)
- [Usage](#usage)
- [Advanced Usage](#advanced-usage)
- [Features](#features)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Credits](#credits)
- [License](#license)

## Overview

The **Filament Barcode Scanner Input** package offers a user-friendly barcode input field for your Filament applications. This component supports dynamic scanning, enabling users to input barcodes seamlessly.

Key features include:

- **Modal Popup:** The component opens a modal popup for barcode scanning, providing a dedicated interface for users to scan and input barcodes without cluttering the main form.
  
- **Customizable Icon:** Users can customize the input field with their own icons, enhancing the visual appeal and allowing for better integration with existing designs.

With these features, the Filament Barcode Scanner Input package ensures an efficient and aesthetically pleasing user experience for barcode entry in your application.

## Screenshot

![Input Screenshot](https://raw.githubusercontent.com/Design-The-Box/barcode-field/main/assets/images/Input-Screenshot.png)

## Installation

You can install the package via Composer:

```bash
composer require designthebox/barcode-field


```
Publish Assets:

```
php artisan vendor:publish --tag=barcode-scanner-assets
```

## Usage

Once installed, you can use the BarcodeInput component in your Filament forms:

```php
 use DesignTheBox\BarcodeField\Forms\Components\BarcodeInput;

// In your form definition
BarcodeInput::make('barcode')
    ->icon('heroicon-o-arrow-right') // Specify your Heroicon name here
    ->required(),
```
##  Features

- Customizable icon using Heroicons.
- Responsive design for optimal use on various devices.
- Easy integration into your existing Filament forms.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Anthony Elleray](https://github.com/AElleray)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


