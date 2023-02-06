# Trustpilot Reviews

A PHP class for scraping Trustpilot account reviews.

## Support 
- Number of reviews to return.
- Increasing and decreasing sorting by date and rating.
- Export reviews to a csv or xml file.

## Usage

### Create an Instance
Instantiates the class:
```php
require_once 'class-trustpilot-reviews.php';
  
$scraper = new Trustpilot_Reviews('account_id');

```
| Parameter | Type     | Description                             | Default |
| :-------- | :------- | :-------------------------------------- | :---:  |
| `$id`     | `string` | **Required**. ID of Trustpilot account. |         |
| `$count`  | `int`    | Defines the number of reviews to return. '-1' returns all reviews. |    -1     |
| `$orderby`| `string` | Defines by which parameter to sort reviews. Accepts: 'time' or 'rating' | time |
| `$order`    | `string`| Designates ascending or descending order of reviews. Accepts 'asc', 'desc' |  desc |

## Functions & Methods

### get_reviews()
Return array of reviews

```php
$scraper->get_reviews();
```

### generate_xml($path)
Generate xml of reviews.

```php
$scraper->generate_xml($path);
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `path`    | `string` | **Required**. Path to save generated xml file. |

### generate_csv($path, $separator)
Generate csv of reviews.

```php
$scraper->generate_xml($path, $separator);
```

| Parameter | Type     | Description                       | Default |
| :-------- | :------- | :-------------------------------- |:------:
| `path`      | `string` | **Required**. Path to save generated csv file. | |
| `separator` | `string` | The optional separator parameter sets the field delimiter. | ','

## Optimization

What optimizations did you make in your code? E.g. refactors, performance improvements, accessibility

The process of recovering reviews can be quite long depending on the number of reviews to be recovered. A good strategy would be to temporarily save reviews in the db or cache, and retrieve them again only when needed.

Se utilizzi WordPress una buona strategia potrebbe essere sfruttare i transients.

### Example

```php
<?php

if ( false === ( $reviews = get_transient( 'trustpilot_reviews' ) ) ) {

  $scraper = new Trustpilot_Reviews('account_id', '-1', 'time', 'desc');
  $reviews = $scraper->get_reviews();
  set_transient( 'trustpilot_reviews', $reviews, 24 * HOUR_IN_SECONDS );
  
}

?>
```

In this case the reviews are saved in a transient with validity of 24 hours in the database. In this way, instead of performing the whole process of scraping and parsing, as long as the transient is valid, they are recovered directly from the database, and only when the transient expires they will be scraped again.
## Authors

- [@Giulio Delmasto](https://www.github.com/giuliodelmastro)
