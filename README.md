# Example Drupal 8 custom block with functional unit test

This is an example of a custom Drupal 8 block with functional unit test for demonstration purposes.

## Drupal 8 custom block and code techniques Used

### Block configuration

The block has a set of configurations that can be set when
the Block is placed in the Drupal Block Layout.

These configurations are used for both query parameters and
output in the template.

### Block Lazy Loading

The block lazy loads content.
After the page loads the block, a Drupal 8 ajax request is made
to load additional data.

This allows the custom block itself to be cached but the content
to continue to load fresh data allow the block content
to be updated while the page continues to be cached.

### Theme templates

The custom block outputs HTML based on a render array with data
that is processed in the custom block twig template.

The twig template is identified in the `hook_theme()` function in the
`.module` file.

### Entity Query

The Block class utilizes the Drupal 8 Entity Query to both limit
the range of the nodes but query nodes that match
a particular set of parameters.

### Functional Unit Tests

Drupal 8 functional unit tests are added for testing of the
custom block functionality. The test utilizes the BlockTestBase.php
funtional unit test Drupal 8 core class for initial base setup.

