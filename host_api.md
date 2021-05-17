# Host API Definition

The host is responsible to keep track of features and defines if features are enabled or disabled. Clients register new features on the host by using simple
HTTP calls. The host is made to handle multiple environments at the same time, so features can be enabled for one instance and disabled for another.

### GET|HEAD /api/feature-toggle/{environment}

Retrieves all the feature toggles for the given environment.

```json5
// Response
{
    "name": "environment", // string
    "hosts": [
        '0.0.0.0',
        'some-domain'
    ], // array<string>
    "features": [
        {
            "name": "feature_name_1", // string
            "environment": "environment", // string
            "enabled": true // boolean
        },
        {
            "name": "feature_name_2", // string
            "environment": "environment", // string
            "enabled": true // boolean
        },
    ],
}
```

*Will return a 404 status if the environment does not exist.*

### POST /api/feature-toggle/create-environment

Creates a new environment. Will return a 201 status if the creation was succesfull.

```json5
// Response
[
    {
        "name": "environment_name", // string
        "host": "0.0.0.0" // the IP/domain of the environment
    },
]
```

*Will return a 409 status if the environment is already exists.*

### GET|HEAD /api/feature-toggle/{environment}/{feature_name}

Retrieves information about the feature toggle.

```json5
// Response
{
    "name": "feature_name", // string
    "environment": "environment", // string
    "enabled": true // boolean
}
```

*Will return a 404 status if the environment is not defined.*

*Will return a 404 status if the feature is not defined.*

### POST /api/feature-toggle/{environment}/create-feature

Defines a new feature toggle with a default value. Will return a 201 status if the creation was succesfull.

```json5
// Request
{
    "name": "feature_name", // string
    "enabled": true // boolean
}
```

*Will return a 404 status if the environment is not defined.*

*Will return a 409 status if the feature is already defined.*

### PATCH /api/feature-toggle/{environment}/{name}

Updates an existing feature toggle with a new value. Will return a 200 status if the update was succesfull.

```json5
// Request
{
    "enabled": true // boolean
}
```

*Will return a 404 status if the environment is not defined.*

*Will return a 404 status if the feature is not defined.*

### DELETE /api/feature-toggle/{environment}/{name}

Removes an existing feature toggle. Will return a 200 status if the deletion was succesfull.

*Will return a 404 status if the feature or environment is not defined.*
