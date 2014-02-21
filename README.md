# Module Free Shipping Thelia 2

This module is used to offer shipping from a minimum amount on a specific shipping zone.

## How to install

This module must be into your ```modules/``` directory (thelia/local/modules/).

You can download the .zip file of this module or create a git submodule into your project like this :

```
cd /path-to-thelia
git submodule add https://github.com/thelia-modules/FreeShipping.git local/modules/FreeShipping
```

Next, go to your Thelia admin panel for module activation.

## How to use

You can manage your free shipping rules on the configuration view of the module with the "configure" button on the modules list.

After defining a rule, commands that respect it will have their shipping costs equal to 0 (the shipping costs will be calculated automatically).
