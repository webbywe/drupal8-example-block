# Example block with unit test

This is an example block with a simple unit test for demonstration purposes.

## Techniques Used

### Block configuration

The block has a set of configurations that can be set when
the Block is placed in the Drupal Block Layout.

These configurations are used for both query parameters and
output in the template.

### Lazy Loading

The block lazy loads content.
After the page loads the block, an ajax request is made
to load additional data.

This allows the block itself to be cached but the content
to continue to load fresh data.

### Theme templates

The Block outputs a twig template with data provided.

### Entity Query

The Block class utilizes the Entity Query to both limit
the range of the nodes but query nodes that match
a particular set of parameters.

