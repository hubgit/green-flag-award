<?php

require_once 'vendor/autoload.php';

/**
 *
 */
class GreenFlagResource extends WebResource\Resource {
    /**
     * @return array
     */
    protected function parse ($data) {
        preg_match(
            '/var locations = (\[\[.+?\]\])/s',
            $data,
            $matches
        );

        return json_decode($matches[1], true);
    }
}

$areas = [
    'north-east',
    'yorkshire-humberside',
    'north-west',
    'east-midlands',
    'west-midlands',
    'east-of-england',
    'london',
    'south-west',
    'south-east',
    'wales',
    'scotland',
];

$features = array_reduce($areas, function ($features, $area) {
    $resource = new GreenFlagResource(sprintf(
        'http://www.greenflagaward.org.uk/award-winning-sites/%s/',
        $area
    ));

    foreach ($resource->get() as $location) {
        //print_r($location);

        list($name, $lng, $lat, $summary, $image, $id) = $location;

        if (!in_array($id, ['1988', '1454'])) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float) $lat,
                        (float) $lng
                    ],
                ],
                'properties' => [
                    'name' => $name,
                    'image' => 'http://www.greenflagaward.org.uk/data/UploadedDocuments/' . $image,
                    'url' => 'http://www.greenflagaward.org/park-summary/?ParkID=' . $id,
                    'summary' => $summary // TODO: extract data from each location's page
                ],
            ];
        }
    }

    return $features;
}, []);

file_put_contents('green-flag-award.json', json_encode([
    'type' => 'FeatureCollection',
    'features' => $features,
], JSON_PRETTY_PRINT));
