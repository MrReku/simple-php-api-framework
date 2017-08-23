# Simple PHP API Framework #

The goal is to provide an extremely quick solution for having an api service up and running in no time.
Every service is self contained in its own folder with the related config JSON file within the config folder.

The flow can't be easier than this:

1- Every time a request is made service and endpoint are evaluated from the URL
2- The core code check for the service presence within the config folder
3- If found, it will validate if the endpoint requested is present and if it is an open or protected method.
4- Once the request is fully validated (for protected method the code needs also to validate `token_id` and `secret_id` against db) include the service folder.
5- Within your service folder you define the logic and the consequent data for each end point that will be returned to the core code in order to be published by the api. 

The Api Framework comes with the following basic features:

a- HTTP status codes
b- Open and Protected Methods via token and secret id
c- Automatic creation of table and related users and credential for each service
d- Debug info via `$API->API_show_env`
e- Reserved internal authentication endpoint

## Example Included ##

Basic service named `test` available at `/src/test`
Config Available at `/config/test.json`
Define a valid credential for the protected method `protected_test`

## Results: ##

1- YOUR_DOMAIN/test/protected_test/?token_id=YOUR_TOKEN&secret_id=YOUR_SECRET

will output:

```
{
	"response": "We swore to protect."
}
```

2- YOUR_DOMAIN/test/open_test/

will output:

```
{
	"response": "Hello Open World."
}
```


Enjoy