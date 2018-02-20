# Settings Tray Translations
This module allows editing of translated blocks via the Settings Tray module.

It just exposes the translations from the core Config Translations module.
## Warning
Drupal core decided not to allow this functionality because there is no way to determine which overrides besides translation that a particular block has.
This module *does not* solve this problem. It is up to the site builder to determine if any other overrides will be used with blocks.

See the core issue for more detail: [Prevent Settings Tray functionality for blocks that have configuration overrides](https://www.drupal.org/project/drupal/issues/2919373)

Since this module simply exposes the translation forms and makes no changes to how translation are stored if Drupal core solves this problem it should be able to turn off this module and use the core solution.s



  
