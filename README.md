# Example Drupal 8 custom block with functional unit test

This is an example of a Drupal 8 custom block with functional unit test for demonstration purposes.

Feel free to comment or provide insight of code critiques but intent of this project
was to practice on Drupal 8 techniques along with providing example code for the Drupal 8 community
for those that would find it a useful reference.

## Drupal 8 custom block and code techniques Used

### Block configuration

The block has a set of configurations that can be set when
the Block is placed in the Drupal Block Layout.

These configurations are used for both query parameters and
output in the template.

For refrence: [Create a custom block](https://www.drupal.org/docs/8/creating-custom-modules/creating-custom-blocks/create-a-custom-block)

### Block Lazy Loading

The block lazy loads content.
After the page loads the block, a Drupal 8 ajax request is made
to load additional data.

This allows the custom block itself to be cached but the content
to continue to load fresh data allow the block content
to be updated while the page continues to be cached.

For reference: [Auto-placeholdering](https://www.drupal.org/docs/8/api/render-api/auto-placeholdering)

### Theme templates

The custom block outputs HTML based on a render array with data
that is processed in the custom block twig template.

The twig template is identified in the `hook_theme()` function in the
`.module` file.

For reference: [Create custom twig templates for custom module](https://www.drupal.org/docs/theming-drupal/twig-in-drupal/create-custom-twig-templates-for-custom-module)

### Entity Query

The Block class utilizes the Drupal 8 Entity Query to both limit
the range of the nodes but query nodes that match
a particular set of parameters.

### Functional Unit Tests

Drupal 8 functional unit tests are added for testing of the
custom block functionality. The test utilizes the BlockTestBase.php
funtional unit test Drupal 8 core class for initial base setup.

The functional tests make use of [Drupal 9 asserts](https://api.drupal.org/api/drupal/core%21modules%21block%21tests%21src%21Functional%21BlockTest.php/class/BlockTest/8.9.x)
and avoid any that have been deprecated.

For reference: [PHPUnit Browser test tutorial](https://www.drupal.org/docs/testing/phpunit-in-drupal/phpunit-browser-test-tutorial)

