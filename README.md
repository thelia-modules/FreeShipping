# Module Free Shipping Thelia 2

This module is used to offer shipping from a minimum amount on a specific shipping zone.

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is ```FreeShipping```.
* Activate it in your thelia administration panel

### Composer

Add it in your main thelia composer.json file

```
composer require thelia/free-shipping-module:~1.1.0
```

Next, go to your Thelia admin panel for module activation.

## How to use

You can manage your free shipping rules on the configuration view of the module with the "configure" button on the modules list.

After defining a rule, commands that respect it will have their shipping costs equal to 0 (the shipping costs will be calculated automatically).
