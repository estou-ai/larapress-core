# Larapress Core

**Larapress Core** is the essential runtime package for the [Larapress Framework](https://github.com/estou-ai/larapress-framework).\
It contains the base classes, contracts, traits, and components that power the development of WordPress plugins using Larapress.

---

## ✨ Features

- **BaseResource**: extendable class to quickly build WordPress admin resources
- **Contracts & Traits**: common interfaces and reusable logic for consistency
- **Components**: ready-to-use Form and Table builders for WordPress admin UI
- **Utilities**: helper functions and validation powered by [rakit/validation](https://github.com/rakit/validation)
- **PSR-4 autoloading**

---

## 📦 Installation

This package is not meant to be used standalone. It should be installed as a dependency of the Larapress Framework.

In your project (created with `larapress-framework`):

```bash
composer require larapress/core:dev-main
```

Or if using the local repository setup:

```json
"repositories": {
  "larapress_core": {
    "type": "path",
    "url": "../core",
    "options": {
      "symlink": true
    }
  }
}
```

Then run:

```bash
composer update larapress/core
```

---

## 🧩 Example

Creating a new Resource in your plugin project that extends the Core:

```php
namespace App\Resources;

use Larapress\Resources\BaseResource;
use Larapress\Contracts\ResourceContract;
use Larapress\Contracts\HasTableContract;
use Larapress\Components\Form\Form;
use Larapress\Components\Form\Input;
use Larapress\Components\Table\Table;
use Larapress\Components\Table\Column;

class Customers extends BaseResource implements ResourceContract, HasTableContract
{
    public function form(Form $form): Form
    {
        return $form->schema([
            Input::make('name')->setLabel('Name')->setType('text')
        ]);
    }

    public function getTable(): Table
    {
        return Table::make()->schema([
            Column::make('id')->label('ID')->sortable(),
        ])->data([]);
    }
}
```

---

## 🤝 Contributing

Contributions are welcome!\
Fork the repository [larapress-core](https://github.com/estou-ai/larapress-core), create a branch, and open a Pull Request.

---

## 📄 License

This project is licensed under the **MIT** license.

