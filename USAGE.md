### Accessing features

For simple feature checks, there is a FeatureToggle interface. This acts as a mediator for checking feature states within an environment.

```php
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use Nusje2000\FeatureToggleBundle\RepositoryFeatureToggle;

/** @var FeatureRepository $repository */

$toggle = new RepositoryFeatureToggle($repository, 'some-env');

// Fetching features
$toggle->get('feature-name');
$toggle->exists('feature-name');
$toggle->isEnabled('feature-name');
$toggle->isDisabled('feature-name');

// Feature assertions, will throw exceptions if condition is not met
$toggle->assertDefined('feature-name');
$toggle->assertEnabled('feature-name');
$toggle->assertDefined('feature-name');
```

### Configuration reference

TODO

### Advanced usage

#### Custom repositories

TODO

#### Using the API

TODO
